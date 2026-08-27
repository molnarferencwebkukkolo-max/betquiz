{{-- A verziózott betöltés az update után is azonnal friss CSS-t kényszerít ki,
     akkor is, ha a tárhely vagy a mobilböngésző a régi app-custom.css-t cache-elte. --}}
<link rel="stylesheet" href="{{ asset('css/app-custom.css') }}?v={{ filemtime(public_path('css/app-custom.css')) }}">
<div class="cookie-consent" data-cookie-consent data-analytics-id="{{ config('analytics.measurement_id') }}" hidden>
    <div class="cookie-consent-backdrop"></div>
    <section class="cookie-consent-dialog" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title">
        <span class="cookie-consent-eyebrow">Adatvédelmi beállítások</span>
        <h2 id="cookie-consent-title">Sütik a KwizzGo oldalán</h2>
        <p>A működéshez szükséges sütik mindig aktívak. Az opcionális sütiket csak a hozzájárulásod után használjuk. A választásodat később bármikor módosíthatod.</p>
        <details>
            <summary>Beállítások megtekintése</summary>
            <label><span><strong>Szükséges sütik</strong><small>Bejelentkezés, biztonság és alapvető működés.</small></span><input type="checkbox" checked disabled></label>
            <label><span><strong>Opcionális sütik</strong><small>Statisztikai és kényelmi funkciók, ha később bekapcsoljuk őket.</small></span><input type="checkbox" data-cookie-optional></label>
        </details>
        <p class="cookie-consent-links"><a href="{{ route('content.privacy') }}">Adatkezelési szabályzat</a></p>
        <div class="cookie-consent-actions"><button type="button" data-cookie-necessary>Csak szükséges</button><button type="button" data-cookie-save>Beállítás mentése</button><button type="button" data-cookie-all>Összes elfogadása</button></div>
    </section>
</div>
<script>
    (() => {
        const root = document.currentScript.previousElementSibling;
        if (!root || window.__kwizzgoCookieConsentReady) return;
        window.__kwizzgoCookieConsentReady = true;
        const optional = root.querySelector('[data-cookie-optional]');
        const read = () => document.cookie.split('; ').find(row => row.startsWith('kwizzgo_cookie_consent='))?.split('=')[1];
        const analyticsId = root.dataset.analyticsId;
        let analyticsLoaded = false;

        // Consent Mode alapállapot: az analitikai tárolás mindaddig tiltott,
        // amíg a látogató kifejezetten nem engedélyezi az opcionális sütiket.
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            wait_for_update: 500,
        });

        const enableAnalytics = () => {
            if (!analyticsId) return;
            window.gtag('consent', 'update', { analytics_storage: 'granted' });
            if (analyticsLoaded || document.querySelector('script[data-kwizzgo-analytics]')) return;
            analyticsLoaded = true;
            const script = document.createElement('script');
            script.async = true;
            script.dataset.kwizzgoAnalytics = 'true';
            script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(analyticsId)}`;
            document.head.appendChild(script);
            window.gtag('js', new Date());
            window.gtag('config', analyticsId, { anonymize_ip: true });
        };

        const disableAnalytics = () => {
            window.gtag('consent', 'update', { analytics_storage: 'denied' });
            // Visszavonáskor a KwizzGo domainen létrehozott GA-sütiket is töröljük.
            document.cookie.split(';').forEach(cookie => {
                const name = cookie.split('=')[0].trim();
                if (name === '_ga' || name.startsWith('_ga_')) {
                    document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
                    document.cookie = `${name}=; Max-Age=0; Path=/; Domain=.${location.hostname}; SameSite=Lax`;
                }
            });
        };

        const save = value => {
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `kwizzgo_cookie_consent=${value}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
            value === 'all' ? enableAnalytics() : disableAnalytics();
            root.hidden = true;
            document.dispatchEvent(new CustomEvent('kwizzgo:cookie-consent', { detail: { value } }));
        };
        const open = () => {
            optional.checked = read() === 'all';
            root.hidden = false;
            requestAnimationFrame(() => root.querySelector('[data-cookie-necessary]')?.focus());
        };

        root.querySelector('[data-cookie-necessary]').addEventListener('click', () => save('necessary'));
        root.querySelector('[data-cookie-save]').addEventListener('click', () => save(optional.checked ? 'all' : 'necessary'));
        root.querySelector('[data-cookie-all]').addEventListener('click', () => save('all'));
        document.addEventListener('click', event => {
            if (event.target.closest('[data-cookie-settings]')) {
                event.preventDefault();
                open();
            }
        });
        if (!read()) {
            open();
        } else if (read() === 'all') {
            enableAnalytics();
        }
    })();
</script>
