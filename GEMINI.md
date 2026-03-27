# Académie Le Rocher v2 - Guide de Survie (Docker/FrankenPHP)

Ce projet est désormais la version officielle en production (migrée le 02/03/2026).

## 🛠 Tech Stack
- **PHP:** 8.4 (FrankenPHP Alpine)
- **Web Server:** FrankenPHP (Mode Worker activé)
- **Base de données:** MySQL 8.0 (Dockerisé)
- **Cache/Sessions:** Redis 7 (Alpine)
- **Email:** Mailpit (en dev/test)
- **Reverse Proxy:** Nginx (Hôte VPS) -> Docker (Port 8095)

## 🚀 Commandes Essentielles (Makefile)
Le projet se pilote entièrement via le `Makefile`.

- `make up` : Lance les containers et attend qu'ils soient "Healthy".
- `make down` : Arrête les containers.
- `make cc` : Vide le cache Symfony.
- `make db-migrate` : Applique les migrations Doctrine.

## 🔐 SSL & Service Worker (Dev)
En développement sur `https://localhost:8096`, le Service Worker peut échouer à cause du certificat auto-signé.
- **Chrome/Edge** : Activer `chrome://flags/#allow-insecure-localhost`.
- **Firefox** : Accepter l'exception de sécurité de manière permanente en visitant directement `https://localhost:8096`.

## 📁 Migration & Structure
- **Dossier:** `/var/www/lerocher/academie` (anciennement `academie-v2`).
- **Ancienne Version:** `/var/www/lerocher/academie-old`.
- **Base de données:** Données migrées depuis MySQL natif (3306) vers Docker MySQL (3307).
- **Nginx:** `academie.lerocher.fr` pointe désormais vers le container Docker sur le port 8095.

