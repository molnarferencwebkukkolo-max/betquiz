# Project Instructions

- When the user says "roffentsd be a localt", "roffentsd be a localhostot", or the same with Hungarian accents, start the local development environment with two separate commands:
  - `php artisan serve`
  - `npm run dev`
- Do not use `composer dev` for this project on Windows, because `php artisan pail` requires the `pcntl` extension and stops the combined script.
- Kiemelten figyelj a kommentelésre a kódban.
- Mindig vizsgáld meg, hogy egy eljárás még mihez kapcsolódik, hogy ne romoljon el a működő rész.

## Work Log

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
