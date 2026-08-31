<?php
/**
 * SEO management UI — CI4 port of CI3 admin/views/seo/index.php. Posts back to
 * admin/seo. $settings = merged SEO settings; $suggest = firm-profile suggestions.
 */
helper(['url', 'form']);
$s = $settings;
$b = isset($s['business']) ? $s['business'] : [];
$v = isset($s['verification']) ? $s['verification'] : [];
$a = isset($s['analytics']) ? $s['analytics'] : [];
$sc = isset($s['schema']) ? $s['schema'] : [];
$faqs = isset($s['faqs']) && is_array($s['faqs']) ? $s['faqs'] : [];
if (! function_exists('seo_v')) {
    function seo_v($arr, $k, $d = '') { return isset($arr[$k]) ? htmlspecialchars((string) $arr[$k], ENT_QUOTES) : $d; }
}
$suggest = $suggest ?? ['email' => '', 'address' => ''];
?>
<style>
  .seo-page { color: var(--tm-ink, #18243c); max-width: 1100px; margin: 0 auto; }
  .seo-hero { margin: 4px 0 18px; padding: 18px 20px; border: 1px solid var(--tm-line, #dce6f2); border-radius: 12px;
    background: linear-gradient(135deg, #fff, var(--tm-brand-soft, #eaf3ff)); box-shadow: 0 14px 34px rgba(24,36,60,.08); }
  .seo-hero h4 { margin: 0; font-size: 22px; font-weight: 900; }
  .seo-hero p { margin: 5px 0 0; color: var(--tm-muted, #718096); font-weight: 700; font-size: 13px; }
  .seo-card { margin-bottom: 18px; border: 1px solid var(--tm-line, #dce6f2); border-radius: 12px; background: #fff;
    box-shadow: 0 12px 30px rgba(24,36,60,.06); overflow: hidden; animation: seoIn .35s ease both; }
  @keyframes seoIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
  .seo-card > .h { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--tm-line, #dce6f2);
    background: linear-gradient(180deg, #fff, rgba(23,105,194,.04)); font-weight: 900; font-size: 15px; }
  .seo-card > .h i { color: var(--tm-brand, #1769c2); }
  .seo-card > .b { padding: 18px; }
  .seo-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
  .seo-grid.three { grid-template-columns: repeat(3, minmax(0,1fr)); }
  .seo-field.full { grid-column: 1 / -1; }
  .seo-page label { display:block; margin-bottom: 6px; font-size: 12px; font-weight: 900; letter-spacing: .03em; color: var(--tm-muted, #718096); }
  .seo-page input[type=text], .seo-page input[type=number], .seo-page select, .seo-page textarea {
    width: 100%; min-height: 42px; border: 1px solid var(--tm-line, #dce6f2); border-radius: 9px; padding: 9px 12px;
    background: #fbfdff; font-size: 14px; font-weight: 600; transition: border-color .18s, box-shadow .18s, background .18s; }
  .seo-page textarea { min-height: 80px; resize: vertical; }
  .seo-page input:focus, .seo-page select:focus, .seo-page textarea:focus {
    border-color: var(--tm-brand, #1769c2); background: #fff; box-shadow: 0 0 0 4px rgba(23,105,194,.12); outline: 0; }
  .seo-hint { margin-top: 5px; font-size: 11px; font-weight: 700; color: var(--tm-muted, #9aa6b6); }
  .seo-check { display:flex; align-items:center; gap:9px; font-weight:800; font-size:13px; padding:9px 11px; border:1px solid var(--tm-line,#dce6f2); border-radius:9px; background:#fbfdff; cursor:pointer; }
  .seo-check input { width:18px; height:18px; accent-color: var(--tm-brand,#1769c2); }
  .seo-actions { display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; margin-top: 6px; }
  .seo-actions .btn { min-height:44px; min-width:130px; border-radius:9px; font-weight:900; }
  .faq-row { display:grid; grid-template-columns: 1fr 1.4fr auto; gap:10px; margin-bottom:10px; align-items:start; }
  .faq-row .rm { min-height:42px; border:0; border-radius:9px; background:#fdecec; color:#e5484d; font-weight:900; cursor:pointer; padding:0 14px; }
  @media (max-width: 800px){ .seo-grid, .seo-grid.three { grid-template-columns: 1fr; } .faq-row { grid-template-columns: 1fr; } }
</style>

<main class="main-content bgc-grey-100">
  <div id="mainContent">
    <div class="container-fluid seo-page">
      <div class="seo-hero">
        <h4><i class="fa fa-search"></i> SEO &amp; Search Optimization</h4>
        <p>Manage meta tags, social previews, structured data, local business info, verification &amp; analytics. Settings apply to the public website.</p>
      </div>

      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <div style="margin-bottom:16px;">
        <a href="<?= base_url('sitemap.xml'); ?>" target="_blank" class="btn btn-light" style="font-weight:800;border:1px solid var(--tm-line,#dce6f2);border-radius:9px;padding:9px 14px;">View sitemap.xml</a>
        <a href="<?= base_url('robots.txt'); ?>" target="_blank" class="btn btn-light" style="font-weight:800;border:1px solid var(--tm-line,#dce6f2);border-radius:9px;padding:9px 14px;">View robots.txt</a>
        <a href="<?= base_url('admin/seo/generate'); ?>" class="btn btn-success" style="font-weight:800;border-radius:9px;padding:9px 14px;">Generate static files</a>
      </div>

      <?= form_open(base_url('admin/seo'), ['id' => 'seoForm']); ?>

      <div class="seo-card">
        <div class="h"><i class="fa fa-tags"></i> General Meta</div>
        <div class="b">
          <div class="seo-grid">
            <div class="seo-field"><label>Site Name</label><input type="text" name="site_name" value="<?= seo_v($s,'site_name'); ?>"></div>
            <div class="seo-field"><label>Title Suffix</label><input type="text" name="title_suffix" value="<?= seo_v($s,'title_suffix'); ?>"><div class="seo-hint">Appended to page titles, e.g. " | C R Industries"</div></div>
            <div class="seo-field full"><label>Default Meta Title</label><input type="text" name="default_title" value="<?= seo_v($s,'default_title'); ?>"></div>
            <div class="seo-field full"><label>Default Meta Description</label><textarea name="default_description"><?= seo_v($s,'default_description'); ?></textarea><div class="seo-hint">Aim for 150–160 characters.</div></div>
            <div class="seo-field full"><label>Meta Keywords (optional)</label><input type="text" name="default_keywords" value="<?= seo_v($s,'default_keywords'); ?>"></div>
            <div class="seo-field"><label>Extra Robots Directives</label><input type="text" name="robots_extra" value="<?= seo_v($s,'robots_extra'); ?>"></div>
            <div class="seo-field"><label>Allow Search Indexing?</label>
              <label class="seo-check"><input type="checkbox" name="indexable" value="1" <?= !empty($s['indexable']) ? 'checked' : ''; ?>> Site is indexable (uncheck for staging)</label>
            </div>
          </div>
        </div>
      </div>

      <div class="seo-card">
        <div class="h"><i class="fa fa-share-alt"></i> Social Sharing (Open Graph / Twitter)</div>
        <div class="b">
          <div class="seo-grid">
            <div class="seo-field full"><label>Default Share Image (OG/Twitter)</label><input type="text" name="og_image" value="<?= seo_v($s,'og_image'); ?>"><div class="seo-hint">Path under web root or full URL. Recommended 1200×630.</div></div>
            <div class="seo-field"><label>Twitter Card Type</label>
              <select name="twitter_card">
                <?php foreach (['summary_large_image','summary'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= (seo_v($s,'twitter_card')===$opt?'selected':''); ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="seo-field"><label>Twitter/X Handle</label><input type="text" name="twitter_handle" value="<?= seo_v($s,'twitter_handle'); ?>" placeholder="@yourbrand"></div>
          </div>
        </div>
      </div>

      <div class="seo-card">
        <div class="h"><i class="fa fa-map-marker"></i> Local Business (Schema)</div>
        <div class="b">
          <div class="seo-grid">
            <div class="seo-field"><label>Business Name</label><input type="text" name="business[name]" value="<?= seo_v($b,'name'); ?>"></div>
            <div class="seo-field"><label>Category</label><input type="text" name="business[category]" value="<?= seo_v($b,'category'); ?>"></div>
            <div class="seo-field"><label>Phone</label><input type="text" name="business[phone]" value="<?= seo_v($b,'phone'); ?>"></div>
            <div class="seo-field"><label>Email</label><input type="text" name="business[email]" value="<?= seo_v($b,'email', htmlspecialchars((string) $suggest['email'],ENT_QUOTES)); ?>"></div>
            <div class="seo-field full"><label>Street Address</label><input type="text" name="business[street]" value="<?= seo_v($b,'street', htmlspecialchars((string) $suggest['address'],ENT_QUOTES)); ?>"></div>
            <div class="seo-field"><label>Locality / City</label><input type="text" name="business[locality]" value="<?= seo_v($b,'locality'); ?>"></div>
            <div class="seo-field"><label>Region / State</label><input type="text" name="business[region]" value="<?= seo_v($b,'region'); ?>"></div>
            <div class="seo-field"><label>Postal Code</label><input type="text" name="business[postal]" value="<?= seo_v($b,'postal'); ?>"></div>
            <div class="seo-field"><label>Country Code</label><input type="text" name="business[country]" value="<?= seo_v($b,'country'); ?>"></div>
            <div class="seo-field"><label>Latitude</label><input type="text" name="business[latitude]" value="<?= seo_v($b,'latitude'); ?>"></div>
            <div class="seo-field"><label>Longitude</label><input type="text" name="business[longitude]" value="<?= seo_v($b,'longitude'); ?>"></div>
            <div class="seo-field full"><label>Google Maps URL</label><input type="text" name="business[map_url]" value="<?= seo_v($b,'map_url'); ?>"></div>
            <div class="seo-field"><label>Opening Hours</label><input type="text" name="business[opening_hours]" value="<?= seo_v($b,'opening_hours'); ?>" placeholder="Mo-Sa 09:00-18:00"></div>
            <div class="seo-field"><label>Price Range</label><input type="text" name="business[price_range]" value="<?= seo_v($b,'price_range'); ?>" placeholder="₹₹"></div>
            <div class="seo-field full"><label>Logo</label><input type="text" name="business[logo]" value="<?= seo_v($b,'logo'); ?>"></div>
          </div>
        </div>
      </div>

      <div class="seo-card">
        <div class="h"><i class="fa fa-check-circle"></i> Verification &amp; Analytics</div>
        <div class="b">
          <div class="seo-grid three">
            <div class="seo-field"><label>Google Search Console</label><input type="text" name="verification[google]" value="<?= seo_v($v,'google'); ?>"></div>
            <div class="seo-field"><label>Bing Webmaster</label><input type="text" name="verification[bing]" value="<?= seo_v($v,'bing'); ?>"></div>
            <div class="seo-field"><label>Yandex</label><input type="text" name="verification[yandex]" value="<?= seo_v($v,'yandex'); ?>"></div>
            <div class="seo-field"><label>Google Analytics (GA4 ID)</label><input type="text" name="analytics[ga4]" value="<?= seo_v($a,'ga4'); ?>" placeholder="G-XXXXXXX"></div>
            <div class="seo-field"><label>Google Tag Manager</label><input type="text" name="analytics[gtm]" value="<?= seo_v($a,'gtm'); ?>" placeholder="GTM-XXXXXX"></div>
            <div class="seo-field"><label>Microsoft Clarity</label><input type="text" name="analytics[clarity]" value="<?= seo_v($a,'clarity'); ?>"></div>
          </div>
        </div>
      </div>

      <div class="seo-card">
        <div class="h"><i class="fa fa-cubes"></i> Structured Data (JSON-LD)</div>
        <div class="b">
          <div class="seo-grid three">
            <?php
              $schema_opts = ['organization'=>'Organization','localbusiness'=>'Local Business / Rice Mill','website'=>'WebSite','breadcrumb'=>'Breadcrumb','faq'=>'FAQ'];
              foreach ($schema_opts as $k=>$lbl):
            ?>
              <label class="seo-check"><input type="checkbox" name="schema[<?= $k ?>]" value="1" <?= !empty($sc[$k]) ? 'checked':''; ?>> <?= $lbl ?></label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="seo-card">
        <div class="h"><i class="fa fa-question-circle"></i> FAQ (rich snippet)</div>
        <div class="b">
          <div id="faqWrap">
            <?php if (empty($faqs)) $faqs = [['q'=>'','a'=>'']]; foreach ($faqs as $f): ?>
              <div class="faq-row">
                <input type="text" name="faq_q[]" placeholder="Question" value="<?= htmlspecialchars((string)$f['q'],ENT_QUOTES); ?>">
                <input type="text" name="faq_a[]" placeholder="Answer" value="<?= htmlspecialchars((string)$f['a'],ENT_QUOTES); ?>">
                <button type="button" class="rm" onclick="this.closest('.faq-row').remove()">&times;</button>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-light" id="addFaq" style="font-weight:800;border:1px dashed var(--tm-brand,#1769c2);border-radius:9px;padding:9px 14px;color:var(--tm-brand,#1769c2);">+ Add FAQ</button>
        </div>
      </div>

      <div class="seo-actions">
        <a href="<?= base_url('admin/dashboard'); ?>"><button type="button" class="btn btn-light" style="border:1px solid var(--tm-line,#dce6f2);">Cancel</button></a>
        <button type="submit" class="btn btn-primary">Save SEO Settings</button>
      </div>

      <?= form_close(); ?>
    </div>
  </div>
</main>

<script>
  document.getElementById('addFaq').addEventListener('click', function () {
    var row = document.createElement('div');
    row.className = 'faq-row';
    row.innerHTML = '<input type="text" name="faq_q[]" placeholder="Question">' +
                    '<input type="text" name="faq_a[]" placeholder="Answer">' +
                    '<button type="button" class="rm">&times;</button>';
    row.querySelector('.rm').addEventListener('click', function(){ row.remove(); });
    document.getElementById('faqWrap').appendChild(row);
  });
</script>
