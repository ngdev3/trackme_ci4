<?php
/**
 * Language boot guard — must be included as early as possible inside <head>.
 *
 * If a non-English UI language was previously chosen (googtrans cookie), it
 * flags <html class="lang-loading"> BEFORE first paint. The i18n.css then
 * hides the page and shows #langLoader (from partials/lang_widgets) until the
 * translate engine has applied the language, so the dashboard / login screen
 * is never revealed in untranslated English first.
 */
?>
<script nonce="{csp-script-nonce}">
    (function () {
        var m = document.cookie.match(/googtrans=\/[^/]*\/([^;]+)/);
        var lang = m ? decodeURIComponent(m[1]) : 'en';
        if (lang && lang !== 'en') {
            document.documentElement.classList.add('lang-loading');
        }
    })();
</script>
