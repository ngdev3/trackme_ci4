<?php
/** Stock Search — worker-friendly. Big search + voice + QR. Card results, no tables. */
$fmt  = static fn ($n) => number_format((float) $n, 0);
$fmtW = static fn ($n) => number_format((float) $n, 2);
?>
<div class="inv-search-page">
    <div class="inv-search-head out-none">
        <a href="<?= site_url('inventory') ?>" class="inv-back dark"><i class="bi bi-arrow-left"></i></a>
        <h2 class="mb-0"><i class="bi bi-search me-2"></i>Stock Search</h2>
    </div>

    <form method="get" action="<?= site_url('inventory/search') ?>" class="inv-search-bar" id="invSearchForm">
        <i class="bi bi-search"></i>
        <input type="search" name="q" id="invQ" value="<?= esc($q, 'attr') ?>" autocomplete="off"
               placeholder="Search product, party, godown, lot…" aria-label="Search stock">
        <button type="button" class="inv-search-mic" id="invMic" title="Voice search"><i class="bi bi-mic"></i></button>
        <button type="button" class="inv-search-qr" id="invQr" title="Scan QR / barcode"><i class="bi bi-qr-code-scan"></i></button>
        <button type="submit" class="inv-search-go">Search</button>
    </form>
    <div class="inv-search-hints">
        Try a product, a party name, a godown, or scan a QR / lot code.
        <a href="<?= site_url('inventory/search?all=1') ?>">Show all stock</a>
    </div>

    <!-- QR scanner (hidden until opened) -->
    <div class="inv-qr-box" id="invQrBox" hidden>
        <video id="invQrVideo" playsinline></video>
        <button type="button" class="btn btn-sm btn-light" id="invQrClose">Close scanner</button>
        <div class="inv-qr-note" id="invQrNote">Point the camera at a QR / barcode.</div>
    </div>

    <?php if ($searched): ?>
        <?php if (empty($results)): ?>
            <div class="inv-empty-card"><i class="bi bi-search"></i><h3>No stock found</h3><p>Nothing matches “<?= esc($q) ?>”. Try a different term.</p></div>
        <?php else: ?>
            <div class="inv-result-count"><?= count($results) ?> result<?= count($results) === 1 ? '' : 's' ?></div>
            <div class="inv-stock-grid inv-result-grid">
                <?php foreach ($results as $s):
                    $bags = (float) $s['bags']; $low = (int) $s['low_stock'];
                    if ($bags <= 0)                   { $st = ['Out of stock', '#dc2626']; }
                    elseif ($low > 0 && $bags <= $low){ $st = ['Low stock', '#f59e0b']; }
                    else                              { $st = ['In stock', '#16a34a']; }
                ?>
                    <div class="inv-stock-card lg">
                        <div class="inv-stock-top">
                            <span class="inv-stock-name"><?= esc($s['product_name']) ?></span>
                            <span class="inv-stock-badge" style="--c: <?= $st[1] ?>"><?= esc($st[0]) ?></span>
                        </div>
                        <div class="inv-stock-bags"><?= $fmt($bags) ?><small>bags</small></div>
                        <div class="inv-stock-meta">
                            <span><i class="bi bi-basket"></i><?= $fmtW($s['weight']) ?> kg approx.</span>
                            <span><i class="bi bi-buildings"></i><?= esc($s['warehouse_name']) ?><?= ! empty($s['location']) ? ' · ' . esc($s['location']) : '' ?></span>
                            <?php if (! empty($s['rack'])): ?><span><i class="bi bi-signpost"></i>Rack <?= esc($s['rack']) ?></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="inv-empty-mini"><i class="bi bi-upc-scan"></i>Search above, tap the mic to speak, or scan a code.</div>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('invSearchForm');
    var q = document.getElementById('invQ');

    // ---- Voice search (Web Speech API) ----
    var mic = document.getElementById('invMic');
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) { mic.style.display = 'none'; }
    else {
        mic.addEventListener('click', function () {
            var rec = new SR();
            rec.lang = document.documentElement.lang || 'en-IN';
            rec.interimResults = false; rec.maxAlternatives = 1;
            mic.classList.add('listening');
            rec.onresult = function (e) {
                var text = (e.results[0][0].transcript || '').trim();
                // Strip filler words so "show potato stock" → "potato".
                text = text.replace(/\b(show|search|find|stock|of|the|please|for)\b/gi, ' ').replace(/\s+/g, ' ').trim();
                q.value = text || (e.results[0][0].transcript || '').trim();
                mic.classList.remove('listening');
                if (q.value) { form.submit(); }
            };
            rec.onerror = function () { mic.classList.remove('listening'); };
            rec.onend = function () { mic.classList.remove('listening'); };
            rec.start();
        });
    }

    // ---- QR / barcode scan (native BarcodeDetector, no library) ----
    var qrBtn = document.getElementById('invQr');
    var box = document.getElementById('invQrBox');
    var video = document.getElementById('invQrVideo');
    var note = document.getElementById('invQrNote');
    var closeBtn = document.getElementById('invQrClose');
    var stream = null, scanning = false;

    function stop() {
        scanning = false;
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
        box.hidden = true;
    }
    closeBtn.addEventListener('click', stop);

    if (!('BarcodeDetector' in window) || !navigator.mediaDevices) {
        qrBtn.title = 'QR scan not supported on this device';
    }
    qrBtn.addEventListener('click', async function () {
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices) {
            note.textContent = 'QR scanning is not supported on this device.'; box.hidden = false; return;
        }
        try {
            box.hidden = false; note.textContent = 'Point the camera at a QR / barcode.';
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream; await video.play();
            var detector = new window.BarcodeDetector();
            scanning = true;
            (async function loop() {
                if (!scanning) { return; }
                try {
                    var codes = await detector.detect(video);
                    if (codes && codes.length) {
                        q.value = codes[0].rawValue; stop(); form.submit(); return;
                    }
                } catch (e) { /* keep trying */ }
                requestAnimationFrame(loop);
            })();
        } catch (e) { note.textContent = 'Could not open the camera.'; }
    });
})();
</script>
