<footer class="kwizzgo-site-footer"><div class="kwizzgo-footer-inner">
<div class="kwizzgo-footer-brand"><a href="{{ url('/') }}">Kwizz<span>Go</span></a><p>Tanulj. Játssz. Versenyezz.</p></div>
@foreach($groups as $group => $items)<nav aria-label="{{ $group }}"><strong>{{ $group }}</strong>@foreach($items as $item)<a href="{{ $item->publicUrl() }}">{{ $item->footer_label ?: $item->title }}</a>@endforeach</nav>@endforeach
<nav aria-label="KwizzGo"><strong>KwizzGo</strong><a href="{{ route('quizzes.index') }}">Kvízek</a><a href="{{ route('articles.index') }}">Cikkek</a><a href="{{ route('content.llms') }}">LLM információk</a></nav>
</div><div class="kwizzgo-footer-bottom">© {{ date('Y') }} KwizzGo</div></footer>
