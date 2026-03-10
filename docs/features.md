# Documentation des Fonctionnalités - Académie Le Rocher

Ce document regroupe les guides d'utilisation et de configuration des fonctionnalités majeures de la plateforme.

---

## 📱 Progressive Web App (PWA)
Permet d'installer l'Académie comme une application native sur mobile et desktop.

### Technique
- **Fichiers clés** : `public/manifest.json`, `public/sw.js`.
- **Service Worker** : Gère le cache des ressources statiques et permet le fonctionnement hors-ligne.

### Utilisateur
- Sur **Android/Chrome** : Cliquez sur "Ajouter à l'écran d'accueil" dans le menu du navigateur.
- Sur **iOS/Safari** : Cliquez sur l'icône de partage, puis "Sur l'écran d'accueil".

---

## 📴 Mode Hors-ligne
Permet d'étudier vos leçons même sans connexion internet.

### Technique
- **Stockage** : Utilise **IndexedDB** (`offline-content-db`) via `OfflineLessonManager.js`.
- **Indicateur** : Un bandeau orange apparaît automatiquement si la connexion est perdue.

### Utilisateur
1. Sur une page de leçon, cliquez sur le bouton **"Sauvegarder hors-ligne"**.
2. Une fois enregistré, le bouton affiche "Enregistré".
3. Vous pouvez désormais revenir sur cette page sans internet.

---

## 🔌 API Publique (v1)
Interface pour les intégrations futures et applications tierces.

### Technique
- **Authentification** : JWT (JSON Web Token).
- **Endpoints** :
  - `POST /api/login` : Retourne un token (nécessite `email` et `password`).
  - `GET /api/courses` : Liste les cours de l'utilisateur (nécessite header `Authorization: Bearer <token>`).

### Commandes utiles
```bash
# Tester le login
curl -X POST http://localhost:8095/api/login -d '{"email":"...", "password":"..."}' -H "Content-Type: application/json"
```

---

## 🧠 Intelligence Layer (Sprint 3)
Fonctionnalités basées sur l'IA Gemini pour enrichir l'apprentissage.

### 💡 Recommandations
- **Utilisation** : En bas de chaque leçon, une section "Suggéré par l'IA" affiche 3 leçons sémantiquement proches du contenu actuel.
- **Technique** : Algorithme de similarité cosinus basé sur les embeddings `text-embedding-004`.

### 📝 Quiz Automatisés
- **Utilisation** : En édition de leçon (Admin), cliquez sur "Générer un Quiz (Gemini)".
- **Résultat** : Un quiz de 5 questions à choix multiples est automatiquement créé et attaché à la leçon.

### 📊 Analyses & Engagement
- **Accès** : Menu Admin > Analytics > At-Risk Students.
- **Fonctionnement** : Identifie les étudiants ayant un score de risque élevé (basé sur l'inactivité, les notes < 10/20 et la progression).

---

## 🏗️ Administration & Équipes (Sprint 4)
Gestion des cohortes, des paiements et des équipes.

### Commandes à lancer
- `make fixtures` : Pour peupler la base de test avec des données de démonstration.
- `make db-migrate` : Pour s'assurer que le schéma de la base de données est à jour.

### Pages à consulter
- `/admin` : Interface principale d'administration (Doctrine & Calendar).
- `/team` : Dashboard pour les managers d'équipe.
