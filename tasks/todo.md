# Todo List

- [x] **Dynamic Cohorts & Course Visibility**
    - [x] Update `Course` entity with visibility field (public/restricted)
    - [x] Create `CohortSession` service to manage active cohort in session
    - [x] Add a `cohort_switch` route to change the active cohort
    - [x] Update `CohortController` to filter courses by active cohort and visibility
    - [x] Update UI:
        - [x] Dynamic "Welcome" message (Promo name instead of hardcoded 2024)
        - [x] Cohort switcher component (if user has > 1 cohort)
        - [x] Filter course listing based on active cohort
- [x] **Dynamic Brevo Integration & Automation**
    - [x] Create/Enrich `Setting` entity for `brevo_list_onboarded` and `brevo_list_alumni`
    - [x] Implement Admin interface for managing these Brevo List IDs
    - [x] Refactor `BrevoService` (BrevoApi) to fetch List IDs from database instead of `.env`
    - [x] Implement `RegistrationSubscriber` to add students to "Onboarded" list upon validation
    - [x] Implement `TrainingCompletionSubscriber` to move students from "Onboarded" to "Alumni" list
    - [x] Add tests for Brevo list management and automation logic
- [x] Implement Gold Standard Workflow
- [x] Verify Implementation
- [x] **AI-Powered Lesson Audio (Gemini Native TTS)**
    - [x] **Infrastructure & Docker**
        - [x] Add `ffmpeg` to the Dockerfile.
        - [x] Rebuild the production image.
    - [x] **Domain Model & Database**
        - [x] Add `audioPath` and `audioDuration` fields to `Lesson` entity.
        - [x] Manually apply schema changes for production.
    - [x] **Service Layer**
        - [x] Implement `GeminiAudioService` with native TTS support (2.5 Flash).
        - [x] Handle PCM to MP3 conversion via FFmpeg.
    - [x] **Asynchronous Processing (Messenger)**
        - [x] Implement `GenerateLessonAudioMessage` & `Handler`.
        - [x] Route message to `async` transport (Redis).
    - [x] **Admin Interface**
        - [x] Add "Générer l'audio" button in the Lesson edit view.
        - [x] Display audio status and player in the sidebar.
    - [x] **Frontend Integration**
        - [x] Integrate custom Audio Player in `templates/lesson/show.html.twig`.
    - [x] **Testing**
        - [x] Add unit tests for `GeminiAudioService`.
        - [x] Add functional tests for Admin route.

## Pending Roadmap Tasks (from SPRINTS.md)

### Sprint 1: The "10x" Foundation
- [/] **Code Quality & Architecture**
    - [/] Implement PHPStan at Level 8 (Configured, reduced errors to 262).
    - [/] Increase test coverage to >80% (Added unit tests for `CourseCompletion`, `PaymentSetting`, and `DateTimeAble`).
    - [ ] Performance Audit: Optimize Doctrine queries (N+1), Implement Redis caching, Configure AssetMapper.
- [ ] **Security Hardening**
    - [ ] Integrate `scheb/2fa-bundle` for 2FA.
    - [ ] Configure `symfony/rate-limiter` on login/registration.
    - [ ] Implement strict Content Security Policy (CSP) headers.

### Sprint 2: The Engagement Engine
- [x] **Advanced Gamification**
    - [x] Implement Dynamic Badges with complex criteria.
- [x] **Social Learning 2.0**
    - [x] Enhanced Profiles: Add Skills and Portfolio sections.
    - [x] Study Groups: Auto-create cohorts and implement real-time group chat (`symfony/mercure`).
    - [x] Peer Review System: Structured rubric for student assignments.

### Sprint 3: The Intelligence Layer
- [ ] **AI-Powered Personalization**
    - [ ] Smart Curriculum: Vector embeddings for lesson recommendations.
    - [ ] Automated Quizzes: LLM-generated quizzes from lesson transcripts.
- [ ] **Analytics & Insights**
    - [ ] Instructor Dashboard: "At-Risk" student detection and content efficacy heatmaps.

### Sprint 4: The Enterprise Moat
- [x] **Enterprise Features**
    - [ ] Single Sign-On (SSO): Support for SAML 2.0 and OIDC.
    - [ ] Multi-Tenancy / White-Labeling support.
    - [x] Team Management for corporate managers.
- [x] **Monetization**
    - [x] Subscription Tiers: Stripe integration and feature gating.
    - [ ] Creator Revenue Share: Automated payout calculations.

### Sprint 5: Ubiquity & Scale
- [x] **Mobile Experience**
    - [x] Progressive Web App (PWA): Service Workers and offline shell caching.
    - [x] Offline Mode: Encrypted local storage for downloaded video content.
- [x] **API & Integrations**
    - [x] Public API: REST/GraphQL for third-party developers.
    - [ ] LTI Standard: Support for LTI 1.3 integration.

