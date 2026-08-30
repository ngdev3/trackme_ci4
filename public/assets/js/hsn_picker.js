/**
 * hsn_picker.js — app-wide HSN / commodity dropdown enhancer.
 *
 * Upgrades any <select data-hsn-picker> into the rich Select2 (v3) dropdown
 * used on the Bill of Supply add screen: each option shows the commodity icon
 * uploaded in the HSN Code Master (data-img), falling back to a default image.
 *
 * Usage (server side):
 *   <select name="hsn_code" id="hsn_code" data-hsn-picker>
 *     <?php foreach ($hsn_list as $row): ?>
 *       <option value="..." data-img="<?= hsn_icon_img($row) ?>" ...>...</option>
 *     <?php endforeach; ?>
 *   </select>
 *
 * Self-initialising + idempotent. For dynamically injected selects call
 * window.hsnPickerInit(context).
 */
(function ($) {
    'use strict';
    if (!$) { return; }

    function fmt(item) {
        if (!item || !item.id) { return item ? item.text : ''; }
        var img = $(item.element).data('img');
        var imgTag = img ? '<img class="hsn-opt-img" src="' + img + '" alt="">' : '';
        return '<span class="hsn-opt">' + imgTag + '<span class="hsn-opt-t">' + item.text + '</span></span>';
    }

    function enhance(el) {
        var $el = $(el);
        if (!$.fn.select2) { return; }
        // Skip if we (or any other code) already turned this into a select2.
        if ($el.data('hsnPickerReady') || $el.data('select2') || $el.hasClass('select2-offscreen')) { return; }
        try {
            $el.select2({
                formatResult: fmt,
                formatSelection: fmt,
                escapeMarkup: function (m) { return m; },
                dropdownCssClass: 'hsn-dd',
                containerCssClass: 'hsn-select',
                width: '100%'
            });
            $el.data('hsnPickerReady', true);
        } catch (e) { /* select2 missing — native select still works */ }
    }

    function initAll(ctx) {
        $('select[data-hsn-picker]', ctx || document).each(function () { enhance(this); });
    }

    // Defer a tick so select2.min.js (loaded near the end of layout) is ready.
    $(function () { setTimeout(function () { initAll(document); }, 0); });

    // Expose for dynamically added selects.
    window.hsnPickerInit = initAll;
})(window.jQuery);
