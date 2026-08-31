<div style="padding:22px;max-width:920px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Add Bill of Supply</h1>
        <a href="<?= site_url('admin/invoice/listing') ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to List</a>
    </div>

    <?php if (! empty($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('admin/invoice/add') ?>" id="bos-form" style="background:#fff;border-radius:10px;padding:22px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <?= csrf_field() ?>
        <input type="hidden" name="bill_type" value="0">
        <input type="hidden" name="enable_delivery" value="no">
        <input type="hidden" name="account_label" id="account_label">
        <input type="hidden" name="naam_label" id="naam_label">

        <div class="row">
            <div class="col-sm-6"><div class="form-group"><label>Party (Naam / debit) <span style="color:#d63d5c;">*</span></label>
                <select name="account_id" id="f-account" class="form-control"><option value="">— select party —</option>
                    <?php foreach ($accounts as $a): ?><option value="<?= (int) $a->account_id ?>"><?= esc($a->name) ?></option><?php endforeach; ?>
                </select></div></div>
            <div class="col-sm-6"><div class="form-group"><label>Deposit Account (Jama / credit) <span style="color:#d63d5c;">*</span></label>
                <select name="naam_id" id="f-naam" class="form-control"><option value="">— select account —</option>
                    <?php foreach ($accounts as $a): ?><option value="<?= (int) $a->account_id ?>"><?= esc($a->name) ?></option><?php endforeach; ?>
                </select></div></div>
        </div>

        <div class="row">
            <div class="col-sm-6"><div class="form-group"><label>Product (HSN) <span style="color:#d63d5c;">*</span></label>
                <select id="f-hsn" class="form-control"><option value="">— select product —</option>
                    <?php foreach ($hsn_list as $h): ?><option value="<?= (int) $h->id ?>" data-code="<?= esc($h->hsn_code, 'attr') ?>" data-name="<?= esc($h->product_name, 'attr') ?>"><?= esc($h->product_name) ?> (<?= esc($h->hsn_code) ?>)</option><?php endforeach; ?>
                </select>
                <input type="hidden" name="hsn_code_id" id="f-hsnid"><input type="hidden" name="hsn_code" id="f-hsncode"><input type="hidden" name="product_name" id="f-prod">
                <div id="stock-note" style="font-size:12px;color:#16835d;margin-top:5px;"></div>
            </div></div>
            <div class="col-sm-3"><div class="form-group"><label>Billing Date <span style="color:#d63d5c;">*</span></label><input type="date" name="billing_date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div>
            <div class="col-sm-3"><div class="form-group"><label>UOM</label><input type="text" name="uom" class="form-control" placeholder="qtl / kg"></div></div>
        </div>

        <div class="row">
            <div class="col-sm-3"><div class="form-group"><label>Quantity <span style="color:#d63d5c;">*</span></label><input type="number" step="0.01" name="quantity" id="f-qty" class="form-control"></div></div>
            <div class="col-sm-3"><div class="form-group"><label>Rate <span style="color:#d63d5c;">*</span></label><input type="number" step="0.01" name="rate" id="f-rate" class="form-control"></div></div>
            <div class="col-sm-3"><div class="form-group"><label>Freight (+)</label><input type="number" step="0.01" name="freight" id="f-freight" class="form-control" value="0"></div></div>
            <div class="col-sm-3"><div class="form-group"><label>Advance / Others (−)</label><input type="number" step="0.01" name="others" id="f-others" class="form-control" value="0"></div></div>
        </div>

        <div class="row">
            <div class="col-sm-4"><div class="form-group"><label>Amount (qty × rate)</label><input type="text" id="f-amount" class="form-control" readonly style="background:#f4f7fb;font-weight:700;"></div></div>
            <div class="col-sm-4"><div class="form-group"><label>Total (amount + freight − advance)</label><input type="text" id="f-total" class="form-control" readonly style="background:#eef7f1;font-weight:800;color:#16835d;"></div></div>
            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div></div>
        </div>

        <div class="row">
            <div class="col-sm-4"><div class="form-group"><label>Truck No</label><input type="text" name="truck_no" class="form-control"></div></div>
            <div class="col-sm-4"><div class="form-group"><label>Driver Name</label><input type="text" name="driver_name" class="form-control"></div></div>
            <div class="col-sm-4"><div class="form-group"><label>Remark</label><input type="text" name="remark" class="form-control"></div></div>
        </div>

        <div style="text-align:right;margin-top:8px;">
            <a href="<?= site_url('admin/invoice/listing') ?>" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary" id="bos-submit"><i class="fa fa-save"></i> Create Bill of Supply</button>
        </div>
    </form>
</div>

<script>
(function(){
    function num(v){return parseFloat(v)||0;}
    function recompute(){
        var amt=num(jQuery('#f-qty').val())*num(jQuery('#f-rate').val());
        var total=amt+num(jQuery('#f-freight').val())-num(jQuery('#f-others').val());
        jQuery('#f-amount').val(amt.toFixed(2)); jQuery('#f-total').val(total.toFixed(2));
    }
    jQuery('#f-qty,#f-rate,#f-freight,#f-others').on('input',recompute);
    jQuery('#f-account').on('change',function(){jQuery('#account_label').val(jQuery(this).find('option:selected').text());});
    jQuery('#f-naam').on('change',function(){jQuery('#naam_label').val(jQuery(this).find('option:selected').text());});
    jQuery('#f-hsn').on('change',function(){
        var o=jQuery(this).find('option:selected');
        jQuery('#f-hsnid').val(o.val()); jQuery('#f-hsncode').val(o.data('code')||''); jQuery('#f-prod').val(o.data('name')||'');
        jQuery('#stock-note').text('Checking stock…');
        if(!o.val()){jQuery('#stock-note').text('');return;}
        jQuery.post("<?= site_url('admin/invoice/stock_balance') ?>",{hsn_code_id:o.val()},function(res){
            if(res&&res.status==='success'){jQuery('#stock-note').text('Available stock: '+res.balance+' '+(res.unit||'')+' · '+(res.product||''));}
        },'json');
    });
    jQuery('#bos-form').on('submit',function(){
        if(!jQuery('#f-account').val()||!jQuery('#f-naam').val()||!jQuery('#f-hsnid').val()){alert('Select party, deposit account and product.');return false;}
        if(num(jQuery('#f-qty').val())<=0||num(jQuery('#f-rate').val())<=0){alert('Quantity and rate are required.');return false;}
        jQuery('#bos-submit').prop('disabled',true).text('Creating…');
        return true;
    });
})();
</script>
