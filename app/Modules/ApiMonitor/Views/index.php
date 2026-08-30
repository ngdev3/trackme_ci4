<?php
/** Sliced content — rendered inside app/Views/layout.php. Super-Admin only. */
helper('text');

/** Small helper: render a params bucket as chips. */
$chips = static function (array $items, string $cls): string {
    $html = '';
    foreach ($items as $it) {
        $html .= '<span class="apim-chip ' . $cls . '">' . esc($it) . '</span> ';
    }
    return $html;
};

$healthMeta = [
    'online'  => ['Online',    'success', 'bi-check-circle-fill'],
    'missing' => ['Not Found', 'warning', 'bi-question-circle-fill'],
    'error'   => ['Error',     'danger',  'bi-exclamation-octagon-fill'],
    'down'    => ['Down',      'danger',  'bi-x-circle-fill'],
];
?>

<div class="cust-page">
<section class="cust-hero">
    <div>
        <h4 class="cust-title">API Monitor</h4>
        <p class="cust-subtitle">Mobile-app API endpoints — reachability, auth, and the live on/off kill-switch.</p>
    </div>
</section>

<!-- Summary -->
<div class="row g-3 mb-3" id="apimSummary">
    <div class="col-6 col-lg"><div class="apim-stat"><div class="apim-stat-num" data-k="total"><?= (int) $summary['total'] ?></div><div class="apim-stat-lbl">Total Endpoints</div></div></div>
    <div class="col-6 col-lg"><div class="apim-stat is-green"><div class="apim-stat-num" data-k="active"><?= (int) $summary['active'] ?></div><div class="apim-stat-lbl">Active</div></div></div>
    <div class="col-6 col-lg"><div class="apim-stat is-gray"><div class="apim-stat-num" data-k="inactive"><?= (int) $summary['inactive'] ?></div><div class="apim-stat-lbl">Inactive</div></div></div>
    <div class="col-6 col-lg"><div class="apim-stat is-green"><div class="apim-stat-num" data-k="online"><?= (int) $summary['online'] ?></div><div class="apim-stat-lbl">Reachable</div></div></div>
    <div class="col-6 col-lg"><div class="apim-stat is-red"><div class="apim-stat-num" data-k="down"><?= (int) $summary['down'] ?></div><div class="apim-stat-lbl">Unreachable</div></div></div>
</div>

