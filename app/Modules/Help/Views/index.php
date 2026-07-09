<?php
/** Help & Support + FAQ. Rendered inside layout.php. */
?>
<div class="row g-3">
    <!-- ============ Support hero ============ -->
    <div class="col-12">
        <div class="card border-0" style="background: linear-gradient(135deg, var(--bs-primary, #4f46e5), #7c3aed); color:#fff;">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="mb-1 text-white"><i class="bi bi-life-preserver me-2"></i>Help &amp; Support</h3>
                        <p class="mb-0" style="opacity:.9">We’re here to help you get the most out of <strong translate="no"><?= esc($appName) ?></strong>. Reach us on WhatsApp or email — we usually reply fast.</p>
                    </div>
                    <a href="<?= esc($waLink, 'attr') ?>" target="_blank" rel="noopener" class="btn btn-light btn-lg fw-semibold">
                        <i class="bi bi-whatsapp text-success me-1"></i> Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Contact cards ============ -->
    <div class="col-md-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mx-auto mb-2 d-grid" style="width:56px;height:56px;place-items:center;border-radius:16px;background:#25D36622;color:#25D366;font-size:1.6rem;"><i class="bi bi-whatsapp"></i></div>
                <h5 class="mb-1">WhatsApp</h5>
                <p class="text-muted small mb-2">Fastest response</p>
                <a href="<?= esc($waLink, 'attr') ?>" target="_blank" rel="noopener" class="fw-semibold" translate="no"><?= esc($waShown) ?></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mx-auto mb-2 d-grid" style="width:56px;height:56px;place-items:center;border-radius:16px;background:rgba(var(--bs-primary-rgb),.12);color:var(--bs-primary);font-size:1.6rem;"><i class="bi bi-envelope-fill"></i></div>
                <h5 class="mb-1">Email</h5>
                <p class="text-muted small mb-2">Detailed queries</p>
                <a href="mailto:<?= esc($email, 'attr') ?>" class="fw-semibold" translate="no"><?= esc($email) ?></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="mx-auto mb-2 d-grid" style="width:56px;height:56px;place-items:center;border-radius:16px;background:rgba(var(--bs-info-rgb),.14);color:var(--bs-info);font-size:1.6rem;"><i class="bi bi-globe2"></i></div>
                <h5 class="mb-1">Website</h5>
                <p class="text-muted small mb-2">Product &amp; updates</p>
                <a href="https://<?= esc($appUrl, 'attr') ?>" target="_blank" rel="noopener" class="fw-semibold" translate="no"><?= esc($appUrl) ?></a>
            </div>
        </div>
    </div>

    <!-- ============ FAQ ============ -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0"><i class="bi bi-patch-question me-1"></i> Frequently Asked Questions</h3>
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="faqSearch" class="form-control" placeholder="Search FAQs…" aria-label="Search FAQs">
                </div>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $i => $f): ?>
                        <div class="accordion-item faq-item" data-faq="<?= esc(strtolower($f['q'] . ' ' . $f['a']), 'attr') ?>">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                    <?= esc($f['q']) ?>
                                </button>
                            </h2>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary"><?= esc($f['a']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div id="faqEmpty" class="text-center text-secondary py-4" hidden>
                        <i class="bi bi-search fs-4 d-block mb-2"></i>No FAQ matches your search. Try WhatsApp or email above.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.getElementById('faqSearch');
    if (!box) { return; }
    var items = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
    var empty = document.getElementById('faqEmpty');
    box.addEventListener('input', function () {
        var q = box.value.trim().toLowerCase(), n = 0;
        items.forEach(function (el) {
            var hit = !q || el.getAttribute('data-faq').indexOf(q) !== -1;
            el.hidden = !hit; if (hit) { n++; }
        });
        empty.hidden = n !== 0;
    });
})();
</script>
