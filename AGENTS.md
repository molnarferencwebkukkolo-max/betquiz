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

- NEXT FIRST: deploy and verify the completed question-report continuation fix on production. Confirm that the `question_reports` migration has run, inspect the Laravel log for the earlier HTTP 500, clear the required production caches, and smoke-test `/quiz/play/{quiz}/questions/{question}/report` end to end on the live domain.
- NEXT FIRST: deploy and verify the completed notification e-mail expansion on production. Confirm the environment-only SMTP values, enable the intended per-event e-mail preferences for test recipients, send and receive real new-registration, quiz-request, quiz-approval, question-report, moderation, password-reset, and weekly-report messages, verify failure logging and the weekly cron, then keep all replacement credentials out of version control.
- NEXT FIRST: complete the production hardening and browser-level smoke test after the successful initial cPanel deployment:
  - verify emergency-hostadmin and normal login, registration with reCAPTCHA v3, Google OAuth, password reset, SMTP delivery, admin tools, content management, advertisements, uploads, quiz creation/import, gameplay, helpers, notifications, and responsive rendering on the live domain;
  - configure and verify the scheduler/cron for weekly reports;
  - verify HTTP-to-HTTPS redirection, required PHP extensions, writable Laravel directories, and the manually created shared-hosting storage symlink;
  - confirm removal of the one-time `public/kwizzgo-deploy.php` endpoint and any uploaded release archives that are no longer needed;
  - document and test the production database-backup and rollback procedure;
  - rotate every secret exposed in chat or diagnostic output, including SMTP, Google OAuth, reCAPTCHA, emergency-hostadmin, and FTP credentials, and keep replacements environment-only.
- Complete the `BetQuiz` -> `KwizzGo` brand migration end to end:
  - audit all remaining user-facing copy, e-mail content, metadata, assets, and configuration;
  - add or replace the final logo, favicon, social/SEO imagery, and branded e-mail styling.
- Finish the remaining gameplay-helper follow-ups:
  - add the deferred helper-package purchase surface and package rules;
  - complete browser-level balancing and interaction testing for Poker, Blackjack, 50:50, audience vote, and KwizzGoBear.
- Replace the remaining Phase 1 large static selectors with server-side autocomplete:
  - the admin quiz selector on the single-question edit screen;
  - the bulk quiz owner selector when the user list becomes large.
- Continue refining the global admin question bank with search, filters, pagination-safe selection, and any additional bulk operations needed beyond the per-quiz question bulk editor.
- Perform a focused cross-browser and accessibility verification of the already redesigned Phase 1 surfaces without restyling the intentionally retained admin screens.
- Restore general registration feature coverage in the currently empty `tests/Feature/Auth/RegistrationTest.php`; the new reCAPTCHA-specific registration protection already has dedicated coverage.

### Phase 2 - Expanded content and user features

- Extend the completed hostadmin content/page CMS with user-authored articles, moderation, author workflows, and links between articles and related quizzes.
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

### 2026-08-26

