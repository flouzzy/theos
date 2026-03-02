# Protocole de Bascule : Académie Le Rocher (Classique vers Docker/FrankenPHP)

Ce guide détaille les étapes pour tester et basculer de l'ancienne infrastructure (serveur web classique sur le port 80/443, MySQL sur le port 3306) vers la nouvelle infrastructure Dockerisée (FrankenPHP sur le port 8095, MySQL 8 sur le port 3307).

## Pourquoi cette approach est-elle excellente ?

1. **Isolation Totale :** L'application Dockerisée utilise sa propre instance MySQL (sur le port 3307). Si vous cassez quelque chose dans la base Docker pendant les tests sur `-v2`, la version de production rest intacte sur le port 3306.
2. **Test de montée en version :** PHP 8.4 apporte des changements. En testant sur `-v2` avec une copy de la vraie base de données, vous verrez tout de suite si vos entités Doctrine ou vos services Symfony ont besoin d'ajustements.
3. **Bascule instantanée :** Le jour où vous êtes prêt, la "bascule" ne prend que 10 secondes : c'est juste une modification de l'adresse `proxy_pass` dans la configuration Nginx principale.

## Étape 1 : Tests sur l'environnement V2 (`academie-v2.lerocher.fr`)

1. **Préparation des fichiers et uploads**
   L'ancienne version stocke probablement des fichiers uploadés par les utilisateurs sur le disque du VPS (par example, dans `/var/www/academie/public/uploads`).
   Pour que la version Docker puisse lire et écrire ces fichiers de manière transparente sans les perdre lors de la bascule, un volume a été configuré dans `compose.yaml` :
   ```yaml
   volumes:
     - /var/www/academie/public/uploads:/app/public/uploads
   ```
   *Assurez-vous que le chemin `/var/www/academie/public/uploads` correspond au chemin réel de vos uploads sur le VPS actuel.*

2. **Lancement de l'environnement de test et copy de la base de données**
   Utilisez la commande Makefile prévue pour lancer l'environnement et copier les données à la volée :
   ```bash
   make deploy-v2
   ```
   Cette commande va :
   - Mettre à jour le code (`git pull`)
   - Construire et démarrer les conteneurs (`make build`, `make up`)
   - **Cloner la base de données de production** (`make db-mirror` va lire sur le 3306 et écrire sur le 3307).
   - Installer les dépendances et vider le cache.

3. **Vérification**
   Rendez-vous sur `https://academie-v2.lerocher.fr`. Naviguez, testez l'upload de fichiers, la connection, etc.

## Étape 2 : Le jour de la bascule vers la production (`academie.lerocher.fr`)

Une fois que tout fonctionne parfaitement sur l'environnement de test V2, suivez ces étapes pour remplacer la version de production actuelle :

1. **Dernière synchronisation des données (Mode maintenance)**
   - Mettez l'ancien site en mode maintenance pour éviter de nouvelles écritures dans l'ancienne base MySQL.
   - Relancez le miroir de la base pour avoir les toutes dernières données :
     ```bash
     make db-mirror
     ```

2. **Modification du Vhost Nginx Principal**
   Ouvrez le fichier de configuration Nginx de l'environnement de production principal (ex: `/etc/nginx/sites-available/academie.lerocher.fr`).

   Modifiez l'endroit où Nginx redirige le traffic (la directive `proxy_pass` ou `fastcgi_pass` si vous utilisiez PHP-FPM) pour pointer vers le conteneur FrankenPHP :

   **Ancienne configuration (example PHP-FPM) :**
   ```nginx
   location / {
       try_files $uri /index.php$is_args$args;
   }
   location ~ \.php$ {
       fastcgi_pass unix:/run/php/php8.1-fpm.sock;
       # ...
   }
   ```

   **Nouvelle configuration (Proxy vers Docker) :**
   ```nginx
   location / {
       proxy_pass http://127.0.0.1:8095;

       # Headers obligatoires pour Symfony et les WebSockets
       proxy_set_header Host $host;
       proxy_set_header X-Real-IP $remote_addr;
       proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       proxy_set_header X-Forwarded-Proto $scheme;

       # WebSockets / Mercure Upgrade Support
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
   }
   ```

3. **Redémarrage de Nginx**
   Testez la configuration puis redémarrez Nginx pour appliquer la bascule :
   ```bash
   nginx -t
   systemctl reload nginx
   ```

4. **Validation**
   Rendez-vous sur `https://academie.lerocher.fr`. Le traffic est désormais géré par votre toute nouvelle stack Docker FrankenPHP ultra-rapide ! Vous pouvez ensuite désactiver/supprimer l'ancienne base MySQL sur le port 3306 et l'ancien code.
