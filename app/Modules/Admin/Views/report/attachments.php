<?php
defined('BASEPATH') or exit('No direct script access allowed');
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$g   = function ($k) { return isset($_GET[$k]) ? htmlspecialchars($_GET[$k], ENT_QUOTES, 'UTF-8') : ''; };
$sel = function ($k, $v) { return (isset($_GET[$k]) && $_GET[$k] == $v) ? 'selected' : ''; };
$rows = isset($rows) ? $rows : array();
$storage = isset($storage) ? $storage : array('total_bytes' => 0, 'img_bytes' => 0, 'voice_bytes' => 0, 'video_bytes' => 0, 'img_files' => 0, 'voice_files' => 0, 'video_files' => 0, 'present' => 0, 'missing' => 0);

// Human-readable byte formatter.
$fmtBytes = function ($b) {
    $b = (float) $b;
    if ($b <= 0) { return '0 B'; }
    $u = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = (int) floor(log($b, 1024));
    if ($i >= count($u)) { $i = count($u) - 1; }
    $val = $b / pow(1024, $i);
    return ($i === 0 ? number_format($val) : number_format($val, ($val < 10 ? 2 : 1))) . ' ' . $u[$i];
};

// Tallies for the summary strip.
$n_img = 0; $n_voice = 0; $n_video = 0;
foreach ($rows as $r) {
    if (!empty($r->image_path))      { $n_img++; }
    if (!empty($r->voice_note_path)) { $n_voice++; }
    if (!empty($r->video_note_path)) { $n_video++; }
}
?>
<style>
    /* Wrapped in the theme's .main-content so it clears the fixed header +
       the absolute "Advanced features" note (responsive top padding). */
    .ag-scope { color: #18243c; }
    .ag-shell { max-width: 1500px; margin: 0 auto; }
    .ag-hero { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        padding: 20px 24px; margin-bottom: 16px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 90% -20%, rgba(120,170,255,.5), transparent 38%), linear-gradient(125deg, #12325b, #1a3f6b 55%, #3b1e6e);
        box-shadow: 0 18px 40px rgba(16,32,72,.26); }
    .ag-hero-l { display: flex; align-items: center; gap: 14px; }
    .ag-hero-ic { width: 50px; height: 50px; border-radius: 13px; display: grid; place-items: center; font-size: 21px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); }
    .ag-title { margin: 0; font-size: 22px; font-weight: 900; }
    .ag-title small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .ag-kpis { display: flex; gap: 8px; flex-wrap: wrap; }
    .ag-kpi { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); border-radius: 10px; padding: 7px 13px; text-align: center; min-width: 78px; }
    .ag-kpi b { display: block; font-size: 18px; font-weight: 900; } .ag-kpi span { font-size: 10px; text-transform: uppercase; letter-spacing: .3px; opacity: .85; }
    .ag-kpi-size { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.4); min-width: 108px; }
    .ag-kpi-size b { display: inline-flex; align-items: center; }

    /* Storage breakdown strip */
    .ag-storage { padding: 14px 18px; }
    .ag-sto-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
    .ag-sto-head b { color: #0f172a; font-size: 14px; } .ag-sto-head i { color: #1a3f6b; }
    .ag-sto-head small { color: #7a8aa0; font-weight: 700; font-size: 11px; }
    .ag-sto-miss { color: #b42318; font-weight: 800; font-size: 12px; background: #fef2f2; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 8px; }
    .ag-sto-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
    .ag-sto-cell { display: flex; align-items: center; gap: 11px; border: 1px solid #e3e9f2; border-radius: 12px; padding: 12px 14px; background: #fbfdff; }
    .ag-sto-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 17px; flex: none; }
    .ag-sto-cell.img .ag-sto-ic { background: linear-gradient(135deg,#1f9d70,#0c7048); }
    .ag-sto-cell.aud .ag-sto-ic { background: linear-gradient(135deg,#2563eb,#1746a2); }
    .ag-sto-cell.vid .ag-sto-ic { background: linear-gradient(135deg,#7c3aed,#55208f); }
    .ag-sto-cell.tot .ag-sto-ic { background: linear-gradient(135deg,#12325b,#1a3f6b); }
    .ag-sto-cell span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #7a8aa0; }
    .ag-sto-cell b { display: block; font-size: 18px; font-weight: 900; color: #18243c; margin-top: 1px; }
    .ag-sto-cell em { font-style: normal; font-size: 11px; font-weight: 700; color: #94a3b8; }
    .ag-sto-cell.tot { background: #f5f8ff; border-color: #dbe4f3; }
    @media (max-width: 900px) { .ag-sto-grid { grid-template-columns: repeat(2,1fr); } }

    .ag-panel { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); padding: 16px 18px; margin-bottom: 16px; }
    .ag-filter { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)) auto; gap: 12px; align-items: end; }
    .ag-field label { display: block; font-size: 11px; font-weight: 900; text-transform: uppercase; color: #718096; margin-bottom: 5px; }
    .ag-field .form-control { min-height: 40px; border: 1px solid #dce6f2; border-radius: 9px; background: #fbfdff; font-weight: 700; }
    .ag-btns { display: flex; gap: 8px; }
    .ag-btn { min-height: 40px; border: 0; border-radius: 9px; font-weight: 800; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .ag-btn-go { background: #1a3f6b; color: #fff; } .ag-btn-go:hover { background: #143257; }
    .ag-btn-rs { background: #f1f5f9; color: #475569; border: 1px solid #dce6f2; } .ag-btn-rs:hover { background: #e6edf6; }

    .ag-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(215px, 1fr)); gap: 15px; }
    .ag-card { border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; overflow: hidden; box-shadow: 0 8px 22px rgba(24,36,60,.06); transition: transform .15s ease, box-shadow .15s ease; }
    .ag-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(24,36,60,.12); }
    .ag-media { position: relative; height: 158px; background: #eef2f7; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .ag-media img { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; display: block; }
    .ag-media video { width: 100%; height: 100%; object-fit: cover; background: #000; }
    .ag-noimg { color: #7c8aa0; font-size: 12px; font-weight: 700; text-align: center; }
    .ag-noimg i { display: block; font-size: 30px; margin-bottom: 6px; color: #aeb9cb; }
    .ag-types { position: absolute; top: 8px; left: 8px; display: flex; gap: 5px; }
    .ag-chip { font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; color: #fff; display: inline-flex; align-items: center; gap: 3px; }
    .ag-chip.img { background: #16a34a; } .ag-chip.aud { background: #2563eb; } .ag-chip.vid { background: #7c3aed; }
    .ag-zoom { position: absolute; bottom: 8px; right: 8px; background: rgba(15,23,42,.7); color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 20px; }
    .ag-body { padding: 10px 12px; }
    .ag-idrow { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .ag-id { font-weight: 900; color: #1d4ed8; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #dbe4f3; background: #f5f8ff; padding: 2px 8px; border-radius: 7px; }
    .ag-id:hover { background: #e6efff; }
    .ag-amt { font-weight: 900; font-size: 13px; } .ag-amt.dep { color: #127a34; } .ag-amt.exp { color: #b42318; }
    .ag-party { font-weight: 800; color: #0f172a; font-size: 13px; margin-top: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ag-meta { font-size: 11px; color: #7a8aa0; margin-top: 3px; display: flex; gap: 8px; flex-wrap: wrap; }
    .ag-audio { padding: 8px 12px 0; } .ag-audio audio { width: 100%; height: 34px; }
    .ag-empty { text-align: center; padding: 50px 20px; color: #7a8aa0; }
    .ag-empty i { font-size: 42px; color: #c3ccd9; display: block; margin-bottom: 10px; }

    /* Lightbox */
    .ag-lb { position: fixed; inset: 0; background: rgba(6,12,24,.92); z-index: 100050; display: none; align-items: center; justify-content: center; padding: 30px; }
    .ag-lb.show { display: flex; }
    .ag-lb img { max-width: 94vw; max-height: 86vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,.6); }
    .ag-lb-close { position: absolute; top: 18px; right: 24px; color: #fff; font-size: 30px; cursor: pointer; background: none; border: 0; }
    .ag-lb-cap { position: absolute; bottom: 22px; left: 0; right: 0; text-align: center; color: #cbd5e1; font-size: 13px; }
    .ag-lb-cap a { color: #93c5fd; font-weight: 800; }

    @media (max-width: 1000px) { .ag-filter { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="main-content ag-scope">
    <div class="ag-shell">
        <div class="ag-hero">
            <div class="ag-hero-l">
                <div class="ag-hero-ic"><i class="ti-gallery"></i></div>
                <h1 class="ag-title">Attachments Gallery
                    <small>All photos, voice &amp; video attached to cash-book entries</small>
                </h1>
            </div>
            <div class="ag-kpis">
                <div class="ag-kpi"><b><?= count($rows) ?></b><span>Entries</span></div>
                <div class="ag-kpi"><b><?= $n_img ?></b><span>Photos</span></div>
                <div class="ag-kpi"><b><?= $n_voice ?></b><span>Voice</span></div>
                <div class="ag-kpi"><b><?= $n_video ?></b><span>Video</span></div>
                <div class="ag-kpi ag-kpi-size" title="Total on-disk size of all attachment files for this firm"><b><i class="ti-harddrives" style="font-size:14px;margin-right:4px;"></i><?= $esc($fmtBytes($storage['total_bytes'])) ?></b><span>Total Size</span></div>
            </div>
        </div>

        <?php $storTotalFiles = (int) $storage['img_files'] + (int) $storage['voice_files'] + (int) $storage['video_files']; ?>
        <div class="ag-panel ag-storage">
            <div class="ag-sto-head">
                <div><i class="ti-server"></i> <b>Attachment Storage</b> <small>whole firm &middot; <?= number_format($storTotalFiles) ?> files</small></div>
                <?php if ((int) $storage['missing'] > 0): ?>
                    <span class="ag-sto-miss"><i class="ti-alert"></i> <?= number_format((int) $storage['missing']) ?> file(s) missing on disk</span>
                <?php endif; ?>
            </div>
            <div class="ag-sto-grid">
                <div class="ag-sto-cell img">
                    <div class="ag-sto-ic"><i class="ti-camera"></i></div>
                    <div><span>Photos</span><b><?= $esc($fmtBytes($storage['img_bytes'])) ?></b><em><?= number_format((int) $storage['img_files']) ?> files</em></div>
                </div>
                <div class="ag-sto-cell aud">
                    <div class="ag-sto-ic"><i class="ti-microphone"></i></div>
                    <div><span>Voice</span><b><?= $esc($fmtBytes($storage['voice_bytes'])) ?></b><em><?= number_format((int) $storage['voice_files']) ?> files</em></div>
                </div>
                <div class="ag-sto-cell vid">
                    <div class="ag-sto-ic"><i class="ti-video-camera"></i></div>
                    <div><span>Video</span><b><?= $esc($fmtBytes($storage['video_bytes'])) ?></b><em><?= number_format((int) $storage['video_files']) ?> files</em></div>
                </div>
                <div class="ag-sto-cell tot">
                    <div class="ag-sto-ic"><i class="ti-harddrives"></i></div>
                    <div><span>Total</span><b><?= $esc($fmtBytes($storage['total_bytes'])) ?></b><em><?= number_format((int) $storage['present']) ?> on disk</em></div>
                </div>
            </div>
        </div>

        <div class="ag-panel">
            <form method="get" class="ag-filter">
                <div class="ag-field"><label>From Date</label><input type="text" name="from_date" class="form-control ag-from" value="<?= $g('from_date') ?>" placeholder="dd-mm-yyyy" autocomplete="off"></div>
                <div class="ag-field"><label>To Date</label><input type="text" name="to_date" class="form-control ag-to" value="<?= $g('to_date') ?>" placeholder="dd-mm-yyyy" autocomplete="off"></div>
                <div class="ag-field"><label>Media Type</label>
                    <select name="mtype" class="form-control">
                        <option value="">All types</option>
                        <option value="image" <?= $sel('mtype', 'image') ?>>Photos only</option>
                        <option value="voice" <?= $sel('mtype', 'voice') ?>>Voice only</option>
                        <option value="video" <?= $sel('mtype', 'video') ?>>Video only</option>
                    </select>
                </div>
                <div class="ag-field"><label>Search (party / #id / remark)</label><input type="text" name="q" class="form-control" value="<?= $g('q') ?>" placeholder="e.g. Sonu or 40738"></div>
                <div class="ag-btns">
                    <button type="submit" class="ag-btn ag-btn-go"><i class="ti-search"></i> Filter</button>
                    <a href="<?= base_url('admin/attachments') ?>" class="ag-btn ag-btn-rs"><i class="ti-reload"></i> Reset</a>
                </div>
            </form>
        </div>

        <?php if (empty($rows)): ?>
            <div class="ag-panel ag-empty"><i class="ti-image"></i>No attachments found for this filter.</div>
        <?php else: ?>
            <div class="ag-grid">
                <?php foreach ($rows as $r):
                    $img   = !empty($r->image_path) ? base_url($r->image_path) : '';
                    $voice = !empty($r->voice_note_path) ? base_url($r->voice_note_path) : '';
                    $video = !empty($r->video_note_path) ? base_url($r->video_note_path) : '';
                    $isDep = ($r->type_of_account === 'deposit');
                    $party = preg_replace('/_(\d+)$/', '', (string) $r->account_name);
                    $viewUrl = base_url('admin/account/edit/' . ID_encode((int) $r->rokad_id));
                    $isWeb = (strtolower(trim((string) $r->entry_source)) === 'web' || trim((string) $r->entry_source) === '');
                ?>
                <div class="ag-card">
                    <div class="ag-media">
                        <div class="ag-types">
                            <?php if ($img): ?><span class="ag-chip img"><i class="ti-camera"></i></span><?php endif; ?>
                            <?php if ($voice): ?><span class="ag-chip aud"><i class="ti-microphone"></i></span><?php endif; ?>
                            <?php if ($video): ?><span class="ag-chip vid"><i class="ti-video-camera"></i></span><?php endif; ?>
                        </div>
                        <?php if ($img): ?>
                            <img src="<?= $esc($img) ?>" alt="attachment #<?= (int) $r->rokad_id ?>" loading="lazy"
                                 onerror="agImgFail(this)"
                                 onclick="agZoom('<?= $esc($img) ?>', '#<?= (int) $r->rokad_id ?> — <?= $esc(addslashes($party)) ?>', '<?= $esc($viewUrl) ?>')">
                            <span class="ag-zoom"><i class="ti-zoom-in"></i> Click to zoom</span>
                        <?php elseif ($video): ?>
                            <video src="<?= $esc($video) ?>" controls preload="none"></video>
                        <?php else: ?>
                            <div class="ag-noimg"><i class="ti-microphone"></i>Voice note</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($voice): ?><div class="ag-audio"><audio controls preload="none" src="<?= $esc($voice) ?>"></audio></div><?php endif; ?>

                    <div class="ag-body">
                        <div class="ag-idrow">
                            <a class="ag-id" href="<?= $esc($viewUrl) ?>" title="Open full entry"><i class="ti-eye"></i> #<?= (int) $r->rokad_id ?></a>
                            <span class="ag-amt <?= $isDep ? 'dep' : 'exp' ?>">&#8377; <?= number_format((float) $r->karch_amount, 2) ?></span>
                        </div>
                        <div class="ag-party" title="<?= $esc($party) ?>"><?= $esc($party) ?></div>
                        <div class="ag-meta">
                            <span><i class="ti-calendar"></i> <?= !empty($r->rokad_date) ? date('d-m-Y', strtotime($r->rokad_date)) : '-' ?></span>
                            <span><?= $isDep ? 'जमा' : 'नाम' ?></span>
                            <span><i class="<?= $isWeb ? 'ti-desktop' : 'ti-mobile' ?>"></i> <?= $isWeb ? 'Web' : 'App' ?></span>
                        </div>
                        <?php
                            $addedBy = trim((string) $r->added_by_name) !== '' ? $r->added_by_name : ($r->added_by ? '#' . $r->added_by : 'Unknown');
                            $addedOn = !empty($r->added_type) ? date('d M Y, h:i A', strtotime($r->added_type)) : '';
                            $modOn   = (!empty($r->last_modified) && $r->last_modified !== '0000-00-00 00:00:00') ? date('d M Y, h:i A', strtotime($r->last_modified)) : '';
                            $modBy   = isset($r->modified_by) ? trim((string) $r->modified_by) : '';
                            $modCnt  = isset($r->modified_count) ? (int) $r->modified_count : 0;
                        ?>
                        <div class="ag-audit">
                            <div class="ag-au-row"><i class="ti-plus"></i> Added by <b><?= $esc($addedBy) ?></b><?= $addedOn ? ' &middot; <span>' . $esc($addedOn) . '</span>' : '' ?></div>
                            <?php if ($modOn): ?>
                                <div class="ag-au-row ag-mod"><i class="ti-pencil"></i> Modified<?= $modBy !== '' ? ' by <b>' . $esc($modBy) . '</b>' : '' ?> &middot; <span><?= $esc($modOn) ?></span><?php if ($modCnt > 1): ?> <em>(<?= $modCnt ?>&times;)</em><?php endif; ?></div>
                            <?php else: ?>
                                <div class="ag-au-row ag-nomod"><i class="ti-check"></i> Not modified</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($rows) >= 500): ?>
                <div class="ag-panel" style="text-align:center;color:#7a8aa0;font-size:12px;">Showing the 500 most recent — narrow the date range to see older attachments.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Lightbox -->
<div class="ag-lb" id="agLb" onclick="agCloseLb(event)">
    <button type="button" class="ag-lb-close" onclick="agCloseLb(event, true)">&times;</button>
    <img id="agLbImg" src="" alt="zoom">
    <div class="ag-lb-cap" id="agLbCap"></div>
</div>

<script>
    $(function () {
        var fy = "<?php echo fy()->FY; ?>".split('-');
        try {
            $('.ag-from, .ag-to').datepicker({ dateFormat: 'dd-mm-yy' });
        } catch (e) {}
    });
    // Broken/slow image → replace the dark box with a clean placeholder.
    function agImgFail(img) {
        var m = img.parentNode; if (!m) return;
        img.remove();
        var z = m.querySelector('.ag-zoom'); if (z) z.remove();
        if (!m.querySelector('.ag-noimg')) {
            var d = document.createElement('div');
            d.className = 'ag-noimg';
            d.innerHTML = '<i class="ti-image"></i>Image unavailable';
            m.appendChild(d);
        }
    }
    function agZoom(src, cap, url) {
        document.getElementById('agLbImg').src = src;
        document.getElementById('agLbCap').innerHTML = (cap ? cap.replace(/</g,'&lt;') : '') + (url ? ' &nbsp; <a href="' + url + '">Open full entry &rarr;</a>' : '');
        document.getElementById('agLb').classList.add('show');
    }
    function agCloseLb(e, force) {
        if (force || e.target.id === 'agLb') { document.getElementById('agLb').classList.remove('show'); document.getElementById('agLbImg').src = ''; }
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { document.getElementById('agLb').classList.remove('show'); } });
</script>
