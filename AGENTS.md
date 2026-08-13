# Project Instructions

- When the user says "roffentsd be a localt", "roffentsd be a localhostot", or the same with Hungarian accents, start the local development environment with two separate commands:
  - `php artisan serve`
  - `npm run dev`
- Do not use `composer dev` for this project on Windows, because `php artisan pail` requires the `pcntl` extension and stops the combined script.
- Kiemelten figyelj a kommentelésre a kódban.
- Mindig vizsgáld meg, hogy egy eljárás még mihez kapcsolódik, hogy ne romoljon el a működő rész.
- When the user asks to update `AGENTS.md` at the end of the day, always review and confirm what is done, what is not done, what new items were added, and update the `Next Development Priorities` section accordingly.
- MINDIG a main ágra tesszük a gitre, de erre kérdezz rá!
- A `database/database.sqlite` fájlt is mindig commitoljuk és feltöltjük a többi módosítással együtt.
- A nyilvános márkanév `KwizzGo`. A technikai azonosítók — köztük a projektmappa, repository és a `betquiz.test` helyi domain — külön kérés nélkül maradjanak változatlanok.

## Next Development Priorities

### Phase 1 - Working quizzes, registration, user management, and gameplay

- TOMORROW FIRST: complete the advertising placements, then deploy the complete application to the cPanel shared-hosting environment:
  - build responsive Google AdSense, affiliate-banner, and admin-configurable placement slots without interrupting critical gameplay;
  - audit the hosting environment for the required PHP version/extensions, Composer availability, document-root configuration, writable Laravel directories, cron support, SMTP, HTTPS, and database options;
  - prepare production environment values without committing secrets, install production dependencies, build frontend assets, run migrations safely with a backup, create the storage link or shared-hosting equivalent, and warm Laravel caches;
  - point the public web root safely at Laravel's `public` directory without exposing `.env`, source files, storage, or the SQLite database;
  - configure the scheduler/cron for weekly reports and verify login, Google OAuth callback URLs, password reset, e-mail delivery, uploads, quiz creation/import, gameplay, helpers, notifications, and responsive rendering on the live domain;
  - document the deployment and rollback procedure before considering the production rollout complete.
- Complete the `BetQuiz` -> `KwizzGo` brand migration end to end:
  - audit all remaining user-facing copy, e-mail content, metadata, assets, and configuration;
  - add or replace the final logo, favicon, social/SEO imagery, and branded e-mail styling;
  - keep technical identifiers such as the project folder, repository, database, routes, and `betquiz.test` unchanged unless explicitly requested.
- Finish the remaining gameplay-helper follow-ups:
  - add the deferred helper-package purchase surface and package rules;
  - complete browser-level balancing and interaction testing for Poker, Blackjack, 50:50, audience vote, and KwizzGoBear.
- Build advertising placements with a shared, responsive component system:
  - Google AdSense placements;
  - affiliate banner placements;
  - admin-configurable placement slots that do not interrupt critical gameplay interactions.
- Replace the remaining Phase 1 large static selectors with server-side autocomplete:
  - the admin quiz selector on the single-question edit screen;
  - the bulk quiz owner selector when the user list becomes large.
- Continue refining the global admin question bank with search, filters, pagination-safe selection, and any additional bulk operations needed beyond the per-quiz question bulk editor.
- Keep the remaining global question-bank, user-administration, and category-administration visuals unchanged for now; their further redesign is intentionally deferred until the user requests it.
- Before production launch, perform a focused cross-browser and accessibility verification of the already redesigned Phase 1 surfaces without restyling the intentionally retained admin screens.

### Phase 2 - Quiz content and expanded user features

- Add content/article sections connected to quizzes, including admin authoring and links between articles and related quizzes.
- Extend user accounts with questionnaires/surveys that can award points.
- Add friendships, including friend requests, acceptance/rejection, removal, privacy rules, and blocking considerations.
- Add direct user-to-user messaging with unread state, moderation/reporting safeguards, and notification-preference integration.
- Add clans/groups with creation, invitations or join requests, roles, membership management, and group-facing activity surfaces.
- Expand notifications to account, survey, article/content, friendship, messaging, clan/group, and other Phase 2 events.
- Apply the established design system to all new Phase 2 content and community surfaces.

### Phase 3 - Thematic house competitions

- Build admin-managed thematic house competitions:
  - selected themes and eligible quizzes;
  - configurable registration, start, and end windows;
  - participation, attempt, scoring, tie-break, and eligibility rules;
  - live and final leaderboards;
  - individual, clan/group, point, badge, or other rewards;
  - competition notifications, moderation, auditability, and result publication.