- DONE LOCALLY: added crawler-accessible, logged-out quiz sharing pages at stable slug URLs. Approved public quizzes now render their own document, description, canonical, robots, Open Graph, Twitter Card, image-alt, image-dimension/type, and Quiz JSON-LD metadata directly without an authentication redirect; private and unapproved quizzes return HTTP 404.
- DISCOVERY / FALLBACK: public quizzes are included in `sitemap.xml`, guest homepage quiz links lead to the public preview, authenticated visitors can continue to the existing setup screen, and quizzes without a usable cover receive a 1200x630 branded KwizzGo fallback image.
- VERIFICATION: added public/private visibility, guest/authenticated CTA, real cover metadata, fallback metadata, and sitemap regression coverage. The focused sharing suite passed with 5 tests and 22 assertions, the complete suite passed with 150 tests and 689 assertions, Blade compilation succeeded, and `git diff --check` reported no whitespace errors.
- NOT DONE / PRODUCTION: the deployed HTML and public image URLs still need validation against the live domain with Facebook Sharing Debugger and a second social-preview validator after deployment.
- DONE LOCALLY: activated preference-controlled e-mail delivery for new registrations, new quiz requests, admin quiz approvals, and faulty-question reports, alongside the existing quiz-moderation, password-reset, and weekly-report mail. Added branded subjects, contextual copy, and direct action links for every new administrative mail event.
- PREFERENCES: regular users and quiz creators see only applicable events, useradmins additionally see quiz-request settings, and hostadmins see registration, quiz-request, approval, and question-report settings. Database delivery remains enabled by default, while every e-mail channel remains explicit opt-in and can be disabled independently.
- HARDENING / VERIFICATION: confirmed that mail configuration is environment-only, the weekly command is scheduled for Monday 08:00 Europe/Budapest, and delivery exceptions are isolated and logged. The focused mail/preference suite passed with 16 tests and 90 assertions, the complete suite passed with 145 tests and 667 assertions, and Blade compilation succeeded.
- NOT DONE / PRODUCTION: no real external e-mail was sent during local automated verification. Production SMTP receipt, spam placement, failure logs, recipient preferences, and the hosting cron still require a controlled live-domain acceptance test after deployment and credential rotation.
- DONE: expanded the in-app bell notification system with role-specific administrative events. The product-level super admin maps to the project's highest `hostadmin` role, while the operational host admin maps to `useradmin`: active, non-banned hostadmins receive new-registration, new-quiz-request, quiz-approval, and faulty-question-report events; active, non-banned useradmins receive new-quiz-request and faulty-question-report events. Quiz owners continue to receive their applicable moderation/report notifications.
- HARDENING: registration notifications use the single auto-discovered Laravel `Registered` listener and therefore cover both password and Google registration; repeated quiz approvals no longer duplicate owner or super-admin notifications; recipient queries are unique and notification delivery failures are isolated and logged.
- VERIFICATION: added role, inactive-recipient, event-payload, and approval-deduplication coverage; the focused notification/reporting/OAuth/quiz-management suite passed with 37 tests and 183 assertions, the complete suite passed with 141 tests and 644 assertions, Blade compilation succeeded, and the event map contains exactly one application registration listener.
- DONE: rebuilt the shared navigation as a complete responsive desktop/tablet/mobile system. Added an off-canvas mobile drawer, hamburger and close controls, overlay closing, body-scroll locking, Escape handling, keyboard focus trapping/restoration, inactive-panel `inert` handling, reduced-motion support, compact tablet layout, active states, and guest/player/useradmin/hostadmin-specific links and account actions.
- VERIFICATION: added responsive-navigation regression coverage for guest, regular-user, useradmin, and hostadmin output; the focused navigation/notification suite passed with 7 tests and 45 assertions, the complete suite passed with 138 tests and 627 assertions, Blade compilation succeeded, and the Vite production build completed successfully.
- DONE: reordered the active gameplay question screen to the required user-facing hierarchy: `question -> answer options -> submit answer -> helpers -> report question`. The order is defined in the DOM and therefore remains consistent across desktop, tablet, and mobile; the collapsible report control is the final, visually secondary action.
- VERIFICATION: added a regression assertion for the complete control order; the focused gameplay/reporting suite passed with 14 tests and 62 assertions, the complete suite passed with 135 tests and 600 assertions, and Blade compilation succeeded.
- DONE LOCALLY: repaired the question-report submission continuation flow. A successful report now enters a dedicated decision screen, preserves the active game, skips the inactive question without counting it as answered, awards no points, creates no `user_answers` record, and leaves question answer statistics unchanged.
- HARDENING: a moderator-notification delivery failure is now logged per recipient and can no longer turn an already persisted player report into an HTTP 500 response.
- VERIFICATION: the focused reporting/gameplay suite passed with 13 tests and 60 assertions; the complete suite passed with 134 tests and 598 assertions; Blade compilation succeeded; the local `question_reports` migration is present in batch 29.
- NOT DONE / PRODUCTION: the earlier live HTTP 500 cannot be considered closed until the production Laravel log and migration status are checked, the updated files are deployed, production caches are cleared, and a real live-domain report is submitted successfully.
- DONE: fixed zero-valued question and answer text handling across creation, CSV import, editing, admin preview, and gameplay. The string/number `0` is preserved as valid text and no longer falls back to `Képes válasz`; added regression coverage for every affected path.
- VERIFICATION: the focused question-management, CSV-import, preview, and gameplay suite passed with 40 tests and 149 assertions; the complete suite passed with 133 tests and 583 assertions; Blade compilation succeeded.
- DONE: reviewed and cleaned the Phase 1 backlog so it contains only unfinished or partially finished work; removed completed deployment state and standing design/technical-identifier decisions from the task list.
- NOT DONE: no application code, database schema, production configuration, or tests were changed as part of this documentation-only update.
- NEW / NEXT FIRST: perform a complete responsive-navigation review and fully repair the mobile menu across supported roles, screen sizes, and interaction modes.
- NEW / NEXT FIRST: extend bell notifications so super admins receive new-registration, new-quiz-request, quiz-approval, and faulty-question-report events, while host admins receive new-quiz-request and faulty-question-report events; reconcile these product labels with the project's concrete authorization roles before wiring recipients.
- NEW / NEXT FIRST: activate production notification e-mails and verify event coverage, recipient preferences, SMTP delivery, failure handling, and secret-safe configuration.

