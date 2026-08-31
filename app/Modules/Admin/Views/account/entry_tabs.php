<?php
/**
 * Combined Deposit (जमा) + Expenditure (नाम) entry — tabbed, no-reload.
 * admin/account/entry
 *
 * Two colour-coded tabs (JAMA green / NAAM red) sit side by side and drive the
 * "Type" of ONE shared form (account/add). Switching a tab recolours the panel
 * and flips type_of_account — so there is a single set of element ids and all of
 * add.php's existing behaviour (account autocomplete, datepicker, media, pay
 * pills, client validation) keeps working untouched. Saving is done via AJAX to
 * admin/account/save_entry: a toast shows the result and the form resets without
 * a page reload. A plain POST still works (no-JS fallback via Account::entry()).
 *
 * Var: $default_type ('deposit' | 'expenses').
 */
$active = (isset($default_type) && $default_type === 'expenses') ? 'expenses' : 'deposit';
?>
<style>
  /* Hidden until JS moves it into .entry-shell (below the fixed header) to avoid a flash. */
  .tf2-wrap{margin:4px 0 16px;display:none}
  .tf2-tabs{display:flex;gap:14px;flex-wrap:nowrap}
  .tf2-tab{flex:1 1 0;min-width:0;cursor:pointer;border:2px solid transparent;border-radius:16px;padding:16px 18px;
    background:#f3f6fb;color:#48566b;font-family:'Plus Jakarta Sans',system-ui,'Segoe UI',sans-serif;
    display:flex;align-items:center;gap:14px;transition:all .18s ease;box-shadow:0 8px 22px -16px rgba(24,36,60,.5)}
  .tf2-tab .ic{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;flex:0 0 auto}
  .tf2-tab .lab{display:flex;flex-direction:column;line-height:1.15;min-width:0}
  .tf2-tab .lab b{font-size:17px;font-weight:800;letter-spacing:-.01em;white-space:nowrap}
  .tf2-tab .lab small{font-size:12px;font-weight:600;opacity:.8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .tf2-tab:hover{transform:translateY(-1px)}

  /* Responsive: shrink on tablets, drop the sub-label / icon on phones (tabs stay side by side). */
  @media (max-width:640px){
    .tf2-tabs{gap:10px}
    .tf2-tab{padding:12px 12px;gap:10px}
    .tf2-tab .ic{width:38px;height:38px;font-size:16px;border-radius:10px}
    .tf2-tab .lab b{font-size:15px}
    .tf2-tab .lab small{display:none}
  }
  @media (max-width:400px){
    .tf2-tab .ic{display:none}
    .tf2-tab{padding:12px 10px;justify-content:center;text-align:center}
  }

  /* JAMA (Deposit) — green */
  .tf2-tab[data-type="deposit"] .ic{background:linear-gradient(135deg,#12a06d,#0a6f49)}
  .tf2-tab[data-type="deposit"].active{border-color:#0f8a5f;background:#e7f7ef;color:#0a6f49;box-shadow:0 12px 26px -14px rgba(15,138,95,.55)}
  /* NAAM (Expenditure) — red/orange */
  .tf2-tab[data-type="expenses"] .ic{background:linear-gradient(135deg,#f0603a,#c0392b)}
  .tf2-tab[data-type="expenses"].active{border-color:#d64027;background:#fdeee9;color:#b5341f;box-shadow:0 12px 26px -14px rgba(214,64,39,.5)}

  /* Host wrapper: hide the shared form's own hero + Type row (the tabs replace them). */
  #tabEntryHost .account-entry-hero{display:none}
  #tabEntryHost .tf2-hide{display:none !important}

  /* ---- Theme the shared form to match the active tab ---- */
  #tabEntryHost.theme-jama .entry-card .card-head{background:linear-gradient(135deg,#12a06d,#0a6f49)}
  #tabEntryHost.theme-jama .account-entry-actions .btn-primary,
  #tabEntryHost.theme-jama .btn-primary{background:#0f8a5f !important;border-color:#0f8a5f !important;color:#fff !important;box-shadow:0 8px 18px rgba(15,138,95,.28) !important}
  #tabEntryHost.theme-jama .account-entry-actions .btn-primary:hover,
  #tabEntryHost.theme-jama .btn-primary:hover{background:#0c7650 !important;border-color:#0c7650 !important}
  #tabEntryHost.theme-jama .form-control:focus{border-color:#0f8a5f;box-shadow:0 0 0 3px rgba(15,138,95,.14)}

  #tabEntryHost.theme-naam .entry-card .card-head{background:linear-gradient(135deg,#f0603a,#c0392b)}
  #tabEntryHost.theme-naam .account-entry-actions .btn-primary,
  #tabEntryHost.theme-naam .btn-primary{background:#d64027 !important;border-color:#d64027 !important;color:#fff !important;box-shadow:0 8px 18px rgba(214,64,39,.26) !important}
  #tabEntryHost.theme-naam .account-entry-actions .btn-primary:hover,
  #tabEntryHost.theme-naam .btn-primary:hover{background:#bd3620 !important;border-color:#bd3620 !important}
  #tabEntryHost.theme-naam .form-control:focus{border-color:#d64027;box-shadow:0 0 0 3px rgba(214,64,39,.14)}

  /* ---- Sticky action bar: Save stays reachable without scrolling (even with no attachments) ---- */
  #tabEntryHost .account-entry-actions{
    position:sticky; bottom:14px; z-index:30;
    margin-top:18px; padding:12px 16px;
    background:rgba(255,255,255,.97); backdrop-filter:blur(8px);
    border:1px solid var(--tm-line,#dce6f2); border-radius:12px;
    box-shadow:0 14px 34px -12px rgba(24,36,60,.32);
  }
  #tabEntryHost.theme-naam .account-entry-actions{border-color:#f4c9bd}
  #tabEntryHost.theme-jama .account-entry-actions{border-color:#bfe6d3}

  /* ---- Full-screen saving loader (also blocks double-submit) ---- */
  #entrySavingOverlay{
    position:fixed; inset:0; z-index:20000; display:none;
    align-items:center; justify-content:center;
    background:rgba(17,28,46,.42); backdrop-filter:blur(2px);
  }
  #entrySavingOverlay.show{display:flex}
  #entrySavingOverlay .es-card{
    background:#fff; border-radius:16px; padding:26px 30px; min-width:210px;
    display:flex; flex-direction:column; align-items:center; gap:14px;
    box-shadow:0 30px 70px -20px rgba(10,20,40,.5); font-family:'Plus Jakarta Sans',system-ui,'Segoe UI',sans-serif;
  }
  #entrySavingOverlay .es-spin{
    width:46px; height:46px; border-radius:50%;
    border:4px solid #e7edf5; border-top-color:#0f8a5f;
    animation:esSpin .8s linear infinite;
  }
  #entrySavingOverlay.naam .es-spin{border-top-color:#d64027}
  #entrySavingOverlay .es-txt{font-size:14.5px; font-weight:800; color:#1f2d45}
  @keyframes esSpin{to{transform:rotate(360deg)}}
</style>

<div class="tf2-wrap" id="entryTabsWrap">
  <div class="tf2-tabs" id="entryTabs">
    <div class="tf2-tab <?= $active === 'deposit' ? 'active' : '' ?>" data-type="deposit">
      <span class="ic"><i class="fa fa-arrow-down"></i></span>
      <span class="lab"><b>जमा • JAMA</b><small>Deposit — money received</small></span>
    </div>
    <div class="tf2-tab <?= $active === 'expenses' ? 'active' : '' ?>" data-type="expenses">
      <span class="ic"><i class="fa fa-arrow-up"></i></span>
      <span class="lab"><b>नाम • NAAM</b><small>Expenditure — money paid</small></span>
    </div>
  </div>
</div>

<div id="tabEntryHost" class="theme-<?= $active === 'expenses' ? 'naam' : 'jama' ?>">
  <?php $default_type = $active; $entry_label = 'Entry'; include __DIR__ . '/add.php'; ?>
</div>

<!-- Full-screen saving loader (shown during AJAX save; blocks input + double clicks) -->
<div id="entrySavingOverlay" aria-live="polite" aria-busy="true">
  <div class="es-card">
    <div class="es-spin"></div>
    <div class="es-txt">Saving entry…</div>
  </div>
</div>

<script>
(function ($) {
  var TAB_BASE = "<?= base_url() ?>";
  function tabToast(t, m, ti) { if (window.showToast) { showToast(t, m, ti || ''); } else { alert(m); } }

  var submitting = false;                                  // guards against double submit
  var $overlay = $('#entrySavingOverlay');
  function showLoader(isNaam) { $overlay.toggleClass('naam', !!isNaam).addClass('show'); }
  function hideLoader() { $overlay.removeClass('show'); }

  $(function () {
    var $host = $('#tabEntryHost');

    // Move the tab bar INTO the form's .entry-shell (inside the offset <main>) so it
    // clears the fixed top header and lines up with the form card; then reveal it.
    var $tabs = $('#entryTabsWrap');
    var $shell = $host.find('.entry-shell').first();
    if ($shell.length) { $shell.prepend($tabs); }
    $tabs.show();

    var $typeGroup = $host.find('select[name="type_of_account"]').closest('.form-group');
    $typeGroup.addClass('tf2-hide');                       // tab replaces the Type dropdown

    function submitBtn() { return $('#ciatyform_id').find('button[type="submit"]'); }
    function isNaam() { return $host.find('select[name="type_of_account"]').val() === 'expenses'; }

    function setTab(type) {
      var naam = (type === 'expenses');
      $host.removeClass('theme-jama theme-naam').addClass(naam ? 'theme-naam' : 'theme-jama');
      $host.find('select[name="type_of_account"]').val(type);
      $('#entryTabs .tf2-tab').removeClass('active');
      $('#entryTabs .tf2-tab[data-type="' + type + '"]').addClass('active');
      submitBtn().text(naam ? 'Save Expenditure (नाम)' : 'Save Deposit (जमा)');
    }
    $('#entryTabs').on('click', '.tf2-tab', function () { setTab($(this).data('type')); });
    // Sync label/colour with the server-chosen default on first load.
    setTab($host.find('select[name="type_of_account"]').val() || 'deposit');

    // Reset the visible acc_picker.js widget back to its placeholder (it replaces
    // #myInput with a rich box, so clearing the hidden input alone isn't enough).
    function resetAccountPicker() {
      $('#myInput').val('').trigger('change');
      var $wrap = $host.find('.acc-picker');
      $wrap.find('.acc-picker-sel').hide().empty();
      $wrap.find('.acc-picker-ph').show();
      $wrap.find('.acc-dd-search').val('');
      $wrap.find('.acc-dd').removeClass('show');
      $wrap.find('.acc-picker-box').removeClass('open');
    }

    function resetEntryForm() {
      resetAccountPicker();
      $('#karch_amount').val('');
      $('[name="remark"]').val('');
      $('[name="party_account_no"]').val('');
      $('[name="party_invoice_no"]').val('');
      $('[name="quantity"]').val('');
      $('[name="rate"]').val('');
      $('[name="truck_no"]').val('');
      $('#image_file, #voice_file, #video_file').val('');
      $('#image_preview, #voice_preview, #video_preview').empty();
      $('#attach_toggle').prop('checked', false);          // re-hide the attachments section
      $('#attach_section').removeClass('open');
      $('.client-error').remove();
      $('.is-invalid').removeClass('is-invalid');
    }

    // AJAX submit — no page reload. add.php's own submit handler runs first and
    // only blocks on invalid input; this one always intercepts and posts.
    $('#ciatyform_id').on('submit', function (e) {
      e.preventDefault();
      if (submitting) { return false; }                    // ignore rapid double-submit
      if (typeof validateDepositForm === 'function' && !validateDepositForm()) { return false; }

      submitting = true;
      var form = this;
      var $btn = submitBtn();
      var orig = $btn.text();
      $btn.prop('disabled', true).text('Saving…');
      showLoader(isNaam());

      $.ajax({
        url: TAB_BASE + 'admin/account/save_entry',
        type: 'POST', data: new FormData(form),
        processData: false, contentType: false, dataType: 'json',
        success: function (res) {
          if (res && res.status === 'success') {
            tabToast('success', res.message, 'Saved');
            resetEntryForm();
            $('#myInput').focus();
          } else {
            tabToast('error', (res && res.message) || 'Could not save the entry.', 'Error');
          }
        },
        error: function () { tabToast('error', 'Could not save the entry. Please try again.', 'Error'); },
        complete: function () {
          submitting = false;
          hideLoader();
          $btn.prop('disabled', false).text(orig);
        }
      });
      return false;
    });
  });
})(jQuery);
</script>
