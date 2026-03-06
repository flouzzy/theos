# AGENTS.md - Règles de Conduite Académie Le Rocher

Ce document définit les protocoles opérationnels que tout Agent (Jules, Gemini, etc.) doit suivre scrupuleusement.

## 👑 Principes Fondateurs
1. **Source de Vérité:** `GEMINI.md` pour l'architecture Docker actuelle.
2. **Priorité Sécurité:** Ne jamais exposer de ports conflictuels (6379, 3306) sur l'hôte sans vérification.
3. **Planification:** Toujours proposer un plan avant de modifier le `Makefile` ou le `compose.yaml`.

## 🛠 Workflow Technique Obligatoire

### Modification du Code PHP
1. Vérifier si le container est "Healthy" via `docker ps`.
2. Appliquer le changement.
3. Vérifier la syntaxe PHP (`php -l`) pour éviter de bloquer le redémarrage (l'entrypoint échouerait).
4. Vider le cache via `make cc`.

### Gestion des Base de Données
- Ne JAMAIS supprimer les données du volume `database_data` sans confirmation explicite.
- Toujours utiliser le port **3307** pour les connexions externes (DBeaver, Sequel Ace, etc.).

### Mise à jour des Dépendances
- Utiliser `make vendor` pour installer les nouveaux packages via Docker.
- Ne jamais modifier manuellement `composer.lock` à l'extérieur du container pour éviter les problèmes de plateforme.

## 📋 Gestion des Tâches
- Toute correction technique majeure doit être consignée dans `tasks/lessons.md`.
- Les tâches en attente doivent être ajoutées à `tasks/todo.md`.

## 🧪 Validation de Fin de Session
Avant de quitter, l'agent doit :
1. Vérifier que `curl -I http://localhost:8095` ne retourne pas une boucle de redirection (308 infini).
2. S'assurer que les logs Docker ne contiennent pas d'erreurs critiques (`docker compose logs -f php --tail 50`).
3. Confirmer que la base de données Docker est accessible via `database:3306` depuis le container PHP.