- Complete the competition-specific responsive design, result states, and administrative reporting.

## Work Log

### 2026-08-13

- Completed weekly quiz performance reporting with aggregation, preference-controlled notification delivery, and scheduled dispatch.
- Configured the provided KwizzGo SMTP account and connected password-reset and notification mail delivery; sensitive credentials remain environment-only.
- Added Google OAuth login and registration with existing verified-email account linking, first-login account creation, and inactive/banned-account enforcement.
- Added a single editable username to profiles, including first-login handling for Google users, and kept the legacy name field synchronized for compatibility.
- Added private profile details (birth date, gender, country/county, favorite category, relationship status, and child count) with a one-time 2,000 PT completion reward.
- Added profile results and creator-reward reporting, including answered questions, accuracy, weekly performance, per-quiz results, and earned creator PT.
- Added marketing e-mail preferences and documented that eligible campaign-page visits may award gift PT.
- Replaced the quiz category set with the approved canonical 20-category list while preserving existing quiz/question relationships.
- Fixed quiz creation/editing and CSV-import regressions, including the missing route, unsupported query/validation methods, and import execution.
- Allowed useradmins and hostadmins to create approved quizzes and add/import any number of questions without moderation, and kept their quiz starts free of entry cost.
- Implemented the shared gameplay-helper usage ledger: three lifetime free uses per helper, then 100 PT per use.
- Implemented and styled 50:50, Poker, Blackjack/21, audience vote, and KwizzGoBear helpers; Poker uses real hand ranking with an 80% player-win target, and Blackjack pauses the question timer and displays hand values.
- Changed answer presentation to a shuffled but per-question session-stable order so the correct answer is not consistently option A and helper labels remain stable after refreshes.
- Reworked dice-roll results so success/failure remains on the game screen until the player chooses the next action; added mode-specific next-question, cash-out, and return controls.
- Finished the Blackjack abandonment rule: leaving an unfinished hand now counts as a wrong answer and enters the normal dice-rescue flow; added a confirmed, responsive abandon action and regression coverage for abandonment and player-winning ties.
- Applied the dark purple/gold KwizzGo design to the profile, results, quiz catalog, quiz setup, gameplay/question screen, own-quiz listing, and per-quiz creator workspace.
- Extended the same responsive design system to quiz and question creation/editing, the notification center, login, and registration surfaces, including dedicated mobile and tablet breakpoints.
- Verified all Blade templates and the connected quiz/question, notification, password-authentication, and Google-authentication flows: 48 tests and 224 assertions passed.
- Added and ran the Google OAuth, username, canonical-category, private-profile, and helper-usage migrations after SQLite backups; database integrity is `ok` with zero foreign-key violations.
- Verified the connected suite after the feature work: 91 tests and 390 assertions passed; subsequent focused UI/gameplay tests also passed and compiled Blade PHP was linted.
- Completed the final helper follow-up for this workday and verified the complete suite successfully: 95 tests and 406 assertions passed.
- Committed every current project change, including `database/database.sqlite`, and pushed commit `398a8ab` to `origin/main`; confirmed that the local and remote commit hashes match and that pasted SMTP/Google secrets were not included in tracked text files.
- NOT DONE: purchasable helper packages and their shop surface were intentionally deferred.
- NOT DONE: advertising placements, the remaining large-selector autocomplete, and the expanded global question bank remain open.
- DESIGN DECISION: keep the current global question-bank, user-administration, and category-administration visuals as they are for now; further redesign is deferred by explicit user choice.
- NOT DONE: a focused pre-launch cross-browser and accessibility verification remains for the already redesigned Phase 1 surfaces.
- NEW FOLLOW-UP: restore dedicated registration feature coverage; `tests/Feature/Auth/RegistrationTest.php` is currently empty even though the registration view compiles successfully.
- SECURITY FOLLOW-UP: rotate the SMTP password and Google OAuth client secret that were pasted into chat, then update the environment configuration without committing secrets.
- TOMORROW: implement the advertising placements first, then deploy the complete KwizzGo system to the cPanel shared-hosting environment and perform the production smoke-test checklist.

### 2026-08-07

- Fixed the login flow so failed authentication and validation errors are visibly displayed on the login page instead of appearing as a no-progress reload.
- Added a dedicated inactive-account login message, accessible invalid-field states, session-regeneration coverage, logout coverage, and existing-session invalidation tests.
- Verified that the real primary hostadmin account is active and not banned; its password is not the insecure seeder default, and no automatic password overwrite was performed.
- Restored the complete password-reset flow and its previously empty/missing controllers and views:
  - reset-link request, token validation, new-password storage, remember-token invalidation, password confirmation, and authenticated password update;
  - restored the working "forgot password" link on the login page;
  - removed stale e-mail-verification routes and the ineffective `verified` middleware because `User` intentionally does not implement `MustVerifyEmail`.