<div class="card erp-panel">
    <div class="card-header erp-panel-title d-flex flex-wrap gap-2 align-items-center">
        <span class="panel-icon"><i class="bi bi-phone"></i></span>
        <h3 class="card-title mb-0 me-auto">Mobile App APIs <small class="text-secondary fw-normal">(<?= esc(rtrim($baseUrl, '/')) ?>)</small></h3>
        <div class="input-group input-group-sm" style="width:230px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="apimSearch" placeholder="Filter path / handler…">
        </div>
        <select class="form-select form-select-sm" id="apimHealthFilter" style="width:150px">
            <option value="">All statuses</option>
            <option value="online">Reachable</option>
            <option value="missing">Not Found</option>
            <option value="error">Error</option>
            <option value="down">Down</option>
            <option value="unknown">Unchecked</option>
        </select>
        <button class="btn btn-sm btn-outline-secondary" id="apimSync" title="Re-scan routes"><i class="bi bi-arrow-repeat me-1"></i>Sync</button>
        <button class="btn btn-sm btn-primary" id="apimCheckAll"><i class="bi bi-broadcast me-1"></i>Check all</button>
    </div>
    <div class="card-body p-0">
        <div class="apim-note px-3 py-2">
            <i class="bi bi-shield-lock me-1"></i>
            Super Admin only. Health checks are sent <b>without</b> a token — secured endpoints answer <code>401</code> (that still means <b>reachable</b>). Turning an endpoint <b>off</b> makes the live app receive a <code>503</code>.
        </div>

        <?php foreach ($groups as $grp => $rows): ?>
            <div class="apim-group" data-group="<?= esc($grp) ?>">
                <div class="apim-group-head">
                    <i class="bi bi-folder2-open me-1"></i><?= esc(ucfirst($grp)) ?>
                    <span class="apim-group-count"><?= count($rows) ?></span>
                </div>
                <div class="table-responsive">
                <table class="table apim-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px">Method</th>
                            <th>Endpoint &amp; Parameters</th>
                            <th style="width:90px">Auth</th>
                            <th style="width:190px">Status</th>
                            <th style="width:150px" class="text-end">Active / Check</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r):
                        $p = json_decode($r['params'] ?? '[]', true) ?: [];
                        $hm = $healthMeta[$r['health']] ?? ['Unchecked', 'secondary', 'bi-dash-circle'];
                        $mcls = ['GET' => 'get', 'POST' => 'post', 'PUT' => 'put', 'DELETE' => 'del', 'PATCH' => 'put'][$r['http_method']] ?? 'get';
                    ?>
                        <tr class="apim-row"
                            data-id="<?= (int) $r['id'] ?>"
                            data-health="<?= esc($r['health'] ?: 'unknown') ?>"
                            data-search="<?= esc(strtolower($r['http_method'] . ' ' . $r['path'] . ' ' . $r['handler'])) ?>">
                            <td><span class="apim-method m-<?= $mcls ?>"><?= esc($r['http_method']) ?></span></td>
                            <td>
                                <div class="apim-path"><code>api/v1/<?= esc($r['path']) ?></code></div>
                                <div class="apim-handler text-secondary small"><?= esc($r['handler']) ?></div>
                                <div class="apim-params mt-1">
                                    <?php if (! empty($p['path'])): ?><span class="apim-plabel">path:</span> <?= $chips($p['path'], 'c-path') ?><?php endif; ?>
                                    <?php if (! empty($p['query'])): ?><span class="apim-plabel">query:</span> <?= $chips($p['query'], 'c-query') ?><?php endif; ?>
                                    <?php if (! empty($p['body'])): ?><span class="apim-plabel">body:</span> <?= $chips($p['body'], 'c-body') ?><?php endif; ?>
                                    <?php if (empty($p['path']) && empty($p['query']) && empty($p['body'])): ?><span class="text-secondary small">— none —</span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($r['auth'] === 'public'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Public</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-key-fill"></i> Bearer</span>
                                <?php endif; ?>
                            </td>
                            <td class="apim-status">
                                <span class="apim-health badge bg-<?= $hm[1] ?>-subtle text-<?= $hm[1] ?>-emphasis">
                                    <i class="bi <?= $hm[2] ?>"></i> <span class="apim-health-label"><?= $hm[0] ?></span>
                                </span>
                                <div class="apim-meta small text-secondary mt-1">
                                    <span class="apim-code"><?= $r['http_status'] !== null ? 'HTTP ' . (int) $r['http_status'] : '' ?></span>
                                    <span class="apim-ms"><?= $r['response_ms'] !== null ? ' · ' . (int) $r['response_ms'] . 'ms' : '' ?></span>
                                    <div class="apim-checked"><?= $r['last_checked'] ? esc(date('d M, H:i', strtotime($r['last_checked']))) : 'never checked' ?></div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="form-check form-switch d-inline-block me-1 align-middle">
                                    <input class="form-check-input apim-toggle" type="checkbox" role="switch" <?= (int) $r['is_active'] === 1 ? 'checked' : '' ?> title="Active / Inactive">
                                </div>
                                <button class="btn btn-sm btn-outline-primary apim-check" title="Ping this endpoint"><i class="bi bi-activity"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($groups)): ?>
            <div class="text-center text-secondary py-5">
                No endpoints registered yet. Click <b>Sync</b> to scan the routes.
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<style nonce="{csp-style-nonce}">
.apim-stat{background:#fff;border:1px solid var(--bs-border-color,#e5e7eb);border-radius:14px;padding:14px 16px;height:100%;box-shadow:0 1px 2px rgba(0,0,0,.03)}
.apim-stat-num{font-size:1.6rem;font-weight:700;line-height:1}
.apim-stat-lbl{font-size:.78rem;color:#6b7280;margin-top:4px}
.apim-stat.is-green .apim-stat-num{color:#16a34a}.apim-stat.is-red .apim-stat-num{color:#dc2626}.apim-stat.is-gray .apim-stat-num{color:#6b7280}
.apim-note{background:#f8fafc;border-bottom:1px solid var(--bs-border-color,#e5e7eb);font-size:.82rem;color:#475569}
.apim-group-head{display:flex;align-items:center;gap:6px;padding:10px 16px;font-weight:700;text-transform:capitalize;background:#f1f5f9;border-top:1px solid #e5e7eb;color:#334155;font-size:.9rem}
.apim-group-count{margin-left:6px;background:#e2e8f0;color:#475569;border-radius:999px;padding:1px 9px;font-size:.72rem;font-weight:600}
.apim-table{font-size:.86rem}
.apim-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:#94a3b8;border-bottom:1px solid #e5e7eb;font-weight:600}
.apim-table td{border-bottom:1px dashed #eef2f7;vertical-align:top;padding-top:.7rem;padding-bottom:.7rem}
.apim-method{display:inline-block;font-weight:700;font-size:.68rem;padding:3px 7px;border-radius:6px;letter-spacing:.02em}
.apim-method.m-get{background:#dcfce7;color:#15803d}.apim-method.m-post{background:#dbeafe;color:#1d4ed8}
.apim-method.m-put{background:#fef3c7;color:#b45309}.apim-method.m-del{background:#fee2e2;color:#b91c1c}
.apim-path code{font-size:.86rem;color:#0f172a;background:transparent;padding:0}
.apim-handler{font-size:.74rem}
.apim-chip{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 6px;font-size:.72rem;margin:2px 2px 0 0;color:#475569}
.apim-chip.c-path{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.apim-chip.c-query{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9}
.apim-chip.c-body{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.apim-plabel{font-size:.72rem;color:#94a3b8;font-weight:600}
.apim-row.is-hidden{display:none}
.apim-row.is-checking{opacity:.55}
.apim-health .spinner-border{width:.8rem;height:.8rem}
</style>

<script nonce="{csp-script-nonce}">
(function () {
    var BASE = window.APP_BASE_URL || '';
    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrf = meta ? meta.getAttribute('content') : '';
    function refreshCsrf(d){ if(d&&d.csrf){csrf=d.csrf; if(meta) meta.setAttribute('content',d.csrf);} }
    function post(url){
        return fetch(BASE + url, {method:'POST', credentials:'same-origin', headers:{'X-CSRF-TOKEN':csrf}})
            .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
            .then(function(res){ refreshCsrf(res.d); return res; });
    }
    var HEALTH = {
        online:{l:'Online',c:'success',i:'bi-check-circle-fill'},
        missing:{l:'Not Found',c:'warning',i:'bi-question-circle-fill'},
        error:{l:'Error',c:'danger',i:'bi-exclamation-octagon-fill'},
        down:{l:'Down',c:'danger',i:'bi-x-circle-fill'},
        unknown:{l:'Unchecked',c:'secondary',i:'bi-dash-circle'}
    };

    function paintRow(row, e){
        row.setAttribute('data-health', e.health || 'unknown');
        var h = HEALTH[e.health] || HEALTH.unknown;
        var badge = row.querySelector('.apim-health');
        badge.className = 'apim-health badge bg-'+h.c+'-subtle text-'+h.c+'-emphasis';
        badge.innerHTML = '<i class="bi '+h.i+'"></i> <span class="apim-health-label">'+h.l+'</span>';
        row.querySelector('.apim-code').textContent = e.http_status!=null ? 'HTTP '+e.http_status : '';
        row.querySelector('.apim-ms').textContent   = e.response_ms!=null ? ' · '+e.response_ms+'ms' : '';
        row.querySelector('.apim-checked').textContent = e.last_checked || 'never checked';
        row.classList.remove('is-checking');
    }
    function spin(row){
        row.classList.add('is-checking');
        var badge = row.querySelector('.apim-health');
        badge.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking…';
    }
    function rowById(id){ return document.querySelector('.apim-row[data-id="'+id+'"]'); }

    // Per-row health check
    document.querySelectorAll('.apim-check').forEach(function(btn){
        btn.addEventListener('click', function(){
            var row = btn.closest('.apim-row'); spin(row);
            post('/api-monitor/check/' + row.getAttribute('data-id')).then(function(res){
                if(res.ok && res.d.status==='success'){ paintRow(row, res.d.endpoint); recount(); }
                else { row.classList.remove('is-checking'); erpNotify && erpNotify('error', (res.d&&res.d.message)||'Check failed.'); }
            }).catch(function(){ row.classList.remove('is-checking'); erpNotify&&erpNotify('error','Network error.'); });
        });
    });

    // Check all
    document.getElementById('apimCheckAll').addEventListener('click', function(){
        var btn=this, old=btn.innerHTML;
        btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Checking…';
        document.querySelectorAll('.apim-row').forEach(spin);
        post('/api-monitor/check-all').then(function(res){
            if(res.ok && res.d.status==='success'){
                res.d.endpoints.forEach(function(e){ var row=rowById(e.id); if(row) paintRow(row,e); });
                recount();
                erpNotify && erpNotify('success', 'Checked '+res.d.endpoints.length+' endpoints.');
            } else { erpNotify && erpNotify('error','Check failed.'); }
        }).catch(function(){ erpNotify&&erpNotify('error','Network error.'); })
          .finally(function(){ btn.disabled=false; btn.innerHTML=old; });
    });

    // Sync
    document.getElementById('apimSync').addEventListener('click', function(){
        var btn=this, old=btn.innerHTML; btn.disabled=true;
        btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Syncing…';
        post('/api-monitor/sync').then(function(res){
            if(res.ok && res.d.status==='success'){
                var r=res.d.result;
                erpNotify && erpNotify('success','Synced: '+r.added+' new, '+r.updated+' updated. Reloading…');
                setTimeout(function(){ location.reload(); }, 700);
            } else { btn.disabled=false; btn.innerHTML=old; erpNotify&&erpNotify('error','Sync failed.'); }
        }).catch(function(){ btn.disabled=false; btn.innerHTML=old; erpNotify&&erpNotify('error','Network error.'); });
    });

    // Active toggle
    document.querySelectorAll('.apim-toggle').forEach(function(sw){
        sw.addEventListener('change', function(){
            var row=sw.closest('.apim-row'); var on=sw.checked;
            post('/api-monitor/toggle/' + row.getAttribute('data-id')).then(function(res){
                if(res.ok && res.d.status==='success'){
                    recount();
                    erpNotify && erpNotify(res.d.is_active? 'success':'info',
                        (res.d.is_active?'Enabled':'Disabled')+' · api/v1/'+row.querySelector('.apim-path code').textContent.replace('api/v1/',''));
                } else { sw.checked=!on; erpNotify&&erpNotify('error','Could not update.'); }
            }).catch(function(){ sw.checked=!on; erpNotify&&erpNotify('error','Network error.'); });
        });
    });

    // Filter (search + health)
    var search=document.getElementById('apimSearch'), hf=document.getElementById('apimHealthFilter');
    function applyFilter(){
        var q=(search.value||'').toLowerCase().trim(), hv=hf.value;
        document.querySelectorAll('.apim-group').forEach(function(g){
            var shown=0;
            g.querySelectorAll('.apim-row').forEach(function(row){
                var okQ = !q || row.getAttribute('data-search').indexOf(q)>=0;
                var okH = !hv || row.getAttribute('data-health')===hv;
                var vis = okQ && okH;
                row.classList.toggle('is-hidden', !vis); if(vis) shown++;
            });
            g.style.display = shown? '' : 'none';
        });
    }
    search.addEventListener('input', applyFilter);
    hf.addEventListener('change', applyFilter);

    // Recompute the summary counters from the live DOM.
    function recount(){
        var rows=document.querySelectorAll('.apim-row');
        var s={total:rows.length,active:0,inactive:0,online:0,down:0};
        rows.forEach(function(row){
            var on=row.querySelector('.apim-toggle').checked; on?s.active++:s.inactive++;
            var h=row.getAttribute('data-health');
            if(h==='online') s.online++;
            else if(h==='down'||h==='error'||h==='missing') s.down++;
        });
        Object.keys(s).forEach(function(k){
            var el=document.querySelector('#apimSummary [data-k="'+k+'"]'); if(el) el.textContent=s[k];
        });
    }
})();
</script>
