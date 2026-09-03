<?php
include __DIR__ . '/_head.php';
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$mlabels = isset($module_labels) ? $module_labels : array();
$ipMarkers = isset($ip_markers) ? $ip_markers : array();

// Exact device-GPS markers (from the entry audit log).
$gpsPoints = array();
foreach ($points as $p) {
    $gpsPoints[] = array(
        'lat' => (float) $p->latitude, 'lng' => (float) $p->longitude,
        'module' => $p->module, 'label' => isset($mlabels[$p->module]) ? $mlabels[$p->module] : $p->module,
        'entry' => (int) $p->entry_id, 'action' => $p->action, 'user' => $p->user_name,
        'ip' => $p->ip_address, 'acc' => $p->accuracy, 'when' => date('d M Y h:i A', strtotime($p->created_at)),
        'url' => base_url('admin/account/edit/' . ID_encode((int) $p->entry_id)),
    );
}
$verBadge = function ($v) {
    if ((int) $v === 6) { return '<span class="mon-kind" style="background:#dcfce7;color:#15803d;">IPv6</span>'; }
    if ((int) $v === 4) { return '<span class="mon-kind" style="background:#dbeafe;color:#1d4ed8;">IPv4</span>'; }
    return '<span class="mon-kind" style="background:#f1f5f9;color:#64748b;">?</span>';
};
?>
<link rel="stylesheet" href="<?= base_url(); ?>assets/global/plugins/leaflet/leaflet.css" />
<div class="main-content mon-scope">
    <div class="mon-shell">
        <?php include __DIR__ . '/_tabs.php'; ?>

        <!-- IP version + map summary -->
        <div class="mon-kpis" style="grid-template-columns:repeat(4,1fr);">
            <div class="mon-kpi"><div class="mon-kpi-ic ic-blue"><i class="ti-world"></i></div><div class="mon-kpi-t"><span>IPv4 Addresses</span><strong><?= number_format((int) $v4_count) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-green"><i class="ti-world"></i></div><div class="mon-kpi-t"><span>IPv6 Addresses</span><strong><?= number_format((int) $v6_count) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-cyan"><i class="ti-location-pin"></i></div><div class="mon-kpi-t"><span>Mapped IPs</span><strong><?= number_format(count($ipMarkers)) ?></strong></div></div>
            <div class="mon-kpi"><div class="mon-kpi-ic ic-violet"><i class="ti-target"></i></div><div class="mon-kpi-t"><span>Exact GPS Points</span><strong><?= number_format(count($gpsPoints)) ?></strong></div></div>
        </div>

        <?php if ($is_super): ?>
        <div class="mon-panel">
            <div class="mon-panel-h">
                <b>Access Map</b>
                <span class="mon-badge">exact GPS (by module) + approximate IP location (IPv4 / IPv6)</span>
            </div>
            <div class="mon-panel-b">
                <?php if (empty($gpsPoints) && empty($ipMarkers)): ?>
                    <div class="mon-empty">Nothing to map for this period.</div>
                <?php else: ?>
                    <div id="monMap"></div>
                    <div id="monLegend" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;"></div>
                    <div style="font-size:11px; color:#94a3b8; margin-top:8px;"><i class="ti-info-alt"></i> Solid pins = exact device GPS (entry audit), coloured by module. Ringed pins = approximate location derived from the IP (city-level), coloured by IP version. Click a chip to toggle a layer.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Module access -->
        <div class="mon-panel">
            <div class="mon-panel-h"><b>Module Access</b><span class="mon-badge">page views + entry actions per module</span></div>
            <div class="mon-panel-b">
                <?php if (empty($module_access)): ?><div class="mon-empty">No module activity in this period.</div>
                <?php else: $maxAcc = 1; foreach ($module_access as $ma) { $maxAcc = max($maxAcc, $ma->views + $ma->entries); } ?>
                    <table class="table mon-tbl">
                        <thead><tr><th>Module</th><th>Activity</th><th>Page Views</th><th>Entry Actions</th><th>GPS</th><th>Users</th><th>IPs</th></tr></thead>
                        <tbody>
                            <?php foreach ($module_access as $ma): $lbl = isset($mlabels[$ma->slug]) ? $mlabels[$ma->slug] : ucwords(str_replace('_', ' ', $ma->slug)); $tot = $ma->views + $ma->entries; ?>
                                <tr>
                                    <td><b><?= $esc($lbl) ?></b><br><span class="et-slug"><?= $esc($ma->slug) ?></span></td>
                                    <td style="min-width:150px;"><div class="mon-bar"><i style="width:<?= (int) round($tot / $maxAcc * 100) ?>%"></i></div><span class="mon-badge"><?= number_format($tot) ?></span></td>
                                    <td><?= number_format($ma->views) ?></td>
                                    <td><?= number_format($ma->entries) ?></td>
                                    <td><?= $ma->geo > 0 ? '<span class="mon-kind k-entry_create">' . number_format($ma->geo) . '</span>' : '<span class="text-muted">0</span>' ?></td>
                                    <td><?= number_format($ma->users) ?></td>
                                    <td><?= number_format($ma->ips) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- IP intelligence with version + location -->
        <div class="mon-panel">
            <div class="mon-panel-h"><b>IP Address Intelligence</b><span class="mon-badge">IPv4 &amp; IPv6 &middot; across visits, entries &amp; logins &middot; <i class="ti-hand-point-up"></i> click an IP to see the pages it opened</span></div>
            <div class="mon-panel-b">
                <?php if (empty($ips)): ?><div class="mon-empty">No IP activity in this period.</div>
                <?php else: ?>
                    <table class="table mon-tbl">
                        <thead><tr><th>IP Address</th><th>Ver</th><th>MAC Address</th><th>Location (approx)</th><th>Modules Accessed</th><th>Hits</th><th>Users</th><th>First Seen</th><th>Last Seen</th><th>Flags</th></tr></thead>
                        <tbody>
                            <?php foreach ($ips as $ip): ?>
                                <tr>
                                    <td><a href="javascript:void(0);" class="mon-ip js-ip-urls" data-ip="<?= $esc($ip->ip) ?>" title="View the exact pages / URLs this IP opened" style="cursor:pointer;text-decoration:none;"><?= $esc($ip->ip) ?> <i class="ti-angle-right" style="font-size:10px;opacity:.55"></i></a></td>
                                    <td><?= $verBadge(isset($ip->version) ? $ip->version : 0) ?></td>
                                    <td>
                                        <?php if (!empty($ip->mac)): ?>
                                            <span class="mon-ip" style="color:#6d28d9;background:#f5f3ff;"><?= $esc($ip->mac) ?></span>
                                            <?php if (!empty($ip->mac_vendor)): ?><br><span class="et-slug"><?= $esc($ip->mac_vendor) ?></span><?php endif; ?>
                                        <?php elseif (isset($ip->mac_source) && $ip->mac_source === 'loopback'): ?>
                                            <span class="text-muted" title="Same machine as the server">Loopback</span>
                                        <?php elseif (isset($ip->mac_source) && $ip->mac_source === 'unknown'): ?>
                                            <span class="text-muted" title="On the LAN but not in the ARP cache yet">LAN — not cached</span>
                                        <?php else: ?>
                                            <span class="text-muted" title="MAC is not obtainable for remote/internet clients">N/A (remote)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ip->city) || !empty($ip->country)): ?>
                                            <?= $esc(trim(($ip->city ? $ip->city . ', ' : '') . $ip->country, ', ')) ?>
                                            <?php if (!empty($ip->isp)): ?><br><span class="et-slug"><?= $esc($ip->isp) ?></span><?php endif; ?>
                                        <?php elseif (isset($ip->geo_status) && $ip->geo_status === 'local'): ?>
                                            <span class="text-muted">Local / private</span>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td style="max-width:280px;">
                                        <?php
                                        $mods = isset($ip->modules) ? $ip->modules : array();
                                        if (empty($mods)) { echo '<span class="text-muted">—</span>'; }
                                        else {
                                            foreach (array_slice($mods, 0, 5) as $mm) {
                                                echo '<span class="mon-kind" style="background:#eef2f7;color:#334155;margin:1px 2px 1px 0;display:inline-block;" title="' . $esc($mm->slug) . '">' . $esc($mm->label) . ' <b>' . (int) $mm->count . '</b></span>';
                                            }
                                            if (count($mods) > 5) { echo '<span class="et-slug">+' . (count($mods) - 5) . ' more</span>'; }
                                        }
                                        ?>
                                    </td>
                                    <td><?= number_format((int) $ip->hits) ?></td>
                                    <td><?= (int) $ip->user_count ?></td>
                                    <td class="mon-when"><?= !empty($ip->first) ? date('d M Y h:i A', strtotime($ip->first)) : '-' ?></td>
                                    <td class="mon-when"><?= !empty($ip->last) ? date('d M Y h:i A', strtotime($ip->last)) : '-' ?></td>
                                    <td>
                                        <?php if ($ip->user_count >= 2): ?><span class="mon-kind k-entry_delete">Shared</span> <?php endif; ?>
                                        <?php if ((int) $ip->geo > 0): ?><span class="mon-kind k-entry_create">GPS</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- IP drill-down modal: exact pages/URLs an IP opened -->