### 2026-08-25

- Updated the homepage hero statistics to show the real counts for available public quizzes, questions waiting to be played, and active players.
- Changed the homepage application preview to display three randomly selected quizzes from the ten most popular approved public quizzes.
- Completed the gameplay question-error reporting and moderation workflow:
  - players can report the current question with a required detailed reason;
  - reported questions are immediately inactivated and skipped without counting as a correct or wrong answer;
  - the quiz owner and every active useradmin and hostadmin receive an internal notification linking to the report queue;
  - quiz owners, useradmins, and hostadmins can resolve reports as accepted-and-fixed or not genuine (`FAKE`);
  - an accepted report can only be closed after the question has actually been edited;
  - questions with multiple pending reports remain inactive until the final pending report is resolved;
  - after at least three resolved reports, players whose `FAKE` rate exceeds 30% are prevented from submitting further reports;
  - gameplay selection, setup counts, answer submission, and the alternate game service now consistently exclude inactive questions.
- Added and ran the `question_reports` migration locally after creating an SQLite backup; `database/database.sqlite` contains the new schema.
- Prepared a safe incremental production hotfix archive containing the changed application files and migration, without `.env`, secrets, tests, the local SQLite database, or a deployment endpoint.
- Verification completed: the full suite passed with 129 tests and 566 assertions, Blade compilation succeeded, SQLite integrity is `ok`, and foreign-key checks reported zero violations.
- DONE: homepage statistic labels/counts, randomized popular hero quizzes, question reporting, moderation notifications, inactive-question enforcement, FAKE-rate restriction, local migration, tests, and the uploadable hotfix package.
- NOT DONE / TOMORROW FIRST: values containing exactly `0` are currently treated as empty in at least one question/answer rendering path and appear as `Képes válasz`. Treat string and numeric zero as valid content everywhere and add regression tests before the next production package.
- NOT DONE / TOMORROW FIRST: the first live-domain question-report submission returned HTTP 500. After a successful report, show the normal correct-answer-style continuation/decision screen and preserve the current game, but grant no points and write no answer or question statistics; verify the live `question_reports` migration and inspect the production Laravel error log as part of the diagnosis.
- NOT DONE / TOMORROW FIRST: the gameplay controls are not currently ordered in a user-friendly way. Rebuild the visual order as question, answers, answer submission, helpers, then error reporting; verify the same hierarchy at every responsive breakpoint.
- NEW FOLLOW-UP: public quiz links currently do not produce a polished logged-out social preview. Add a crawler-safe public quiz landing/preview response with quiz-specific title, meta/OG/Twitter description, absolute cover-image URL, canonical URL, image dimensions/type where available, and branded fallbacks; verify the final HTML with Facebook Sharing Debugger and another social-preview validator without requiring authentication.
- DEPLOYMENT STATUS: the 2026-08-25 question-report route is present on the live domain and Coming Soon mode has been disabled, but the first report submission failed with HTTP 500; production migration/cache status and the complete hotfix acceptance test remain unconfirmed.

### 2026-08-14

