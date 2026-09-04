<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RollDiceController;
use App\Http\Controllers\TimeTravellerController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\QuizHelperController;
use App\Http\Controllers\QuestionReportController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\GuestQuizTrialController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;

/*
|--------------------------------------------------------------------------
| Web Routes - BetQuiz
|--------------------------------------------------------------------------
*/

Route::get('/', [QuizController::class, 'dashboard']);
Route::get('/meghivo/{code}', [PageController::class, 'acceptReferral'])->name('referrals.accept');
Route::get('/aszf', [ContentPageController::class, 'short'])->defaults('slug', 'aszf')->name('content.aszf');
Route::get('/adatkezeles', [ContentPageController::class, 'short'])->defaults('slug', 'adatkezeles')->name('content.privacy');
Route::get('/mediaajanlat', [ContentPageController::class, 'short'])->defaults('slug', 'mediaajanlat')->name('content.media-kit');
Route::get('/llms.txt', [ContentPageController::class, 'llms'])->name('content.llms');
Route::get('/sitemap.xml', [ContentPageController::class, 'sitemap'])->name('content.sitemap');
// A megosztási URL szándékosan az auth csoporton kívül van, hogy a
// közösségi oldalak robotjai is közvetlenül a kvíz metaadatait kapják.
Route::get('/kviz/{quiz}', [QuizController::class, 'publicPreview'])->name('quizzes.share');
Route::post('/kviz/{quiz}/proba', [GuestQuizTrialController::class, 'start'])->name('quizzes.trial.start');
Route::get('/kviz/{quiz}/proba', [GuestQuizTrialController::class, 'show'])->name('quizzes.trial.show');
Route::post('/kviz/{quiz}/proba/valasz', [GuestQuizTrialController::class, 'answer'])->name('quizzes.trial.answer');
Route::get('/kviz/{quiz}/proba/eredmeny', [GuestQuizTrialController::class, 'result'])->name('quizzes.trial.result');
Route::get('/support-chat/state', [SupportChatController::class, 'state'])->name('support-chat.state');
Route::post('/support-chat/messages', [SupportChatController::class, 'store'])->name('support-chat.messages.store');
Route::post('/support-chat/reopen', [SupportChatController::class, 'reopen'])->name('support-chat.reopen');
Route::get('/support-chat/access/{token}', [SupportChatController::class, 'access'])->name('support-chat.access');
// A korábban megosztott setup URL-eket sem engedjük a loginoldal metaadataira
// esni: vendégnek publikus előnézet, játékosnak a megszokott setup jelenik meg.
Route::get('/quiz/setup/{quiz}', [QuizController::class, 'setupQuizEntry'])
    ->middleware('active')
    ->name('quiz.setup');
Route::get('/oldal/{content:slug}.md', [ContentPageController::class, 'markdown'])->name('content.markdown');
Route::get('/oldal/{content:slug}', [ContentPageController::class, 'show'])->name('content.show');
Route::get('/cikkek', [ContentPageController::class, 'articles'])->name('articles.index');
Route::get('/cikk/{content:slug}.md', [ContentPageController::class, 'markdown'])->name('articles.markdown');
Route::get('/cikk/{content:slug}', [ContentPageController::class, 'article'])->name('articles.show');

