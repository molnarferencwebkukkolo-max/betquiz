@if($advertisement)
    <aside class="kwizzgo-ad-slot kwizzgo-ad-slot--{{ $position }}" aria-label="Hirdetés">
        <span class="kwizzgo-ad-label">Hirdetés</span>
        @if($advertisement->type === 'image')
            <a href="{{ $advertisement->target_url }}" target="_blank" rel="noopener noreferrer sponsored"
               class="kwizzgo-ad-link">
                <img src="{{ asset('storage/'.$advertisement->image_path) }}"
                     alt="{{ $advertisement->alt_text ?: $advertisement->name }}" loading="lazy">
            </a>
        @else
            <div class="kwizzgo-adsense-code">{!! $advertisement->adsense_code !!}</div>
        @endif
    </aside>
@endif