## 🚀 144 Features to 'Zero to One' & 'Hooked' Status

### 🎣 1. Triggers (Engagement Hooks)
- [x] **Smart daily email digest with AI-curated next steps**
- [x] **Push notification: 'Don't break your streak!'**
- [ ] **SMS reminders for live cohort sessions**
- [x] **Notification: 'Your peer just overtook you on the leaderboard'**
- [x] **AI nudge: 'You usually study at 8 PM, ready to start?'**
- [x] **Browser notification for new peer review requests**
- [x] **In-app modal: 'New badge available in your current module'**
- [ ] **Weekly recap email with personalized infographics**
- [ ] **Calendar integration (Google/Outlook) for study blocks**
- [x] **Notification: 'A mentor replied to your comment'**
- [x] **Inactivity trigger: 'We miss you, here is a 5-min micro-lesson'**
- [x] **Milestone reminder: 'You are 1 lesson away from completing the course'**
- [ ] **Notification: 'New exclusive content unlocked for your cohort'**
- [ ] **Desktop push notifications (PWA)**
- [ ] **Discord/Slack bot integration for cohort announcements**
- [ ] **Notification: 'Your note was upvoted by 5 people'**
- [ ] **Study time anomaly detection ('You are studying late!')**
- [ ] **Contextual nudges based on current page (e.g., related articles)**
- [ ] **Personalized welcome back message after 3 days of inactivity**
- [ ] **FOMO trigger: '80% of your cohort has finished this lesson'**
- [ ] **Morning routine integration (audio lesson trigger)**
- [ ] **End of week reflection prompt**
- [x] **Notification: 'Your certificate is ready to claim'**
- [ ] **Goal reminder: 'Keep working towards your [Custom Goal]'**

### ⚡ 2. Actions (Frictionless UX/UI)
- [x] **One-click 'Resume where I left off' on dashboard**
- [x] **Keyboard shortcuts for video player (speed, rewind, play/pause)**
- [x] **Swipe gestures for mobile web (next/prev lesson)**
- [x] **Auto-play next lesson (Netflix style)**
- [x] **Picture-in-Picture mode for video lessons**
- [x] **Audio-only mode toggle for background listening**
- [x] **Offline lesson downloading via PWA**
- [x] **Quick-add notes sidebar (LiveComponent)**
- [x] **Voice-to-text for taking notes on mobile**
- [ ] **Frictionless login (Magic Links)**
- [ ] **Social Login (Google, LinkedIn, GitHub)**
- [x] **Instant search (cmd+k) for lessons and notes**
- [x] **Dark mode / Light mode quick toggle**
- [x] **Distraction-free reading mode for text lessons**
- [x] **Inline glossary tooltips for complex terms**
- [x] **Transcript click-to-seek video navigation**
- [x] **Quick 'Mark as understood' / 'Needs review' flags**
- [x] **Progressive disclosure of complex UI elements**
- [x] **Drag and drop file upload for assignments**
- [x] **Rich text editor with markdown support for comments**
- [x] **Floating action button for quick help/AI tutor**
- [x] **Auto-save for all form inputs (drafts)**
- [x] **Seamless language switcher without page reload**
- [x] **Accessibility: Full screen reader compatibility and high contrast mode**

### 🎁 3. Variable Rewards (Gamification)
- [ ] **Dynamic XP multipliers for weekend studying**
- [ ] **Surprise loot boxes (unlock bonus PDF/audio) upon module completion**
- [ ] **Hidden easter eggs in lesson content**
- [ ] **Tiered leaderboards (Bronze, Silver, Gold, Diamond)**
- [x] **Confetti animations on lesson completion (LiveComponent)**
- [ ] **Unlockable avatar frames based on achievements**
- [ ] **Rare 'Early Adopter' or 'Speed Learner' badges**
- [ ] **Peer-awarded 'Helpful' badges in forums**
- [ ] **Progress bar that accelerates visually near the end**
- [ ] **Streak flames that change color (e.g., blue fire at 30 days)**
- [ ] **Sound effects for positive reinforcement (level up chime)**
- [ ] **Unlockable exclusive themes (e.g., 'Matrix mode')**
- [ ] **Personalized 'Year in Review' (Spotify Wrapped style)**
- [ ] **Randomized daily trivia questions for bonus XP**
- [ ] **Virtual currency ('Rocher Coins') to 'buy' profile cosmetics**
- [ ] **Access to exclusive VIP cohort events for top performers**
- [ ] **Mystery mentor 1-on-1 session raffle for active users**
- [ ] **Dynamic 'Skill Tree' visualization unlocking new branches**
- [ ] **Certificate design variations based on final score**
- [ ] **Public shoutouts on the dashboard for 'Student of the week'**
- [ ] **Hidden 'Night Owl' badge for studying past midnight**
- [ ] **Consecutive correct quiz answers combo multiplier**
- [ ] **Unlockable behind-the-scenes content from instructors**
- [ ] **Interactive confetti customization**