// Az e-mail-hitelesítés jelenleg nincs aktiválva a User modellen, ezért
// itt csak a valóban érvényes auth- és fiókállapot-feltételek szerepelnek.
Route::middleware(['auth', 'active', 'verified'])->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // ------------------------------------------------------------------------
    // 1. DASHBOARD
    // ------------------------------------------------------------------------
    Route::get('/dashboard', [QuizController::class, 'dashboard'])->name('dashboard');


    // ------------------------------------------------------------------------
    // 2. JÁTÉKOS FELÜLET (Katalógus & Játékmenet)
    // ------------------------------------------------------------------------
    Route::get('/quizzes', [QuizController::class, 'showBetForm'])->name('quizzes.index');

    Route::prefix('quiz')->name('quiz.')->group(function () {
        // Játék indítása (quiz.start_play)
        Route::post('/play/{quiz}', [QuizController::class, 'startPlay'])->name('start_play');

        // Játék képernyő (quiz.play.screen)
        Route::get('/play/{quiz}/screen', [QuizController::class, 'playScreen'])->name('play.screen');
        Route::post('/play/{quiz}/next', [QuizController::class, 'nextQuestion'])->name('next_question');

        // Válasz beküldése (quiz.submit_answer)
        Route::post('/play/{quiz}/answer', [QuizController::class, 'submitAnswer'])->name('submit_answer');
        Route::post('/play/{quiz}/questions/{question}/report', [QuestionReportController::class, 'store'])->name('questions.report');

        // Dobókocka mentőöv (quiz.roll_dice)
        Route::post('/play/{quiz}/roll-dice', [RollDiceController::class, 'rollDice'])->name('roll_dice');
        Route::post('/play/{quiz}/roll-dice/finish', [RollDiceController::class, 'finishDiceResult'])->name('roll_dice.finish');

        // Emmett Brown mentőöv (quiz.time_travel)
        Route::post('/play/{quiz}/time-travel', [TimeTravellerController::class, 'timeTravel'])->name('time_travel');

        // Kérdés közben használható KwizzGo-segítségek.
        Route::post('/play/{quiz}/helpers/blackjack', [QuizHelperController::class, 'startBlackjack'])->name('helpers.blackjack.start');
        Route::post('/play/{quiz}/helpers/blackjack/action', [QuizHelperController::class, 'blackjackAction'])->name('helpers.blackjack.action');
        Route::post('/play/{quiz}/helpers/blackjack/abandon', [QuizHelperController::class, 'abandonBlackjack'])->name('helpers.blackjack.abandon');
        Route::post('/play/{quiz}/helpers/resolve', [QuizHelperController::class, 'resolve'])->name('helpers.resolve');
        Route::post('/play/{quiz}/helpers/{helper}', [QuizHelperController::class, 'use'])->name('helpers.use');

        // Kiszállás (quiz.cashout)
        Route::post('/play/{quiz}/cashout', [QuizController::class, 'cashout'])->name('cashout');

        // 🟢 JAVÍTVA: Like, dislike, restart (A prefix miatt nem kell elé a /quiz/ és a name-be sem a quiz.)
        Route::post('/{quiz}/toggle-favorite', [QuizController::class, 'toggleFavorite'])->name('toggle-favorite');
        Route::post('/{quiz}/toggle-dislike', [QuizController::class, 'toggleDislike'])->name('toggle-dislike');
        Route::post('/{quiz}/reset-answers', [QuizController::class, 'resetQuizAnswers'])->name('reset-answers');
    });


    // ------------------------------------------------------------------------
    // 3. ALKOTÓI FELÜLET (Saját kvízek szerkesztése, létrehozása & CSV import)
    // ------------------------------------------------------------------------
    Route::resource('my-quizzes', QuizManagementController::class)
        ->names('my-quizzes')
        ->parameters(['my-quizzes' => 'quiz']);
    Route::get('/my-quizzes/{quiz}/preview', [QuizManagementController::class, 'preview'])
        ->name('my-quizzes.preview');

    Route::post('/my-quizzes/{quiz}/questions/import', [QuestionController::class, 'importForQuiz'])->name('my-quizzes.questions.import');
    Route::post('/my-quizzes/{quiz}/questions/store', [QuestionController::class, 'storeForQuiz'])->name('questions.storeForQuiz');
    Route::patch('/my-quizzes/{quiz}/questions/bulk', [QuestionController::class, 'bulkUpdate'])
        ->name('my-quizzes.questions.bulk-update');

    Route::resource('questions', QuestionController::class);
    Route::get('/question-reports', [QuestionReportController::class, 'index'])->name('question-reports.index');
    Route::patch('/question-reports/{questionReport}', [QuestionReportController::class, 'resolve'])->name('question-reports.resolve');


    // ------------------------------------------------------------------------
    // 4. PROFIL ÉS EGYÉB OLDALAK
    // ------------------------------------------------------------------------
    Route::get('/points', [PageController::class, 'points'])->name('pages.points');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/results', [ProfileController::class, 'results'])->name('results');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/game-experience', [ProfileController::class, 'updateGameExperience'])->name('game-experience');
        Route::patch('/private-details', [ProfileController::class, 'updatePrivateDetails'])->name('private-details');
        Route::patch('/notification-preferences', [NotificationPreferenceController::class, 'update'])
            ->name('notification-preferences');
        Route::post('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

    });


    // ------------------------------------------------------------------------
    // 5. ADMINISZTRÁCIÓ
    // ------------------------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/support-chat', [AdminSupportChatController::class, 'index'])->name('support-chat.index');
        Route::get('/support-chat/{conversation}', [AdminSupportChatController::class, 'show'])->name('support-chat.show');
        Route::post('/support-chat/{conversation}/reply', [AdminSupportChatController::class, 'reply'])->name('support-chat.reply');
        Route::patch('/support-chat/{conversation}/status', [AdminSupportChatController::class, 'status'])->name('support-chat.status');
        // A kategóriáknál csak a ténylegesen használt kezelőműveleteket tesszük elérhetővé.
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('settings', SettingController::class);
        Route::resource('advertisements', AdvertisementController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('/contents/upload-image', [AdminContentController::class, 'uploadImage'])->name('contents.upload-image');
        Route::resource('contents', AdminContentController::class)->except(['show']);
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/create', [EmailTemplateController::class, 'create'])->name('email-templates.create');
        Route::post('/email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
        Route::post('/email-templates/upload-image', [EmailTemplateController::class, 'uploadImage'])->name('email-templates.upload-image');
        Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::get('/email-templates/{emailTemplate}/deliveries', [EmailTemplateController::class, 'deliveries'])->name('email-templates.deliveries');
        Route::patch('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::post('/email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'sendTest'])->name('email-templates.test');

        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/email/verification', [UserController::class, 'sendVerificationEmail'])->name('users.email.verification');
        Route::post('/users/{user}/email/campaign', [UserController::class, 'sendCampaignEmail'])->name('users.email.campaign');
        Route::post('/users/{user}/email/custom', [UserController::class, 'sendCustomEmail'])->name('users.email.custom');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.status');
        Route::get('/quizzes/search', [QuizManagementController::class, 'search'])->name('quizzes.search');
        Route::post('/quizzes/{quiz}/transfer', [QuizManagementController::class, 'transferOwnership'])->name('quizzes.transfer');
        Route::patch('/quizzes/bulk', [QuizManagementController::class, 'bulkUpdate'])->name('quizzes.bulk-update');
    });

});

require __DIR__.'/auth.php';
