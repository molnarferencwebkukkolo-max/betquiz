<aside class="support-chat" data-support-chat data-state-url="{{ route('support-chat.state') }}" data-send-url="{{ route('support-chat.messages.store') }}" data-reopen-url="{{ route('support-chat.reopen') }}">
    <section class="support-chat-panel" data-chat-panel hidden aria-label="KwizzGo ügyfélszolgálati chat">
        <header><div><strong>KwizzGo segítség</strong><small>Írj nekünk, itt válaszolunk. Jelenlegi válaszadási időnk 3–12 óra, kérjük, légy türelemmel.</small></div><button type="button" data-chat-close aria-label="Chat bezárása">×</button></header>
        <div class="support-chat-messages" data-chat-messages role="log" aria-live="polite"></div>
        <div class="support-chat-closed" data-chat-closed hidden><strong>Ez a beszélgetés lezárult.</strong><span>Ha új kérdésed van, nyisd újra, és folytathatod ugyanitt.</span><button type="button" data-chat-reopen>Beszélgetés újranyitása</button></div>
        <form data-chat-form>
            <div class="support-chat-identity" data-chat-identity><input name="guest_name" maxlength="100" placeholder="Neved" autocomplete="name"><input type="email" name="guest_email" maxlength="255" placeholder="E-mail-címed" autocomplete="email"></div>
            <textarea name="body" maxlength="2000" rows="2" required placeholder="Írd ide az üzeneted…"></textarea>
            <div><small data-chat-status></small><button type="submit">Küldés</button></div>
        </form>
    </section>
    <button type="button" class="support-chat-button" data-chat-toggle aria-expanded="false" aria-label="KwizzGo chat megnyitása"><span aria-hidden="true">💬</span><i data-chat-badge hidden></i></button>
</aside>
<script>
(() => {
    const root = document.currentScript.previousElementSibling;
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';
    const panel = root.querySelector('[data-chat-panel]'), toggle = root.querySelector('[data-chat-toggle]'), close = root.querySelector('[data-chat-close]'), form = root.querySelector('[data-chat-form]'), messages = root.querySelector('[data-chat-messages]'), status = root.querySelector('[data-chat-status]'), identity = root.querySelector('[data-chat-identity]'), closedBox = root.querySelector('[data-chat-closed]'), reopen = root.querySelector('[data-chat-reopen]');
    let open = false;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const escapeHtml = value => { const node = document.createElement('div'); node.textContent = value; return node.innerHTML; };
    function render(data) {
        messages.innerHTML = '';
        const conversation = data.conversation;
        if (!conversation) { form.hidden = false; closedBox.hidden = true; messages.innerHTML = '<div class="support-chat-welcome"><strong>Szia! 👋</strong><p>Miben segíthetünk? Hagyd meg az üzeneted, és itt válaszolunk.</p></div>'; return; }
        messages.innerHTML = '<div class="support-chat-reference">' + escapeHtml(conversation.reference) + '</div>';
        const isClosed = conversation.status === 'closed'; form.hidden = isClosed; closedBox.hidden = !isClosed; identity.hidden = true;
        conversation.messages.forEach(message => { const item = document.createElement('div'); item.className = 'support-chat-message ' + (message.sender_type === 'admin' ? 'admin' : 'visitor'); item.innerHTML = '<p>' + escapeHtml(message.body) + '</p><small>' + escapeHtml(message.sent_at) + '</small>'; messages.appendChild(item); });
        messages.scrollTop = messages.scrollHeight;
    }
    async function load() { try { const response = await fetch(root.dataset.stateUrl, { headers: { Accept: 'application/json' } }); if (response.ok) render(await response.json()); } catch (_) { status.textContent = 'A chat pillanatnyilag nem frissíthető.'; } }
    function setOpen(value) { open = value; panel.hidden = !value; toggle.setAttribute('aria-expanded', String(value)); if (value) { load(); setTimeout(() => form.querySelector('textarea').focus(), 50); } }
    toggle.addEventListener('click', () => setOpen(!open)); close.addEventListener('click', () => setOpen(false));
    reopen.addEventListener('click', async () => { reopen.disabled = true; try { const response = await fetch(root.dataset.reopenUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } }); if (response.ok) render({ conversation: await response.json() }); } finally { reopen.disabled = false; } });
    form.addEventListener('submit', async event => { event.preventDefault(); status.textContent = 'Küldés…'; try { const response = await fetch(root.dataset.sendUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(Object.fromEntries(new FormData(form))) }); const data = await response.json(); if (!response.ok) { status.textContent = Object.values(data.errors || {}).flat()[0] || 'A küldés nem sikerült.'; return; } form.querySelector('textarea').value = ''; status.textContent = 'Elküldve'; render({ conversation: data }); } catch (_) { status.textContent = 'Hálózati hiba történt.'; } });
    if (location.hash === '#support-chat') setOpen(true); else load();
    setInterval(() => { if (open) load(); }, 10000);
})();
</script>
