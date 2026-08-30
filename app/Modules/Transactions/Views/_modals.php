<?php
/**
 * Shared Transactions modals — included by the Rokad Parcha, ledger and detail
 * views. Provides:
 *   • #txEntryModal  — view an entry (details + attachments + reminder), AJAX-loaded
 *   • #txDeleteModal — delete an entry with a mandatory reason
 *
 * Any element with `data-tx-view data-id="N"` opens the entry modal.
 * Any element with `data-tx-delete data-action="URL" data-label="TXN"` opens delete.
 */
?>
<div class="modal fade" id="txEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content"><div class="p-5 text-center text-secondary"><div class="spinner-border"></div></div></div>
    </div>
</div>

<div class="modal fade" id="txDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="txDeleteForm">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-1"></i> Delete entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to delete <strong id="txDeleteLabel"></strong>. This can be restored later from <em>Deleted Entries</em>.</p>
                <label class="form-label">Reason for deletion <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="2" required maxlength="255" placeholder="e.g. duplicate entry, wrong amount…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{csp-script-nonce}">
(function () {
    var entryEl = document.getElementById('txEntryModal');
    var delEl   = document.getElementById('txDeleteModal');
    var base = '<?= site_url('transactions/entry/') ?>';

    // Instantiate lazily — Bootstrap's JS loads in the page footer, after this
    // script, so `bootstrap` only exists by the time the user clicks.
    function modal(el) { return bootstrap.Modal.getOrCreateInstance(el); }

    // Open the entry (view) modal — AJAX-load the fragment.
    document.addEventListener('click', function (e) {
        var v = e.target.closest('[data-tx-view]');
        // A whole row may carry data-tx-view. Controls nested inside it (Edit, Delete)
        // keep their own behaviour, so only a click on the row itself opens the view.
        var control = e.target.closest('a, button, input, select, textarea, label');
        if (v && (!control || control === v)) {
            e.preventDefault();
            var id = v.getAttribute('data-id');
            var content = entryEl.querySelector('.modal-content');
            content.innerHTML = '<div class="p-5 text-center text-secondary"><div class="spinner-border"></div></div>';
            modal(entryEl).show();
            fetch(base + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) { content.innerHTML = html; })
                .catch(function () { content.innerHTML = '<div class="p-4 text-danger text-center">Failed to load entry.</div>'; });
            return;
        }

        // Open the delete-with-reason modal.
        var d = e.target.closest('[data-tx-delete]');
        if (d) {
            e.preventDefault();
            document.getElementById('txDeleteForm').setAttribute('action', d.getAttribute('data-action'));
            document.getElementById('txDeleteLabel').textContent = d.getAttribute('data-label') || 'this entry';
            var wasOpen = entryEl.classList.contains('show');
            if (wasOpen) { modal(entryEl).hide(); }
            setTimeout(function () { modal(delEl).show(); }, wasOpen ? 250 : 0);
        }
    });
})();
</script>