- Added mandatory, editable admin moderation reasons for quiz rejection and approval/publication withdrawal:
  - selectable common reasons fill the editable final-reason field;
  - whitespace-only reasons are rejected;
  - final reasons are stored in `rejection_reason`, shown to the quiz owner, and cleared after reapproval or republication;
  - fixed the dashboard moderation forms' HTTP method mismatch.
- Built the database-backed in-app notification system:
  - navigation bell with unread counter;
  - paginated notification center with individual and mark-all-as-read actions;
  - ownership-safe notification access that prevents users from modifying another user's notification;
  - quiz approval, rejection, publication, and publication-withdrawal notifications, including the final admin reason and stable quiz link.
- Added per-user, per-event notification preferences on the profile page for internal and/or e-mail delivery:
  - quiz approval, rejection, publication, and withdrawal events are fully connected to the preferences;
  - weekly quiz performance report preferences are stored and ready for the future report sender;
  - defaults preserve internal notifications while keeping e-mail opt-in only;
  - moderation e-mails include the event, quiz, admin reason, and quiz link.
- Added and ran the `notifications` and `notification_preferences` migrations locally after timestamped SQLite backups.
- Verified the live SQLite database after migration: integrity is `ok` and there are zero foreign-key violations.
- Ran the connected feature suite successfully: 51 tests and 217 assertions passed; all Blade templates compiled successfully.
- NOT DONE: e-mail verification remains intentionally disabled; re-enabling it would require a separate product decision and a migration/onboarding plan for existing unverified accounts.
- NEW TASK: add admin-managed thematic competitions with configurable topics, time windows, participation/scoring rules, leaderboards, and rewards.
- NEW TASK: complete the final end-to-end design pass, responsive polish, component consistency, accessibility review, and replacement of remaining temporary or inline styling.
- PLANNING UPDATE: reorganized all remaining development into three phases: core quizzes/accounts/gameplay, content and expanded user/community features, then thematic house competitions.
- NEW PHASE 1 TASK: create responsive, admin-manageable advertising placements for Google AdSense and affiliate banners without disrupting gameplay.
- NEW PHASE 2 TASKS: add friendships, direct messaging, and clans/groups with the required privacy, moderation, membership, unread, and notification behavior.

### 2026-08-06

- Fixed the local `betquiz.test` environment by switching the Herd site from PHP 8.3 to PHP 8.4 and replacing the stale absolute SQLite path with a portable project-relative path.
- Completed hostadmin category management with create/edit, activation state, unique slugs, safe deletion protection, navigation access, and active-category filtering in quiz creation and the catalog.
- Added an admin-only user list with search, role/email/account-status filters, pagination, account statistics, points, created-quiz counts, and visible account states.
- Added separate banned and active/inactive user states plus the moderation permission matrix:
  - hostadmins can moderate players and useradmins and grant/revoke useradmin rights;
  - useradmins can ban/unban and activate/inactivate regular players only;
  - self-moderation and hostadmin moderation are blocked.
- Added inactive-account login prevention and middleware that logs out an already authenticated user after inactivation.
- Ran the new moderation migration locally without data loss; SQLite integrity is `ok`, there are zero foreign-key violations, and the restored counts remain 8 users, 17 quizzes, 1,214 questions, and 89 recorded answers.
- Ran the connected feature suite successfully: 27 tests and 104 assertions passed.
- NOT DONE: the real browser login issue remains reproducible from the user's perspective: submitting the form returns to the login page without visible progress. Database integrity, account active/ban state, and the login page itself were verified; continue this investigation first tomorrow.
- Confirmed the new project preference that the live `database/database.sqlite` file is committed and pushed with other changes despite the known risk that pulls can replace local sessions/data.

### 2026-07-30

