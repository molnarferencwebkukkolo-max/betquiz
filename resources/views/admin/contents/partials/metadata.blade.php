<details class="content-panel" open><summary>SEO beállítások</summary><div class="content-metadata-grid">
    <label><span>SEO-cím</span><input name="seo_title" maxlength="255" value="{{ old('seo_title',$content->seo_title) }}"></label>
    <label><span>Meta leírás</span><textarea name="seo_description" maxlength="160" rows="3">{{ old('seo_description',$content->seo_description) }}</textarea></label>
    <label><span>Canonical URL</span><input type="url" name="canonical_url" value="{{ old('canonical_url',$content->canonical_url) }}"></label>
    <label><span>Strukturált adattípus</span><select name="schema_type">@foreach(['WebPage','Article','AboutPage','ContactPage'] as $schema)<option @selected(old('schema_type',$content->schema_type ?: 'WebPage')===$schema)>{{ $schema }}</option>@endforeach</select></label>
    <label><span>Sitemap prioritás</span><input type="number" name="sitemap_priority" min="0" max="1" step="0.1" value="{{ old('sitemap_priority',$content->sitemap_priority ?? 0.5) }}"></label>
    <div class="content-checks"><label><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index',$content->robots_index ?? true))> Indexelhető</label><label><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow',$content->robots_follow ?? true))> Linkek követhetők</label><label><input type="checkbox" name="sitemap_include" value="1" @checked(old('sitemap_include',$content->sitemap_include ?? true))> Sitemapben szerepel</label></div>
</div></details>
<details class="content-panel"><summary>Social megosztás</summary><div class="content-metadata-grid">
    <label><span>Social cím</span><input name="social_title" maxlength="255" value="{{ old('social_title',$content->social_title) }}"></label>
    <label><span>Social leírás</span><textarea name="social_description" maxlength="200" rows="3">{{ old('social_description',$content->social_description) }}</textarea></label>
    <label><span>Megosztási kép (1200×630 ajánlott)</span><input type="file" name="social_image" accept="image/jpeg,image/png,image/webp"></label>
    <label><span>X/Twitter kártya</span><select name="twitter_card"><option value="summary_large_image" @selected(old('twitter_card',$content->twitter_card ?: 'summary_large_image')==='summary_large_image')>Nagy kép</option><option value="summary" @selected(old('twitter_card',$content->twitter_card)==='summary')>Kis kép</option></select></label>
</div></details>
<details class="content-panel"><summary>LLM beállítások</summary><div class="content-metadata-grid">
    <label><span>LLM-összefoglaló</span><textarea name="llms_summary" maxlength="1000" rows="4">{{ old('llms_summary',$content->llms_summary) }}</textarea></label>
    <label><span>LLM-szekció</span><input name="llms_section" maxlength="100" value="{{ old('llms_section',$content->llms_section ?: 'Információk') }}"></label>
    <label><span>LLM-sorrend</span><input type="number" name="llms_priority" min="0" max="1000" value="{{ old('llms_priority',$content->llms_priority ?? 50) }}"></label>
    <div class="content-checks"><label><input type="checkbox" name="llms_include" value="1" @checked(old('llms_include',$content->llms_include))> Szerepeljen az llms.txt-ben</label><label><input type="checkbox" name="markdown_enabled" value="1" @checked(old('markdown_enabled',$content->markdown_enabled ?? true))> Markdown-változat</label></div>
</div></details>
<details class="content-panel"><summary>Láblécmenü</summary><div class="content-metadata-grid">
    <label><span>Menüfelirat</span><input name="footer_label" maxlength="100" value="{{ old('footer_label',$content->footer_label) }}"></label>
    <label><span>Láblécoszlop</span><input name="footer_group" maxlength="100" value="{{ old('footer_group',$content->footer_group ?: 'Információk') }}"></label>
    <label><span>Sorrend</span><input type="number" name="footer_order" min="0" max="1000" value="{{ old('footer_order',$content->footer_order ?? 100) }}"></label>
    <div class="content-checks"><label><input type="checkbox" name="footer_visible" value="1" @checked(old('footer_visible',$content->footer_visible))> Megjelenjen a láblécben</label></div>
</div></details>
