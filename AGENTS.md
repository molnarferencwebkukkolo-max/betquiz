# Project Instructions

- When the user says "roffentsd be a localt", "roffentsd be a localhostot", or the same with Hungarian accents, start the local development environment with two separate commands:
  - `php artisan serve`
  - `npm run dev`
- Do not use `composer dev` for this project on Windows, because `php artisan pail` requires the `pcntl` extension and stops the combined script.
- Kiemelten figyelj a kommentelésre a kódban.
- Mindig vizsgáld meg, hogy egy eljárás még mihez kapcsolódik, hogy ne romoljon el a működő rész.

- When the user asks to update `AGENTS.md` at the end of the day, always review and confirm what is done, what is not done, what new items were added, and update the `Next Development Priorities` section accordingly.

## Next Development Priorities

- Fix the issue where the app logs the user out or does not allow login.
- Clean up admin question and quiz management views with table-based views and bulk editing.
- Extend user accounts with questionnaires/surveys that can award points.
- Add content/article sections connected to quizzes.

## Work Log

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
