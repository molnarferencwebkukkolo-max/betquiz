# Project Instructions

- When the user says "roffentsd be a localt", "roffentsd be a localhostot", or the same with Hungarian accents, start the local development environment with two separate commands:
  - `php artisan serve`
  - `npm run dev`
- Do not use `composer dev` for this project on Windows, because `php artisan pail` requires the `pcntl` extension and stops the combined script.
- Kiemelten figyelj a kommentelésre a kódban.
- Mindig vizsgáld meg, hogy egy eljárás még mihez kapcsolódik, hogy ne romoljon el a működő rész.
- When the user asks to update `AGENTS.md` at the end of the day, always review and confirm what is done, what is not done, what new items were added, and update the `Next Development Priorities` section accordingly.
- MINDIG a main ágra tesszük a gitre, de erre kérdezz rá!

## Next Development Priorities

- Require an admin reason when rejecting a quiz or withdrawing its approval/publication:
  - provide a selectable list of common fixed reasons;
  - selecting a reason should fill an editable free-text field;
  - store the final edited reason and show it to the quiz owner.
- Build an in-app notification system with a notification bell and read/unread state.
- Add per-user notification preferences so users can choose internal notifications/messages and/or email for events such as:
  - quiz approval, rejection, withdrawal, or other moderation changes;
  - weekly quiz performance reports;
  - future account, survey, content, and gameplay events.
- Extend user accounts with questionnaires/surveys that can award points.
- Add content/article sections connected to quizzes, including admin authoring and links between articles and related quizzes.
- Replace remaining large static selectors with server-side autocomplete:
  - the admin quiz selector on the single-question edit screen;
  - the bulk quiz owner selector when the user list becomes large.
- Continue refining the global admin question bank with search, filters, pagination-safe selection, and any additional bulk operations needed beyond the per-quiz question bulk editor.

## Work Log

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