- Pulled the latest `main` branch from GitHub.
- Diagnosed the login/logout incident: the tracked `database/database.sqlite` file was replaced by `git pull`, invalidating database sessions and replacing local users/game data.
- Restored the pre-pull 704 KB SQLite database from commit `c7fa570`, reran the newer migrations, and verified that 8 users, 17 quizzes, 1,214 questions, and 89 recorded answers returned.
- Added `database/*.sqlite` to `.gitignore` and removed the live SQLite database from Git tracking so a future pull cannot overwrite local data.
- Created timestamped database backups before each destructive schema migration.
- Fixed local image uploads by configuring a writable PHP `upload_tmp_dir`, aligning PHP/Laravel upload limits, creating the public storage link, and cleaning up duplicate stale `artisan serve` processes.
- Added detailed upload error reporting, selected-file information, size validation, instant previews, and saving-state feedback for quiz cover images and question/answer images.
- Added quiz-management search and filters for title, description, tags, category, status, and—when used by an admin—creator name/email.
- Added persistent admin card/table view switching to the quiz management screen.
- Added the admin quiz table with title, creator, approval state, visibility, question count, edit action, and a zero-point/statistics-free browser-based quiz preview.
- Added a separate `is_public` quiz field so approval (`pending`, `approved`, `rejected`) and public visibility are no longer conflated.
- Preserved all existing approved quizzes as public during migration and blocked unrelated users from opening private quizzes directly by URL.
- Added quiz bulk selection and operations for approval, rejection, public/private visibility, and ownership transfer.
- Added confirmation dialogs for quiz and question bulk actions, including the selected count and action details.
- Fixed the broken question editor caused by the missing `authorizeAccess()` method and unified question authorization through the owning quiz.
- Corrected the question editor domain model: questions belong to quizzes, not directly to categories or users; admins can move a question to another quiz.
- Removed the redundant `questions.creator_id` column and `Question::creator()` relationship. Question ownership now always follows `question -> quiz -> creator`, including after quiz ownership transfer.
- Restored full question editing for text, difficulty, correct answer, answer texts, question images, and answer images.
- Added per-quiz question bulk selection with select-all/clear controls, bulk difficulty changes, and admin-only bulk movement to another quiz.
- Added a debounced, server-side admin quiz autocomplete endpoint returning at most 20 matches for scalable bulk question movement.
- Updated legacy question creation/import paths and seed data so questions inherit their quiz relationship and legacy category value correctly.
- Ran the relevant feature suite successfully: 22 tests and 73 assertions passed after the bulk-management implementation.

### 2026-07-29

- Started the local dev environment with separate `php artisan serve` and `npm run dev` processes.
- Made the guest homepage use the dashboard experience without personal points/user data/admin-only areas.
- Added a guest auth prompt popup on quiz play buttons instead of sending guests directly to login.
- Fixed the quiz catalog filters: `category_id=all`, text search, sort order, and query-string pagination now work together.
- Added SEO-friendly quiz slugs generated from quiz titles and switched quiz URLs to use slugs while keeping numeric route binding fallback.
- Added editable admin-only quiz SEO fields: `seo_title` and `seo_description`; defaults come from title and the first 160 description characters.
- Added quiz tags with `tags` and `quiz_tag`, admin editing UI, existing-tag suggestions, and tag display on quiz cards/catalog.
- Added quiz aggregate answer stats from question totals: total answers and correct answers.
- Fixed quiz cover/header image persistence by adding `quizzes.cover_image`, storing uploaded files on the public disk, and rendering them on cards.
- Improved quiz catalog free-text search priority: title matches first, tag matches second, description matches last only when the search term is longer than 5 characters.
- Added an elegant clear-filters control to the quiz catalog when filters or non-default sorting are active.
- Added `Question::rebalanceDifficultyIfNeeded()` to adjust difficulty after at least 100 answers: success rate above 80 moves one level easier, below 20 moves one level harder, and resets current answer stats after a real level change.
- Hooked question difficulty rebalancing into both active answer-processing paths and added focused feature tests for threshold and boundary behavior.

### 2026-07-28

- Split quiz helper actions out of `QuizController` into `RollDiceController` and `TimeTravellerController`.
- Added shared quiz finish handling in `App\Http\Controllers\Concerns\FinishesQuizGames`.
- Fixed quiz catalog start links to route to `/quiz/setup/{quiz}`.
- Fixed normal-mode starts when fewer than 10 unanswered questions remain; Odds mode still requires at least 10.
- Added increasing quiz reset pricing with `user_quiz_resets`: 20 PT/question, then 40, 60, etc. per user and quiz.
- Added profile "Játékélmény" setting for the time-travel helper theme: Back to the Future or Harry Potter.
- Added themed time-travel helper screens while keeping the same gameplay rules and shared 3 free lifetime uses.
- Ensured time travel returns the exact same question after timeout by storing `current_question_id` in the game session.
- Fixed array-render errors for translated question and answer text in the game view.
- Restored creator rewards so quiz creators receive +1 PT when another user answers a question for the first time.
- Ran migrations locally and pushed changes to GitHub in commit `5992ff0`.
