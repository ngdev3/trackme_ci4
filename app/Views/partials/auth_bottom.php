<?php
/** Shared cosmic auth chrome — bottom half. Closes the shell + loads scripts. */
?>
                </div><!-- /.login-card -->
            </div><!-- /.login-panel -->
        </section>
    </main>

    <!-- Honor the saved UI language on the auth screens too -->
    <div id="google_translate_element" aria-hidden="true"></div>
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,hi,zh-CN,es,ar,fr,bn,pt,ru,ur,de,ja',
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/auth.js') ?>"></script>
    <script src="<?= base_url('assets/js/i18n.js') ?>"></script>
</body>
</html>
