# Documentation des Fonctionnalités

Ce document détaille les fonctionnalités de la plateforme Le Rocher Académie, incluant les guides d'utilisation et les configurations techniques.

---

## 🧠 Couche d'Intelligence (L'Intelligence Layer)

### 1. Personnalisation par l'IA (Recommandations)
**Description** : Propose des leçons pertinentes aux étudiants basées sur le contenu sémantique de ce qu'ils étudient.
- **Usage Utilisateur** : Les suggestions apparaissent en bas des pages de leçons ("Leçons recommandées").
- **Technique** : 
    - Service : `App\Service\RecommendationService`
    - Champ : `embeddings` dans l'entité `Lesson`.
    - Algorithme : Similarité cosinus entre vecteurs d'embeddings.

### 2. Génération Automatisée de Quiz
**Description** : Permet aux instructeurs de générer instantanément des quiz à partir d'une leçon.
- **Usage Utilisateur** : Bouton "Générer un Quiz par IA" dans l'administration des leçons.
- **Configuration** : `GEMINI_API_KEY` doit être défini dans le fichier `.env`.
- **Technique** : Utilise le modèle `gemini-1.5-flash` via le service `QuizGeneratorService`.

### 3. Analyse de l'Engagement (At-Risk Students)
**Description** : Identifie les étudiants qui risquent de décrocher.
- **Accès** : Tableau de Bord Instructeur -> `/admin/instructor/dashboard`.
- **Indicateurs** : Score de risque (0-100%) calculé selon :
    - Inactivité (dernière connexion).
    - Performance (moyenne des quiz).
    - Progression (taux de complétion du cours).
- **Technique** : `App\Service\EngagementAnalyzer`.

---

## 💬 Collaboration & Social

### 1. Chat de Cohorte en Temps Réel
**Description** : Messagerie instantanée pour les membres d'une même cohorte.
- **Usage Utilisateur** : Onglet "Chat" dans l'espace cohorte.
- **Technique** : 
    - Intégration **Mercure** pour le temps réel.
    - Composant : `App\Twig\Components\ChatComponent`.
    - Commande Mercure (Docker) : Automatique via `make up`.

### 2. Système de Peer Review (Évaluation par les pairs)
**Description** : Les étudiants évaluent les travaux des autres selon une grille (rubrique).
- **Usage Utilisateur** : Section "Évaluations" dans les exercices.
- **Technique** : Entités `PeerReview`, `Rubric`, `RubricCriterion`.

---

## 🏆 Gamification

### 1. Badges Dynamiques
**Description** : Octroi automatique de badges selon les succès.
- **Usage Utilisateur** : Affichés sur le profil public.
- **Technique** : `BadgeManager` déclenché par des événements Symfony.

### 2. Streaks (Assiduité)
**Description** : Suit le nombre de jours consécutifs d'activité.
- **Usage Utilisateur** : Icône "Flamme" sur le dashboard étudiant.

---

## 🛠 Guide Technique & Commandes

### Configuration Environnement
Pour activer les fonctionnalités IA et temps réel :
```bash
# .env.local
GEMINI_API_KEY=votre_cle_ici
MERCURE_PUBLISH_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:8080/.well-known/mercure
```

### Commandes Utiles
- `make up` : Lance l'infrastructure (WebApp, MySQL, Redis, Mercure).
- `make db-migrate` : Applique les schémas de base de données (inclut Quiz, Embeddings).
- `make tests` : Vérifie l'intégrité de toutes les fonctionnalités (AI, Chat, Login).

### Routes Clés
- **Dashboard Instructeur** : `/admin/instructor/dashboard`
- **Profil Public** : `/profile/{username}`
- **Chat** : `/cohort/{slug}/chat`