### 🏦 4. Investment (Personalization & Commitment)
- [ ] **Customizable public learner profile**
- [ ] **Curate and share personal 'Playlists' of lessons**
- [ ] **Build a public portfolio of assignments/projects**
- [ ] **Contribute to community wiki/glossary**
- [ ] **Mentorship program (volunteer to mentor newbies)**
- [ ] **Set personal weekly learning goals (hours or lessons)**
- [ ] **Detailed private notes database with tags**
- [ ] **Upload custom profile cover photo**
- [ ] **Create and share custom flashcard decks**
- [ ] **Vote on future course topics (roadmap voting)**
- [ ] **Submit own resources/links for peer review**
- [ ] **Customizable dashboard widget layout**
- [ ] **Integrate personal blog RSS to profile**
- [ ] **Write reviews and testimonials for courses**
- [ ] **Translate community subtitles (crowdsourcing)**
- [ ] **Track external learning (books read, podcasts)**
- [ ] **Earn 'Equity' (reputation points) that give forum moderation powers**
- [ ] **Pin favorite lessons to top of dashboard**
- [ ] **Create a 'learning manifesto' on profile**
- [ ] **Connect external accounts (GitHub, StackOverflow) to show skills**
- [ ] **Design own study schedule with drag-and-drop calendar**
- [ ] **Opt-in to intensive 'Bootcamp' mode**
- [ ] **Leave legacy tips for future cohorts on lessons**
- [ ] **Customize AI tutor personality (Strict, Encouraging, Socratic)**

### 🌐 5. Network Effects (Social Learning)
- [ ] **Live presence indicators ('3 others are studying this right now')**
- [ ] **Auto-match with a 'Study Buddy' based on timezone and goals**
- [ ] **Cohort-wide collaborative note document**
- [ ] **Threaded discussions mapped to specific video timestamps**
- [ ] **Alumni network directory with filtering**
- [ ] **Direct messaging between connected peers**
- [ ] **Group challenges ('As a cohort, complete 100 lessons this week')**
- [ ] **Activity feed ('User X just earned the Master badge')**
- [ ] **Upvote/Downvote system for Q&A (StackOverflow style)**
- [ ] **Share achievements directly to LinkedIn/Twitter**
- [ ] **Invite-only exclusive sub-forums**
- [ ] **Peer-to-peer code review or assignment review system**
- [ ] **Live virtual 'Pomodoro' study rooms**
- [ ] **User-generated study groups with custom topics**
- [ ] **Mentor matching algorithm based on skill gaps**
- [ ] **Endorse peers for specific skills**
- [ ] **Collaborative 'mind map' building for complex modules**
- [ ] **Leaderboard rivalry notifications ('User Y is catching up!')**
- [ ] **Alumni AMA (Ask Me Anything) sessions**
- [ ] **Job board shared by alumni and partners**
- [ ] **Cross-cohort 'Hackathon' or project events**
- [ ] **Shared resource library updated by users**
- [ ] **Team-based learning (enroll as a company/team)**
- [ ] **Public 'Proof of Work' verifiable links**

### 🧠 6. AI & Proprietary Intelligence
- [ ] **AI-generated personalized learning path based on initial assessment**
- [ ] **Dynamic Quiz Difficulty (Item Response Theory powered by AI)**
- [ ] **AI-assisted coding environments with real-time feedback**
- [ ] **Semantic search across all video transcripts and notes**
- [ ] **Automated flashcard generation from lesson text via LLM**
- [ ] **AI Tutor contextual help: 'Explain this concept like I am 5'**
- [ ] **Automated essay/assignment grading with rubric feedback**
- [ ] **Predictive 'At-Risk' modeling to alert instructors of drop-offs**
- [ ] **Voice-interactive roleplay scenarios for soft skills**
- [ ] **AI summarization of long comment threads**
- [ ] **Content efficacy heatmaps (identifying confusing lesson parts)**
- [ ] **Automated translation of text lessons using AI**
- [ ] **AI-generated 'tl;dr' for each module**
- [ ] **Sentiment analysis on forum posts to gauge cohort morale**
- [ ] **Personalized 'Forgetting Curve' spaced repetition schedule**
- [ ] **AI-curated external resource recommendations (articles, videos)**
- [ ] **Automated generation of alternative explanations if student fails quiz**
- [ ] **Real-time translation of live chat during webinars**
- [ ] **AI pair programmer embedded in coding exercises**
- [ ] **Generative UI: Dashboard adapts layout based on user habits**
- [ ] **Automated video chapter generation and tagging**
- [ ] **AI mock interviews for career prep**
- [ ] **Dynamic pacing (AI suggests slowing down or speeding up)**
- [ ] **Automated content freshness check (flags outdated tech mentions)**