<div id="ipUrlsModal" class="ipum-overlay" style="display:none;">
    <div class="ipum-box">
        <div class="ipum-head">
            <div>
                <b>Pages opened by <span id="ipumIp" class="mon-ip"></span></b>
                <div class="ipum-sub" id="ipumRange"></div>
            </div>
            <button type="button" class="ipum-x" id="ipumClose" aria-label="Close">&times;</button>
        </div>
        <div class="ipum-body" id="ipumBody">
            <div class="mon-empty">Loading…</div>
        </div>
    </div>
</div>

<style>
    .ipum-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:20000;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;overflow:auto;}
    .ipum-box{background:#fff;border-radius:12px;box-shadow:0 30px 80px rgba(2,6,23,.4);width:100%;max-width:920px;overflow:hidden;}
    .ipum-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid #eef2f7;}
    .ipum-head b{font-size:15px;color:#0f172a;}
    .ipum-sub{font-size:11px;color:#94a3b8;font-weight:700;margin-top:3px;}
    .ipum-x{border:0;background:#f1f5f9;color:#475569;width:32px;height:32px;border-radius:8px;font-size:20px;line-height:1;cursor:pointer;}
    .ipum-x:hover{background:#e2e8f0;}
    .ipum-body{max-height:70vh;overflow:auto;padding:14px 20px 20px;}
    .ipum-tbl{width:100%;border-collapse:collapse;}
    .ipum-tbl th{position:sticky;top:0;background:#f8fafc;color:#516174;font-size:10px;font-weight:900;text-transform:uppercase;text-align:left;padding:9px 10px;border-bottom:1px solid #e6edf5;white-space:nowrap;}
    .ipum-tbl td{padding:9px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#26374f;vertical-align:top;}
    .ipum-tbl tr:hover td{background:#f8fbff;}
    .ipum-url{font-family:Consolas,monospace;word-break:break-all;}
    .ipum-url a{color:#1769c2;text-decoration:none;}
    .ipum-url a:hover{text-decoration:underline;}
    .ipum-hits{display:inline-block;min-width:26px;text-align:center;background:#eef2f7;border-radius:6px;padding:2px 7px;font-weight:900;color:#334155;}
    .ipum-mod{background:#eef2f7;color:#334155;border-radius:5px;padding:2px 7px;font-size:11px;font-weight:800;white-space:nowrap;}
    .ipum-when{color:#64748b;white-space:nowrap;}
</style>

<script>
(function () {
    var URL_ENDPOINT = '<?= base_url('admin/monitor/ip_urls') ?>';
    var FILTERS = { from: '<?= html_escape($filters['from']) ?>', to: '<?= html_escape($filters['to']) ?>', user: '<?= (int) $filters['user'] ?>' };

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]; }); }

    var modal = document.getElementById('ipUrlsModal');
    var body  = document.getElementById('ipumBody');
    function open() { modal.style.display = 'flex'; }
    function close() { modal.style.display = 'none'; body.innerHTML = '<div class="mon-empty">Loading…</div>'; }

    document.getElementById('ipumClose').onclick = close;
    modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.style.display === 'flex') { close(); } });

    function render(resp) {
        var rows = (resp && resp.rows) || [];
        document.getElementById('ipumIp').textContent = resp.ip || '';
        document.getElementById('ipumRange').textContent = 'Date range: ' + (resp.from || '') + ' → ' + (resp.to || '') + ' · ' + rows.length + ' distinct page(s)';
        if (!rows.length) { body.innerHTML = '<div class="mon-empty">No page visits recorded for this IP in the selected period.</div>'; return; }
        var h = '<table class="ipum-tbl"><thead><tr><th>#</th><th>Page / URL</th><th>Module</th><th>Hits</th><th>User</th><th>First</th><th>Last</th></tr></thead><tbody>';
        rows.forEach(function (r, i) {
            var u = esc(r.url), link = /^https?:\/\//i.test(r.url) ? '<a href="' + u + '" target="_blank" rel="noopener">' + u + '</a>' : u;
            h += '<tr><td>' + (i + 1) + '</td>'
               + '<td class="ipum-url">' + link + '</td>'
               + '<td><span class="ipum-mod" title="' + esc(r.action) + '">' + esc(r.module) + '</span></td>'
               + '<td><span class="ipum-hits">' + (r.hits || 0) + '</span></td>'
               + '<td>' + esc(r.user) + '</td>'
               + '<td class="ipum-when">' + esc(r.first) + '</td>'
               + '<td class="ipum-when">' + esc(r.last) + '</td></tr>';
        });
        h += '</tbody></table>';
        body.innerHTML = h;
    }

    var links = document.querySelectorAll('.js-ip-urls');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function () {
            var ip = this.getAttribute('data-ip');
            open();
            body.innerHTML = '<div class="mon-empty">Loading pages for ' + esc(ip) + '…</div>';
            var fd = new FormData();
            fd.append('ip', ip); fd.append('from', FILTERS.from); fd.append('to', FILTERS.to); fd.append('user', FILTERS.user);
            fetch(URL_ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function () { body.innerHTML = '<div class="mon-empty">Could not load pages for this IP.</div>'; });
        });
    }
})();
</script>

<?php if ($is_super && (!empty($gpsPoints) || !empty($ipMarkers))): ?>
<script src="<?= base_url(); ?>assets/global/plugins/leaflet/leaflet.js"></script>
<script>
    (function () {
        var GPS = <?= json_encode($gpsPoints, JSON_UNESCAPED_UNICODE); ?>;
        var IPM = <?= json_encode($ipMarkers, JSON_UNESCAPED_UNICODE); ?>;
        var PALETTE = ['#2563eb','#16a34a','#db2777','#ea580c','#7c3aed','#0891b2','#ca8a04','#e11d48','#0d9488','#4f46e5','#9333ea','#65a30d'];
        function colorFor(slug) { var h = 0; for (var i = 0; i < slug.length; i++) { h = (h * 31 + slug.charCodeAt(i)) % 100000; } return PALETTE[h % PALETTE.length]; }
        function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]; }); }

        var map = L.map('monMap');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

        var groups = {}, bounds = [];
        function grp(key, label, color) { if (!groups[key]) { groups[key] = L.layerGroup().addTo(map); groups[key]._label = label; groups[key]._col = color; groups[key]._n = 0; } return groups[key]; }

        // Exact GPS markers (module coloured, solid).
        GPS.forEach(function (p) {
            var col = colorFor(p.module), g = grp('mod::' + p.module, p.label, col);
            var m = L.circleMarker([p.lat, p.lng], { radius: 8, color: '#fff', weight: 2, fillColor: col, fillOpacity: .95 });
            m.bindPopup('<div style="min-width:180px"><div style="font-weight:800;color:' + col + '">' + esc(p.label) + ' <span style="font-size:10px;color:#64748b">· exact GPS</span></div><div style="font-size:12px;margin:4px 0"><b>' + esc(p.user) + '</b> · ' + esc(p.action) + '</div><div style="font-size:12px">Entry <a href="' + p.url + '" target="_blank">#' + p.entry + '</a></div><div style="font-size:11px;color:#64748b;margin-top:3px">' + esc(p.when) + (p.ip ? ' · ' + esc(p.ip) : '') + '</div><a style="font-size:11px" target="_blank" href="https://www.google.com/maps?q=' + p.lat + ',' + p.lng + '">Google Maps</a></div>');
            m.addTo(g); g._n++; bounds.push([p.lat, p.lng]);
        });

        // Approximate IP markers (version coloured, ringed/translucent).
        IPM.forEach(function (p) {
            var v6 = (p.version === 6), col = v6 ? '#16a34a' : '#2563eb';
            var g = grp('ipv' + (v6 ? 6 : 4), 'IPv' + (v6 ? 6 : 4) + ' (approx)', col);
            var m = L.circleMarker([p.lat, p.lng], { radius: 11, color: col, weight: 2, dashArray: '3', fillColor: col, fillOpacity: .28 });
            m.bindPopup('<div style="min-width:200px;max-width:260px"><div style="font-weight:800;color:' + col + '">IPv' + (v6 ? 6 : 4) + ' <span style="font-size:10px;color:#64748b">· approx (city-level)</span></div><div class="mon-ip" style="font-size:12px;margin:4px 0">' + esc(p.ip) + '</div><div style="font-size:12px">' + esc([p.city, p.region, p.country].filter(Boolean).join(', ')) + '</div><div style="font-size:11px;color:#64748b">' + esc(p.isp || '') + '</div>' + (p.modules ? '<div style="font-size:11px;color:#334155;margin-top:4px"><b>Modules:</b> ' + esc(p.modules) + '</div>' : '') + '<div style="font-size:11px;color:#64748b;margin-top:3px">' + p.hits + ' hits · ' + p.users + ' user(s)</div>' + ((p.first || p.last) ? '<div style="font-size:11px;color:#64748b;margin-top:2px"><b>Seen:</b> ' + esc(p.first) + (p.last && p.last !== p.first ? ' → ' + esc(p.last) : '') + '</div>' : '') + '</div>');
            m.addTo(g); g._n++; bounds.push([p.lat, p.lng]);
        });

        if (bounds.length) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 }); } else { map.setView([22.9734, 78.6569], 5); }

        var legend = document.getElementById('monLegend');
        Object.keys(groups).forEach(function (key) {
            var g = groups[key], chip = document.createElement('span');
            var ring = key.indexOf('ipv') === 0 ? 'border:2px dashed ' + g._col + ';background:' + g._col + '44' : 'background:' + g._col;
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;border:1px solid #e3e9f2;background:#fff;font-size:12px;font-weight:800;color:#334155;cursor:pointer;';
            chip.innerHTML = '<i style="width:12px;height:12px;border-radius:50%;' + ring + '"></i>' + esc(g._label) + ' <span style="color:#94a3b8">(' + g._n + ')</span>';
            chip.dataset.on = '1';
            chip.onclick = function () { if (chip.dataset.on === '1') { map.removeLayer(g); chip.dataset.on = '0'; chip.style.opacity = '.4'; } else { map.addLayer(g); chip.dataset.on = '1'; chip.style.opacity = '1'; } };
            legend.appendChild(chip);
        });

        setTimeout(function () { map.invalidateSize(); }, 200);
    })();
</script>
<?php endif; ?>
