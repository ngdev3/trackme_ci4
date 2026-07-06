<?php
/**
 * Shared language widgets — included by the app layout and the auth pages.
 *   1. #langLoader  — full-screen spinner shown while html.lang-loading is set
 *                     (i.e. until the translate engine has applied the language).
 *   2. #erpLangModal — first-visit popup asking the user to pick a language.
 */
$langs = erp_languages();
?>
<!-- Waits for the language module before revealing the page (no untranslated flash) -->
<div id="langLoader" aria-hidden="true">
    <div class="lang-loader-spin"></div>
    <div class="lang-loader-text" translate="no">Loading language…</div>
</div>

<!-- First-visit language chooser -->
<div id="erpLangModal" class="erp-lang-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="erp-lang-modal-card" translate="no">
        <button type="button" class="erp-lang-modal-close" data-lang-skip aria-label="Close">&times;</button>
        <div class="erp-lang-modal-head">
            <div class="erp-lang-modal-icon"><i class="bi bi-translate"></i></div>
            <h4>Choose your language</h4>
            <p>Pick the language you'd like to use across the app. You can change it anytime from the top bar.</p>
        </div>
        <div class="erp-lang-modal-grid">
            <?php foreach ($langs as $code => $l): ?>
                <button type="button" class="erp-lang-pick" data-lang="<?= esc($code, 'attr') ?>">
                    <span class="lang-flag"><?= $l['flag'] ?></span>
                    <span class="erp-lang-pick-text">
                        <strong><?= esc($l['native']) ?></strong>
                        <small><?= esc($l['name']) ?></small>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
        <button type="button" class="erp-lang-modal-skip" data-lang-skip>Continue in English</button>
    </div>
</div>
