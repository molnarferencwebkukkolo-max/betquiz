<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Hamarosan indulunk – KwizzGo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 50% 15%,#352071 0,#11122d 38%,#07091b 100%);color:#f8fafc;font-family:ui-sans-serif,system-ui,sans-serif}.soon-card{width:min(720px,100%);padding:clamp(32px,7vw,72px);text-align:center;border:1px solid rgba(250,204,21,.28);border-radius:32px;background:rgba(10,12,34,.88);box-shadow:0 30px 90px rgba(0,0,0,.45);backdrop-filter:blur(16px)}.soon-mark{display:grid;place-items:center;width:84px;height:84px;margin:0 auto 26px;border-radius:24px;background:linear-gradient(145deg,#7c3aed,#14b8a6);font-size:42px;box-shadow:0 18px 45px rgba(124,58,237,.35)}.soon-kicker{margin:0 0 12px;color:#facc15;font-weight:900;letter-spacing:.18em;text-transform:uppercase}.soon-card h1{margin:0;font-size:clamp(42px,9vw,78px);line-height:.95;font-weight:950;letter-spacing:-.055em}.soon-card h1 span{color:#2dd4bf}.soon-lead{max-width:540px;margin:26px auto 0;color:#cbd5e1;font-size:clamp(17px,3vw,21px);line-height:1.65}.soon-status{display:inline-flex;align-items:center;gap:10px;margin-top:30px;padding:11px 18px;border:1px solid rgba(45,212,191,.25);border-radius:999px;background:rgba(20,184,166,.1);color:#99f6e4;font-weight:800}.soon-dot{width:9px;height:9px;border-radius:50%;background:#2dd4bf;box-shadow:0 0 18px #2dd4bf}.soon-login{display:block;width:max-content;margin:28px auto 0;color:#8b93ad;font-size:13px;text-decoration:none}.soon-login:hover{color:#facc15}
    </style>
</head>
<body>
<main class="soon-card">
    <div class="soon-mark" aria-hidden="true">?</div>
    <p class="soon-kicker">Valami izgalmas készül</p>
    <h1>A KwizzGo<br><span>hamarosan indul!</span></h1>
    <p class="soon-lead">Dolgozunk az utolsó kérdéseken és finomhangoljuk a játékélményt. Hamarosan találkozunk a kvízarénában!</p>
    <div class="soon-status"><span class="soon-dot"></span> Az előkészületek folyamatban</div>
    <a class="soon-login" href="{{ route('login') }}">Adminisztrátori belépés</a>
</main>
</body>
</html>