- Completed the responsive, database-backed advertising system:
  - added hostadmin management for image/link advertisements and trusted Google AdSense code;
  - added weighted random rotation, active date ranges, placement activation, and shared Blade rendering;
  - added top-horizontal, content-horizontal, right-sidebar, and gameplay decision-square placements;
  - ensured all placements are hidden for users marked as ad-free, with hostadmin-only control of that user flag.
- Generated, optimized, stored, and seeded eight project-local demo creatives: two for each of the four current advertising positions; the demo seeder is idempotent.
- Added the square advertisement beside the correct-answer decision panel, with responsive stacking below the panel on narrower screens.
- Completed the initial content management system under the `Tartalomkezelő` navigation group:
  - hostadmin-managed pages and articles with publication state, scheduling, revisions, sanitized WYSIWYG HTML, and editor image uploads;
  - SEO metadata, canonical settings, social sharing metadata/image, and LLMS title/summary/include settings;
  - public HTML and Markdown content endpoints, `llms.txt`, `sitemap.xml`, and configurable footer-menu inclusion;
  - seeded the ÁSZF, Adatkezelési szabályzat, and Médiaajánlat page records for admin editing.
- Added an environment-controlled Coming Soon mode through `COMING_SOON_MODE`:
  - returns a branded responsive HTTP 503 page for guests and regular users;
  - preserves login, password-access routes, health checks, and full hostadmin access.
- Added Google reCAPTCHA v3 protection to e-mail login and registration:
  - environment-controlled enablement, keys, and minimum score;
  - invisible per-form tokens with separate `login` and `register` actions;
  - server-side success, action, and score verification through Google's `siteverify` endpoint with timeout and fail-closed error handling.
- Added a secure environment-backed emergency hostadmin that works without a pre-existing database user and is restored as a protected hostadmin after the production schema exists.
- Removed self-service role switching from user settings and kept role changes under the established hostadmin moderation rules.
- Standardized connected public content and quiz-facing copy on the `KwizzGo` brand while preserving technical project identifiers.
- Reworked fresh production database bootstrap behavior so migrations create the schema, canonical categories, initial managed pages, and advertising placements without creating a known-password administrator, demo quiz, orphan questions, or demo advertisements.
- Made the locked Composer dependency set compatible with the hosting account's PHP 8.3 runtime and generated a Linux-safe production release containing optimized dependencies and frontend assets but no SQLite database or tests.
- Completed the initial cPanel deployment to MySQL with Laravel's `public` directory as document root, production caches, database migrations, and a manual storage symlink because the host disables PHP `exec()`.
- Hardened production packaging so the local Vite `public/hot` marker can never redirect live visitors to their own localhost development server.
- Fixed Windows local serving for the accented project path by using the existing ASCII `Q:` drive mapping; the Laravel and Vite development servers are running separately as required.
- Verification completed during the work:
  - the full suite passed at the end of the CMS/advertising implementation with 107 tests and 464 assertions;
  - later focused advertising tests passed with 5 tests and 18 assertions;
  - Coming Soon tests passed with 5 tests and 8 assertions;
  - the final complete suite passed with 126 tests and 554 assertions after the PHP 8.3 and reCAPTCHA v3 corrections;
  - Blade compilation succeeded, SQLite integrity is `ok`, and foreign-key checks reported zero violations.
- DONE: the advertising system, initial hostadmin content/page CMS, Coming Soon mode, reCAPTCHA v3, emergency hostadmin, clean production bootstrap, PHP 8.3 release build, and initial cPanel deployment are complete.
- DEPLOYMENT STATUS: the application schema and caches are live on cPanel/MySQL, but the complete real-browser production smoke test and rollback checklist remain the immediate next milestone.
- SECURITY FOLLOW-UP: rotate SMTP, Google OAuth, reCAPTCHA, emergency-hostadmin, and FTP secrets exposed in chat or diagnostic output; keep replacements environment-only.
- RELEASE FOLLOW-UP: verify deletion of the one-time web deployment endpoint and unneeded uploaded archives, configure cron, confirm HTTP-to-HTTPS redirection, and disable Coming Soon only after production acceptance.
- NEW FOLLOW-UP: extend the completed CMS in Phase 2 with user-authored articles, moderation/author workflows, and quiz-to-article relationships.

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
