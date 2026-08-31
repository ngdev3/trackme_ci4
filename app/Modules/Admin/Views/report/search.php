<style>
* {
  box-sizing: border-box;
}

.report-search-page {
  color: var(--tm-ink, #18243c);
}

.report-shell {
  max-width: 1440px;
  margin: 0 auto;
}

.report-page-title {
  position: relative;
  margin: 6px 0 18px;
  padding: 18px 20px 18px 66px;
  border: 1px solid rgba(var(--tm-brand-rgb, 23, 105, 194), .13);
  border-radius: 8px;
  color: var(--tm-ink, #18243c) !important;
  background:
    linear-gradient(135deg, rgba(255,255,255,.98), rgba(255,255,255,.88)),
    radial-gradient(circle at 96% 0, rgba(var(--tm-brand-rgb, 23, 105, 194), .16), transparent 38%);
  box-shadow: 0 14px 34px rgba(24, 36, 60, .08);
  font-size: 24px;
  font-weight: 900;
  letter-spacing: 0;
}

.report-page-title:before {
  content: "\e6a4";
  position: absolute;
  left: 20px;
  top: 50%;
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: #fff;
  background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-brand-dark, #0c315f));
  box-shadow: 0 10px 22px rgba(var(--tm-brand-rgb, 23, 105, 194), .22);
  font-family: themify;
  font-size: 15px;
  transform: translateY(-50%);
}

.report-panel {
  border: 1px solid var(--tm-line, #dce6f2) !important;
  border-radius: 8px !important;
  background: rgba(255,255,255,.96) !important;
  box-shadow: 0 14px 34px rgba(24, 36, 60, .08);
}

.report-search-card {
  padding: 18px !important;
}

.report-search-grid {
  display: grid;
  grid-template-columns: minmax(260px, 420px) auto minmax(360px, 1fr);
  gap: 14px;
  align-items: end;
}

.report-search-grid > * {
  min-width: 0;
}

.report-search-grid > [class*="col-"] {
  width: auto !important;
  max-width: none !important;
  flex: none !important;
}

.report-field {
  margin: 0 !important;
  height: auto !important;
}

.report-field label {
  margin-bottom: 7px;
  color: var(--tm-muted, #718096);
  font-size: 12px;
  font-weight: 900;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.report-field .form-control,
.report-field input[type=text],
.report-search-page input[type=text] {
  width: 100%;
  min-height: 44px;
  border: 1px solid var(--tm-line, #dce6f2) !important;
  border-radius: 8px !important;
  color: var(--tm-ink, #18243c);
  background: #fff !important;
  box-shadow: none;
  font-size: 14px;
  transition: border-color .18s ease, box-shadow .18s ease;
}

.report-field .form-control:focus,
.report-search-page input[type=text]:focus {
  border-color: var(--tm-brand, #1769c2) !important;
  box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23, 105, 194), .12) !important;
}

.report-action {
  min-height: 44px;
  min-width: 118px;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px !important;
  border-radius: 8px !important;
  font-weight: 900;
  white-space: nowrap;
}

.report-button-field {
  align-self: end;
}

.report-button-field .report-action {
  transform: translateY(-7px);
}

.report-summary-field {
  grid-column: 1 / -1;
  width: 100% !important;
}

.report-kisan-summary {
  display: block;
  width: 100%;
  margin: 0;
  overflow: hidden;
  border: 1px solid var(--tm-line, #dce6f2);
  border-radius: 8px;
  background: #fff;
}

.report-kisan-summary thead,
.report-kisan-summary tbody,
.report-kisan-summary tr {
  display: block;
}

.report-kisan-summary thead tr,
.report-kisan-summary tbody tr {
  display: grid;
  grid-template-columns: repeat(5, minmax(92px, 1fr));
}

.report-kisan-summary thead th,
.report-kisan-summary tbody th {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 8px 10px !important;
  text-align: center;
}

.report-kisan-summary thead th {
  border: 0 !important;
  color: var(--tm-muted, #718096);
  background: linear-gradient(180deg, var(--tm-brand-soft, #eaf3ff), #fff);
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  white-space: nowrap;
}

.report-kisan-summary tbody th {
  border-top: 1px solid var(--tm-line, #dce6f2) !important;
  border-left: 1px solid var(--tm-line, #dce6f2) !important;
  color: var(--tm-brand-dark, #0c315f);
  font-size: 14px;
  font-weight: 900;
  background: #fff;
}

.report-kisan-summary thead th + th {
  border-left: 1px solid rgba(var(--tm-brand-rgb, 23, 105, 194), .12) !important;
}

.report-kisan-summary tbody th:first-child {
  border-left: 0 !important;
}

.report-results-panel {
  padding: 0 !important;
  overflow: hidden;
}

.report-result-table {
  margin: 0;
  border-collapse: separate;
  border-spacing: 0;
}

.report-result-table tr {
  position: relative;
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}

.report-result-table tr:hover {
  background: rgba(var(--tm-brand-rgb, 23, 105, 194), .04);
}

.report-result-table th,
.report-result-table td {
  vertical-align: middle !important;
  border-top: 1px solid var(--tm-line, #dce6f2) !important;
  padding: 14px 16px !important;
}

.report-result-table th {
  width: 32%;
  color: var(--tm-muted, #718096);
  font-size: 13px;
  font-weight: 900;
  letter-spacing: .02em;
}

.report-result-table td {
  color: var(--tm-ink, #18243c);
}

.report-row-title {
  display: block;
  color: var(--tm-ink, #18243c);
}

.report-row-hint {
  display: block;
  margin-top: 4px;
  color: var(--tm-muted, #718096);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: none;
}

.report-result-table tr[onclick],
.report-result-table tr[onClick] {
  cursor: pointer;
}

.report-result-table tr.report-clickable-row {
  background:
    linear-gradient(90deg, rgba(var(--tm-brand-rgb, 23, 105, 194), .055), rgba(255,255,255,0) 58%),
    #fff;
}

.report-result-table tr.report-clickable-row:hover {
  transform: translateY(-1px);
  box-shadow: inset 4px 0 0 var(--tm-brand, #1769c2), 0 10px 22px rgba(24, 36, 60, .08);
}

.report-result-table tr.report-clickable-row td:after {
  content: "Open";
  display: none;
  float: right;
  margin-left: 12px;
  padding: 5px 10px;
  border-radius: 999px;
  color: #fff;
  background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-brand-dark, #0c315f));
  font-size: 11px;
  font-weight: 900;
  line-height: 1;
}

.report-has-data .report-result-table tr.report-clickable-row td:after {
  display: inline-block;
}

.report-result-table tr.report-deposit-row {
  background:
    linear-gradient(90deg, rgba(31, 157, 112, .1), rgba(255,255,255,0) 58%),
    #fff;
}

.report-result-table tr.report-deposit-row:hover {
  box-shadow: inset 4px 0 0 #1f9d70, 0 10px 22px rgba(24, 36, 60, .08);
}

.report-result-table tr.report-deposit-row td:after {
  background: linear-gradient(135deg, #1f9d70, #13734f);
}

.report-result-table tr.report-expense-row {
  background:
    linear-gradient(90deg, rgba(229, 72, 77, .09), rgba(255,255,255,0) 58%),
    #fff;
}

.report-result-table tr.report-expense-row:hover {
  box-shadow: inset 4px 0 0 #e5484d, 0 10px 22px rgba(24, 36, 60, .08);
}

.report-result-table tr.report-expense-row td:after {
  background: linear-gradient(135deg, #e5484d, #a32635);
}

.blackCSS {
  color: var(--tm-ink, #18243c);
  font-size: 18px;
}

.textcenter {
  font-weight: 900;
}

#deposit,
#expense,
#MyfinalDeposit,
#MyFinalExpenses,
#expense_nagad,
#expense_kisanvahi {
  color: var(--tm-brand-dark, #0c315f);
  min-height: 48px;
  font-size: 17px;
  line-height: 1.4;
}

#deposit:empty:before,
#expense:empty:before,
#MyfinalDeposit:empty:before,
#MyFinalExpenses:empty:before,
#expense_nagad:empty:before,
#expense_kisanvahi:empty:before {
  content: "Search account to load value";
  color: var(--tm-muted, #718096);
  font-size: 13px;
  font-weight: 700;
}

#account_holder {
  width: 20px;
  height: 20px;
  accent-color: var(--tm-brand, #1769c2);
}

#account_holder_text {
  display: inline-block;
  margin-left: 8px;
  font-weight: 800;
}

.autocomplete {
  position: relative;
  display: block;
}

.autocomplete-items {
  position: absolute;
  z-index: 99;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  max-height: 240px;
  overflow: auto;
  border: 1px solid var(--tm-line, #dce6f2);
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 16px 34px rgba(24, 36, 60, .14);
}

.autocomplete-items div {
  padding: 10px 12px;
  cursor: pointer;
  border-bottom: 1px solid var(--tm-line, #dce6f2);
  background-color: #fff;
}

.autocomplete-items div:hover,
.autocomplete-active {
  color: var(--tm-brand-dark, #0c315f) !important;
  background: var(--tm-brand-soft, #eaf3ff) !important;
}

.runmytable_wrapper,
.dataTables_wrapper {
  overflow-x: auto;
  width: 100%;
}

.report-search-page .modal-dialog.modal-lg {
  max-width: min(1120px, calc(100% - 28px)) !important;
}

.report-search-page .modal-content {
  border: 0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 24px 70px rgba(24, 36, 60, .22);
}

.report-search-page .modal-body {
  padding: 18px;
}

.report-search-page .modal-body h2 {
  margin: 0 0 14px;
  color: var(--tm-ink, #18243c);
  font-size: 20px;
  font-weight: 900;
}

.report-search-page .modal table {
  min-width: 780px;
}

.report-search-page .modal-footer {
  border-top: 1px solid var(--tm-line, #dce6f2);
  background: #f8fbff;
}

@media (max-width: 991px) {
  .report-search-grid {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: stretch;
  }

  .report-action {
    height: 44px;
  }
}

@media (max-width: 767px) {
  .report-page-title {
    padding: 15px 14px 15px 56px;
    font-size: 20px;
  }

  .report-search-card {
    padding: 14px !important;
  }

  .report-panel {
    margin-bottom: 14px !important;
  }

  .report-kisan-summary,
  .report-result-table {
    display: block;
    width: 100%;
    overflow-x: auto;
    white-space: nowrap;
  }

  .report-search-grid {
    grid-template-columns: 1fr;
  }

  .report-summary-field {
    grid-column: auto;
  }

  .report-button-field .report-action {
    transform: none;
  }

  .report-action {
    width: 100%;
  }

  .report-kisan-summary {
    border: 0;
    background: transparent;
    overflow: visible;
    white-space: normal;
  }

  .report-kisan-summary thead {
    display: none;
  }

  .report-kisan-summary tbody tr {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .report-kisan-summary tbody th {
    justify-content: flex-start;
    min-height: 42px;
    border: 1px solid var(--tm-line, #dce6f2) !important;
    border-radius: 8px;
    background: #fff;
  }

  .report-result-table th,
  .report-result-table td {
    display: block;
    width: 100%;
    padding: 10px 12px !important;
  }

  .report-result-table th {
    padding-bottom: 2px !important;
    border-top: 1px solid var(--tm-line, #dce6f2) !important;
  }

  .report-result-table td {
    padding-top: 2px !important;
    border-top: 0 !important;
    white-space: normal;
  }

  .blackCSS {
    font-size: 16px;
  }
}
</style>
<meta charset="UTF-8">
<main class="main-content bgc-grey-100 report-search-page">

                <div id="mainContent">
                    <div class="container-fluid report-shell" >
                        <h4 class="c-grey-900 mT-10 mB-30 report-page-title">Search Report</h4>
                        <div class="row">
                        <div class="col-md-12">
                                <div class="bgc-white bd bdrs-3 p-20 mB-20 report-panel report-search-card">
                                <div>
                                <div class="form-row report-search-grid">
                                           
                                           <div class="form-group col-md-4 report-field">
                                               <label for="inputEmail4">Search Name *</label>
                                              <?php  
                                                   $name = @$result->search_name;
                                                   $postvalue = @$_SESSION['search_name'];
                                                   // echo $postvalue; die;
                                                   echo form_input(array('autocomplete'=>'off','id'=>'myInput','name' => 'search_name', 'data-acc-picker'=>'1', 'data-acc-nocreate'=>'1', 'class' => 'form-control', 'placeholder' => 'Search an account', 'value' => !empty($postvalue) ? $postvalue : $name )); ?>
                                              <label  class="error"><div class="help-block" style="color:red"> <?php echo form_error('search_name'); ?></div></label>
                                           </div>
                                           <div class="form-group col-md-1 report-field report-button-field">
                                                   <button type="submit" class="btn btn-primary report-action" id="search"><i class="ti-search"></i> Find</button>
                                                    
                                           </div> 
                                           <style>
                                           .selectSetting{
                                             width:inherit;
                                           }
                                           .optionval{
                                            font-size: 18px;
                                            font-weight: 700;
                                            padding: 5px;
                                            margin-left: 10px;
                                           }
                                           </style>
                                           <div class="form-group col-md-7 report-field report-summary-field">
                                           <table class="table report-kisan-summary">
  <thead>
	<?php 
  // KV summary panel. Reaching this page already requires 'report' view via
  // RBAC, so this mirrors that instead of the retired aa_white_listing check.
  $white_list_ID = (!function_exists('erp_current_user_can')) || erp_current_user_can('report', 'view');
  ?>

  <?php 
  if($white_list_ID){
  if(get_logical_data()->status){?>
    <tr style="text-align:center">
      <th scope="col">Count</th>
      <th scope="col">Amount</th>
      <th scope="col">Quantity</th>
      <th scope="col">Paid Amount In AC</th>
      <!-- <th scope="col">UTR Quantity</th> -->
      <!-- <th scope="col">UTR Count</th> -->
      <th scope="col">Current नाम</th>
    </tr>
    <?php } ?>
  </thead>
  <tbody style="text-align:center">
 
    <tr>
      <th scope="row" id="mykisanvahicount"></th>
      <th scope="row" id="mykisanvahiamount"></th>
      <th scope="row" id="mykisanvahiquantity"></th>
      <th scope="row" id="kisanvahiUTRNo"></th>
      <!-- <th scope="row" id="kisanvahiUTRNoQuantity"></th> -->
      <!-- <th scope="row" id="kisanvahiUTRNoCount"></th> -->
      <th scope="row" id="kisanvahiUTRNaam"></th>
    </tr>
   
  </tbody>
    <?php } ?>

</table>
                                           </div> 
                                           <div class="form-group col-md-4 hide report-field">
                                               <label for="inputEmail4">Send Detail To Mobile Number *</label>
                                              <?php  
                                                   $name = @$result->mobile_no;
                                                   $postvalue = @$_SESSION['mobile_no'];
                                                   // echo $postvalue; die;
                                                   echo form_input(array('autocomplete'=>'off','id'=>'mobile_no','name' => 'mobile_number', 'maxlength'=>'25', 'class' => 'form-control', 'placeholder' => 'Mobile Number', 'value' => !empty($postvalue) ? $postvalue : $name )); ?>
                                              <label  class="error"><div class="help-block" style="color:red"> <?php echo form_error('search_name'); ?></div></label>
                                           </div>
                                           <div class="form-group col-md-1 hide report-field">
                                                   <button type="submit" class="btn btn-primary report-action" id="whatsAppSend"><i class="ti-mobile"></i> Send</button>
                                                    
                                           </div> 
                                           
                                       </div>
                                       </div>
                                </div>
                                </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="bgc-white bd bdrs-3 p-20 mB-20 report-panel report-results-panel">
                               <!-- <div> <a target="_blank" href="<?php echo base_url().'uploads/invoice_slips/'.$users->invoice_name;?>" id="back-btn" class="btn cur-p btn-primary pull-right"><i class="fa fa-download"></i></a></div> -->
                                    <table class="table report-result-table">
                                        <tbody>
                                            <tr>
                                                <th  class="table_bg" scope="row"><span class="report-row-title">नगद नाम</span></th>
                                                <td id="expense_nagad" class="blackCSS textcenter"></td>
                                            </tr>
                                            <?php 
                                            if($white_list_ID){
                                            if(get_logical_data()->status){?>
                                            <tr>
                                                <th  class="table_bg" scope="row"><span class="report-row-title">किसान वही नाम</span></th>
                                                <td id="expense_kisanvahi" class="blackCSS textcenter"></td>
                                            </tr>
                                            <?php }} ?>
                                            <tr class="report-clickable-row report-expense-row" onClick="return openRokadModal('expenses', event)" data-toggle="modal" data-target="#ExpensesmyModal">
                                                <th  class="table_bg" scope="row"><span class="report-row-title">टोटल नाम</span><small class="report-row-hint">Click to view expense details</small></th>
                                                <td id="expense" class="blackCSS textcenter"></td>
                                            </tr>
                                            <tr class="report-clickable-row report-deposit-row" onClick="return openRokadModal('deposit', event)" data-toggle="modal" data-target="#DepositmyModal">
                                                <th class="table_bg" scope="row"><span class="report-row-title">जमा</span><small class="report-row-hint">Click to view deposit details</small></th>
                                                <td id="deposit" class="blackCSS textcenter"></td>

                                            </tr>
                                            <tr>
                                                <th class="table_bg" scope="row"><span class="report-row-title">शेष जमा</span></th>
                                                <td id="MyfinalDeposit" class="blackCSS textcenter"></td>

                                            </tr>
                                            <tr>
                                                <th class="table_bg" scope="row"><span class="report-row-title">शेष नाम</span></th>
                                                <td id="MyFinalExpenses" class="blackCSS textcenter"></td>

                                            </tr>
                                            <?php  if($white_list_ID){
                                              if(get_logical_data()->status){?>
                                            <tr class="report-clickable-row" onClick="return openRokadModal('kisanvahi', event)" data-toggle="modal" data-target="#myModal">
                                                <th class="table_bg" scope="row" ><span class="report-row-title">किसान संख्या</span><small class="report-row-hint">Click to view KisanVahi details</small></th>
                                              
                                                <td id="mykisanvahicount">   
                                                  <button class="btn btn-primary">Show</button>
                                                </td>

                                            </tr>
                                            <tr>
                                                <th class="table_bg" scope="row"><span class="report-row-title">Total Reg Kisan</span></th>
                                                <td id="total_reg" class="blackCSS textcenter"></td>

                                            </tr>
                                            <tr>
                                                <th class="table_bg" scope="row"><span class="report-row-title">Is Account Holder Is Kisan</span></th>
                                                <td id="total_reg" class="blackCSS textcenter">
                                                <input type="checkbox" id="account_holder" name="account_holder" onClick="isUserIsKisanVahi()">
                                                <span style="color:green" id="account_holder_text"></span>
                                                </td>

                                            </tr>
                                            <tr>
                                                <th class="table_bg" scope="row"><span class="report-row-title">Account Detail</span></th>
                                                <td id="acn_detail" class="blackCSS textcenter">
                                                </td>

                                            </tr>
                                            <?php } }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
<style>
.modal-lg {
    max-width: 90% !important;
}
</style>

              <!-- Modal -->
              <div class="modal fade" id="myModal" role="dialog">
              <div class="modal-dialog modal-lg">

                <!-- Modal content-->
                <div class="modal-content">
                  
                  <div class="modal-body">
                  <div ">
                      <h2>Information About Kisan</h2>
                      <table class="table" id="runmytable">
                        <thead>
                          <tr id="overLapData">
                           
                          </tr>
                        </thead>
                        <tbody id="getData">
                          
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
                </div>
                
              </div>
              </div>

              <div class="modal fade" id="DepositmyModal" role="dialog">
              <div class="modal-dialog modal-lg">

                <!-- Modal content-->
                <div class="modal-content">
                  
                  <div class="modal-body">
                  <div >
                      <h2>Information About Deposit Value</h2>
                      <table class="table" id="Depositrunmytable">
                        <thead>
                          <tr id="DepositoverLapData">
                           
                          </tr>
                        </thead>
                        <tbody id="DepositgetData">
                          
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
                </div>
                
              </div>
              </div>


              <div class="modal fade" id="ExpensesmyModal" role="dialog">
              <div class="modal-dialog modal-lg">

                <!-- Modal content-->
                <div class="modal-content">
                  
                  <div class="modal-body">
                  <div class="">
                      <h2>Information About Kisan</h2>
                      <table class="table" id="Expensesrunmytable">
                        <thead>
                          <tr id="ExpensesoverLapData">
                           
                          </tr>
                        </thead>
                        <tbody id="ExpensesgetData">
                          
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
                </div>
                
              </div>
              </div>

  
              <!-- Media / source detail viewer -->
              <div class="rk-overlay" id="rokadMediaModal">
                <div class="rk-modal">
                  <div class="rk-head">
                    <h5>Entry Details</h5>
                    <button type="button" class="rk-close" onclick="closeRokadMedia()">&times;</button>
                  </div>
                  <div class="rk-body" id="rokadMediaBody"></div>
                </div>
              </div>

              <style>
                .rk-src { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:900; padding:2px 9px; border-radius:20px; white-space:nowrap; }
                .rk-src-web { background:#e6f0fb; color:#1769c2; }
                .rk-src-app { background:#e8f7ee; color:#1f9d70; }
                .rk-clip { font-size:13px; color:#1769c2; }
                .report-search-page .modal table tr.rk-entry-row { cursor:pointer; transition:background .15s ease; }
                .report-search-page .modal table tr.rk-entry-row:hover { background:#f1f7ff; }
                .rk-view-btn { background:linear-gradient(135deg,#1769c2,#0c315f); color:#fff !important; border:0; border-radius:6px; font-weight:800; font-size:11px; padding:5px 12px; }
                .rk-view-btn:hover { color:#fff; opacity:.92; }

                .rk-overlay { position:fixed; inset:0; z-index:20000; display:none; align-items:flex-start; justify-content:center; padding:40px 16px; background:rgba(15,23,42,.55); overflow:auto; }
                .rk-overlay.show { display:flex; }
                .rk-modal { width:640px; max-width:100%; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 30px 80px rgba(15,23,42,.4); animation:rkPop .18s ease; }
                @keyframes rkPop { from { transform:translateY(12px); opacity:0; } to { transform:translateY(0); opacity:1; } }
                .rk-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:linear-gradient(135deg,#1769c2,#0c315f); color:#fff; }
                .rk-head h5 { margin:0; font-size:16px; font-weight:900; color:#fff; }
                .rk-close { background:transparent; border:0; color:#fff; font-size:26px; line-height:1; cursor:pointer; }
                .rk-body { padding:18px; }
                .rk-grid { display:grid; grid-template-columns:130px 1fr; gap:8px 14px; margin-bottom:8px; }
                .rk-k { font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:.03em; color:#718096; align-self:center; }
                .rk-v { font-size:14px; font-weight:700; color:#18243c; }
                .rk-media-wrap { margin-top:8px; border-top:1px dashed #dce6f2; padding-top:14px; }
                .rk-media { margin-bottom:16px; }
                .rk-media label { display:block; font-size:11px; font-weight:900; text-transform:uppercase; color:#718096; margin-bottom:6px; }
                .rk-media img { max-width:100%; border-radius:8px; border:1px solid #dce6f2; }
                .rk-media audio, .rk-media video { width:100%; border-radius:8px; }

                /* loading / status states for the entry popups */
                .rk-spinner { display:inline-block; width:22px; height:22px; border:3px solid #d6e4f5; border-top-color:#1769c2; border-radius:50%; animation:rkSpin .7s linear infinite; vertical-align:middle; }
                @keyframes rkSpin { to { transform:rotate(360deg); } }
                .rk-load-cell { text-align:center !important; padding:34px 12px !important; color:#516174; font-weight:700; }
                .rk-load-cell .rk-load-text { display:block; margin-top:12px; font-size:13px; color:#718096; }
                .rk-load-cell.rk-error { color:#e5484d; }
                /* button busy state */
                .report-action.is-loading { opacity:.75; pointer-events:none; }
                .report-action .rk-btn-spin { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.5); border-top-color:#fff; border-radius:50%; animation:rkSpin .7s linear infinite; }
              </style>

              <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
              <link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/buttons/1.4.0/css/buttons.dataTables.min.css" />

<!-- jQuery is already loaded by the layout; reloading it here wiped plugin bindings. Removed. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<!-- pdfmake core from cdnjs (cdn.rawgit.com was permanently shut down and 404s). -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.print.min.js"></script>
<script src="//cdn.datatables.net/plug-ins/1.10.22/api/sum().js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>



            <script>


            $('#whatsAppSend').click(()=>{
              whatsAppSend();
            })

            function whatsAppSend(){
             // console.log($('#mobile_no').val());
              $.ajax({
              url: "<?php echo base_url(); ?>admin/dashboard/sendWhatsapp",
              type: "POST",
              dataType: 'json',
              data:{
                'mobile_no':$('#mobile_no').val(), 
                'totalExpenses':totalExpenses, 
                'deposit':$('#deposit').text(), 
                'MyfinalDeposit':$('#MyfinalDeposit').text(),
               'MyFinalExpenses':$('#MyFinalExpenses').text(),
               'mykisanvahicount':$('#mykisanvahicount').text()
                },
              success: function (a) {
                console.log("**************************",a)
                  }
              });
            }

/* ---------- shared rokad entry / media viewer ---------- */
var rokadMediaStore = {};

function rkEsc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

function rokadSourceBadge(src) {
  var isApp = String(src || '').toLowerCase() === 'app';
  return isApp
    ? '<span class="rk-src rk-src-app"><i class="ti-mobile"></i> App</span>'
    : '<span class="rk-src rk-src-web"><i class="ti-desktop"></i> Web</span>';
}

function buildRokadRow(d, idx) {
  rokadMediaStore[d.rokad_id] = d;
  var hasMedia = d.image_url || d.voice_url || d.video_url;
  var clip = hasMedia ? ' <span class="rk-clip" title="Has attachment">&#128206;</span>' : '';
  var remark = (d.remark && String(d.remark).trim() !== '') ? rkEsc(d.remark) : '<span class="text-muted">-</span>';
  return '<tr class="rk-entry-row" onclick="showRokadMedia(\'' + rkEsc(d.rokad_id) + '\')">' +
    '<td>' + (idx + 1) + '</td>' +
    '<td>' + rkEsc(d.rokad_id) + '</td>' +
    '<td>' + rkEsc(d.rokad_date) + '</td>' +
    '<td>' + rkEsc(d.account_name) + clip + '</td>' +
    '<td>' + rkEsc(d.party_invoice_no) + ' / ' + rkEsc(d.quantity) + '</td>' +
    '<td>' + rkEsc(d.karch_amount) + '</td>' +
    '<td>' + rokadSourceBadge(d.source_label) + '</td>' +
    '<td>' + rkEsc(d.added_by_name || '-') + '</td>' +
    '<td>' + rkEsc(d.added_on || '-') + '</td>' +
    '<td>' + remark + '</td>' +
    '<td><button type="button" class="btn rk-view-btn">View</button></td>' +
    '</tr>';
}

function rkRow(k, v) { return '<div class="rk-k">' + k + '</div><div class="rk-v">' + v + '</div>'; }

/* ---------- export (copy / csv / excel / pdf / print) formatting ---------- */
// Strip the cell markup so exports carry clean text: no badge HTML, no clip
// emoji, no "View" button. The trailing "Media" column is dropped entirely.
function rokadExportOptions() {
  return {
    columns: ':not(:last-child)',
    format: {
      body: function (data) {
        var s = (data == null ? '' : String(data));
        s = s.replace(/<[^>]*>/g, '');        // remove all tags (badges, buttons, spans)
        s = s.replace(/📎/g, '');   // remove the 📎 attachment clip
        s = s.replace(/ /g, ' ').replace(/\s+/g, ' ').trim();
        return s;
      }
    }
  };
}

// Plain text export for tables without media/badges (e.g. KisanVahi):
// strip any stray HTML but keep every column.
function plainExportOptions() {
  return {
    format: {
      body: function (data) {
        var s = (data == null ? '' : String(data));
        return s.replace(/<[^>]*>/g, '').replace(/📎/g, '').replace(/\s+/g, ' ').trim();
      }
    }
  };
}

function rokadExportButtons(title, opts) {
  opts = opts || rokadExportOptions();
  return [
    { extend: 'copy',  title: title, exportOptions: opts },
    { extend: 'csv',   title: title, filename: title, exportOptions: opts },
    { extend: 'excel', title: title, filename: title, exportOptions: opts },
    { extend: 'pdf',   title: title, filename: title, orientation: 'landscape', pageSize: 'A4',
      exportOptions: opts, customize: function (doc) { processDoc(doc); } },
    { extend: 'print', title: title, exportOptions: opts }
  ];
}

// Build a readable export title from the selected account name.
function rokadExportTitle(suffix) {
  var acct = ($('#myInput').val() || '').split('_')[0].trim();
  return (acct ? acct + ' - ' : '') + suffix;
}

function showRokadMedia(id) {
  var d = rokadMediaStore[id];
  if (!d) { return; }

  var g = '';
  g += rkRow('Entry ID', rkEsc(d.rokad_id));
  g += rkRow('Date', rkEsc(d.rokad_date));
  g += rkRow('Party', rkEsc(d.account_name));
  g += rkRow('Invoice / Qnt', rkEsc(d.party_invoice_no) + ' / ' + rkEsc(d.quantity));
  g += rkRow('Amount', '&#8377; ' + rkEsc(d.karch_amount));
  g += rkRow('Source', rokadSourceBadge(d.source_label));
  g += rkRow('Added By', rkEsc(d.added_by_name || '-'));
  g += rkRow('Added On', rkEsc(d.added_on || '-'));
  g += rkRow('IP Address', d.ip ? rkEsc(d.ip) : '<span class="text-muted">Not captured</span>');
  if (d.lat && d.lng) {
    g += rkRow('Location', '<a href="https://www.google.com/maps?q=' + encodeURIComponent(d.lat + ',' + d.lng) + '" target="_blank" rel="noopener"><i class="ti-location-pin"></i> ' + rkEsc(d.lat) + ', ' + rkEsc(d.lng) + '</a>');
  } else {
    g += rkRow('Location', '<span class="text-muted">Not captured</span>');
  }
  g += rkRow('Remark', (d.remark && String(d.remark).trim() !== '') ? rkEsc(d.remark) : '<span class="text-muted">No remark</span>');

  var m = '';
  if (d.image_url) m += '<div class="rk-media"><label>Image</label><a href="' + d.image_url + '" target="_blank"><img src="' + d.image_url + '" alt="image"></a></div>';
  if (d.voice_url) m += '<div class="rk-media"><label>Voice Recording</label><audio controls preload="none" src="' + d.voice_url + '"></audio></div>';
  if (d.video_url) m += '<div class="rk-media"><label>Video Recording</label><video controls preload="none" src="' + d.video_url + '"></video></div>';
  if (!m) m = '<div class="text-muted" style="font-size:12px;">No attachments for this entry.</div>';

  document.getElementById('rokadMediaBody').innerHTML =
    '<div class="rk-grid">' + g + '</div><div class="rk-media-wrap">' + m + '</div>';
  document.getElementById('rokadMediaModal').classList.add('show');
}

function closeRokadMedia() {
  var ov = document.getElementById('rokadMediaModal');
  ov.classList.remove('show');
  setTimeout(function () { document.getElementById('rokadMediaBody').innerHTML = ''; }, 250);
}

$(function () {
  var ov = document.getElementById('rokadMediaModal');
  if (ov) {
    ov.addEventListener('click', function (e) { if (e.target === this) closeRokadMedia(); });
  }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeRokadMedia(); });
});

function resetReportTable(tableSelector, headerSelector, bodySelector) {
  if ($.fn.DataTable && $.fn.DataTable.isDataTable(tableSelector)) {
    $(tableSelector).DataTable().clear().destroy();
  }

  $(headerSelector).empty();
  $(bodySelector).empty();
}

function hasReportSearchValue(selector) {
  var searchName = $.trim($('#myInput').val() || '');
  var value = $.trim($(selector).text() || '');

  return searchName !== '' && value !== '' && value !== '0';
}

/* ============================================================
 * Unified entry-list modal module — drives all three popups
 * (Deposit, Expense, KisanVahi) through one loader.
 *
 * Every open performs a HARD RESET (destroy any DataTable + clear
 * the header row and body) before rebuilding from the currently
 * searched account. This is what fixes the old bug where opening a
 * popup after a second search duplicated the columns and threw
 * "Cannot reinitialise DataTable".
 * ========================================================== */

// Row builder for the KisanVahi popup (plain rows, no media).
function buildKisanRow(d, idx) {
  var less = (parseFloat((d.Ammount || 0) - (d.paid_amount || 0))).toFixed(2);
  return '<tr>' +
    '<td>' + (idx + 1) + '</td>' +
    '<td>' + rkEsc(d.Farmer_ID) + '</td>' +
    '<td>' + rkEsc(d.Farmer_name) + '</td>' +
    '<td>' + rkEsc(d.Quantity) + '</td>' +
    '<td>' + rkEsc(d.Ammount) + '</td>' +
    '<td>' + rkEsc(d.paid_amount) + '</td>' +
    '<td>' + less + '</td>' +
    '<td>' + rkEsc(d.Purchase_Date) + '</td>' +
    '<td>' + rkEsc(d.name) + '</td>' +
    '</tr>';
}

var ROKAD_MODALS = {
  deposit: {
    url: "<?php echo base_url(); ?>admin/report/ListmytotalDeposit",
    tableId: '#Depositrunmytable', theadId: '#DepositoverLapData', tbodyId: '#DepositgetData',
    valueSelector: '#deposit', notFoundMsg: 'Please search account first',
    titleSuffix: 'Deposit (जमा)',
    headers: ['Sno', 'Rokadh ID', 'Rokadh Date', 'Name', 'Invoice No / Qnt', 'Amount', 'Source', 'Added By', 'Added On', 'Remark', 'Media'],
    rowBuilder: buildRokadRow, kind: 'rokad'
  },
  expenses: {
    url: "<?php echo base_url(); ?>admin/report/ListmytotalExpenses",
    tableId: '#Expensesrunmytable', theadId: '#ExpensesoverLapData', tbodyId: '#ExpensesgetData',
    valueSelector: '#expense', notFoundMsg: 'Please search account first',
    titleSuffix: 'Expense (नाम)',
    headers: ['Sno', 'Rokadh ID', 'Rokadh Date', 'Name', 'Invoice / Qnt', 'Amount', 'Source', 'Added By', 'Added On', 'Remark', 'Media'],
    rowBuilder: buildRokadRow, kind: 'rokad'
  },
  kisanvahi: {
    url: "<?php echo base_url(); ?>admin/report/Listmytotalkisanvahi",
    tableId: '#runmytable', theadId: '#overLapData', tbodyId: '#getData',
    valueSelector: '#mykisanvahicount', notFoundMsg: 'There is not Kisan Vahi',
    titleSuffix: 'KisanVahi',
    headers: ['Sno', 'Farmer ID', 'Farmer Name', 'Quantity', 'Amount', 'Paid Amount', 'Less Amount', 'Date', 'Center Name'],
    rowBuilder: buildKisanRow, kind: 'kisan'
  }
};

function openRokadModal(typeKey, event) {
  var cfg = ROKAD_MODALS[typeKey];
  if (!cfg) { return true; }

  // Guard: an account must be searched and the relevant value must be non-zero.
  if (!hasReportSearchValue(cfg.valueSelector)) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    alert(cfg.notFoundMsg);
    return false;
  }

  // Hard reset (destroy DataTable + clear header/body) — fixes re-search bugs.
  resetReportTable(cfg.tableId, cfg.theadId, cfg.tbodyId);

  var head = '';
  for (var h = 0; h < cfg.headers.length; h++) { head += '<th>' + cfg.headers[h] + '</th>'; }
  $(cfg.theadId).html(head);

  // Show an immediate "fetching" state so the user knows data is loading.
  $(cfg.tbodyId).html(rokadStatusRow(cfg.headers.length, 'spinner', 'Fetching data…'));

  $.ajax({
    url: cfg.url, type: 'POST', dataType: 'json',
    data: { 'search_name': $('#myInput').val() },
    success: function (a) {
      a = a || [];
      if (!a.length) {
        $(cfg.tbodyId).html(rokadStatusRow(cfg.headers.length, 'empty', 'No records found.'));
        return;
      }
      var rows = '';
      for (var i = 0; i < a.length; i++) { rows += cfg.rowBuilder(a[i], i); }
      $(cfg.tbodyId).html(rows);
      initRokadDataTable(cfg);
    },
    error: function () {
      $(cfg.tbodyId).html(rokadStatusRow(cfg.headers.length, 'error', 'Could not load data. Please try again.'));
    }
  });

  return true;
}

// A full-width status row (loading / empty / error) for the entry popups.
function rokadStatusRow(colCount, state, text) {
  var inner;
  if (state === 'spinner') {
    inner = '<span class="rk-spinner"></span><span class="rk-load-text">' + rkEsc(text) + '</span>';
  } else {
    inner = '<span class="rk-load-text">' + rkEsc(text) + '</span>';
  }
  var cls = 'rk-load-cell' + (state === 'error' ? ' rk-error' : '');
  return '<tr><td class="' + cls + '" colspan="' + colCount + '">' + inner + '</td></tr>';
}

function initRokadDataTable(cfg) {
  setTimeout(function () {
    var isKisan = (cfg.kind === 'kisan');
    var opts = {
      dom: isKisan ? 'Blfrtip' : 'Bfrtip',
      destroy: true,
      buttons: rokadExportButtons(
        rokadExportTitle(cfg.titleSuffix),
        isKisan ? plainExportOptions() : rokadExportOptions()
      )
    };
    if (isKisan) {
      opts.language = { "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Hindi.json" };
      opts.drawCallback = function () {
        var api = this.api();
        $(api.table().footer()).html(api.column(4, { page: 'current' }).data().sum());
      };
    }
    $(cfg.tableId).DataTable(opts);
  }, 0);
}

function processDoc(doc) {
    pdfMake.fonts = {
      Roboto: {
        normal: 'Roboto-Regular.ttf',
        bold: 'Roboto-Medium.ttf',
        italics: 'Roboto-Italic.ttf',
        bolditalics: 'Roboto-MediumItalic.ttf'
      },
      arial: {
        normal: 'arial.ttf',
        bold: 'arial.ttf',
        italics: 'arial.ttf',
        bolditalics: 'arial.ttf'
      }
    };
    // modify the PDF to use a different default font:
    doc.defaultStyle.font = "arial";
    var i = 1;
  }

function isUserIsKisanVahi(){
  if($('#myInput').val() == 0){
    alert('Account Holder is not selected')
    $("#account_holder").prop("checked", false);
    return
  }
  $('#account_holder_text').text('')
  $.ajax({
        url: "<?php echo base_url(); ?>admin/report/convertUserIsKisan",
        type: "POST",
        dataType: 'json',
        data:{'search_name':$('#myInput').val(), 'account_holder':$('#account_holder').prop("checked")},
        success: function (a) {
          console.log("**************************",a)
          $('#account_holder_text').text('Updated Successfully!!!')
        },
        error: function () {
            alert("Error");
        }
        });


}
/* myFunction_deposit / myFunction_expenses were replaced by the unified
   openRokadModal('deposit' | 'expenses', event) loader above. */

function unMapMyID(x){
 // alert(x)
 jConfirm('Continue?', 'Are You Sure! You want to Unmap ', function (ans) {
   console.log(ans)
          if (ans){
            jAlert("No")
          }else{
            confirmMyUnmap();
          }
                });
}

function confirmMyUnmap(){
  
  $.ajax({
        url: "<?php echo base_url(); ?>admin/report/unmapkisanVahi",
        type: "POST",
        dataType: 'json',
        data:{'search_name':x},
        success: function (a) {
          console.log("**************************",a)
          window.reload();
          //myFunction();
        },
        error: function () {
            alert("Error");
        }
        });
}
function autocomplete(inp, arr) {
   
   var arr;
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
    $.ajax({
        url: "<?php echo base_url(); ?>admin/billing/account_options",
        type: "POST",
        dataType: 'json',
        success: function (a) {
            arr = a
        },
        error: function () {
            alert("Error");
        }
        });
        console.log(arr)
      var a, b, i, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);
      /*for each item in the array...*/
      for (i = 0; i < arr.length; i++) {
        /*check if the item starts with the same letters as the text field value:*/
        if (arr[i].name.substr(0, val.length).toUpperCase() == val.toUpperCase()) {
          /*create a DIV element for each matching element:*/
          b = document.createElement("DIV");
          /*make the matching letters bold:*/
          b.innerHTML = "<strong>" + arr[i].name.substr(0, val.length) + "</strong>";
          b.innerHTML += arr[i].name.substr(val.length);
          /*insert a input field that will hold the current array item's value:*/
          b.innerHTML += "<input type='hidden' value='" + arr[i].name + '_' +arr[i].account_id +"'>";
          /*execute a function when someone clicks on the item value (DIV element):*/
          b.addEventListener("click", function(e) {
              /*insert the value for the autocomplete text field:*/
              inp.value = this.getElementsByTagName("input")[0].value;
              /*close the list of autocompleted values,
              (or any other open lists of autocompleted values:*/
              closeAllLists();
          });
          a.appendChild(b);
        }
      }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
        x[i].parentNode.removeChild(x[i]);
      }
    }
  }
  /*execute a function when someone clicks in the document:*/
  document.addEventListener("keydown", function (e) {
      closeAllLists(e.target);
  });
}




/*An array containing all the country names in the world:*/
//var countries = ["Afghanistan","Albania","Algeria","Andorra","Angola","Anguilla","Antigua & Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia & Herzegovina","Botswana","Brazil","British Virgin Islands","Brunei","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Central Arfrican Republic","Chad","Chile","China","Colombia","Congo","Cook Islands","Costa Rica","Cote D Ivoire","Croatia","Cuba","Curacao","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Falkland Islands","Faroe Islands","Fiji","Finland","France","French Polynesia","French West Indies","Gabon","Gambia","Georgia","Germany","Ghana","Gibraltar","Greece","Greenland","Grenada","Guam","Guatemala","Guernsey","Guinea","Guinea Bissau","Guyana","Haiti","Honduras","Hong Kong","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Isle of Man","Israel","Italy","Jamaica","Japan","Jersey","Jordan","Kazakhstan","Kenya","Kiribati","Kosovo","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montenegro","Montserrat","Morocco","Mozambique","Myanmar","Namibia","Nauro","Nepal","Netherlands","Netherlands Antilles","New Caledonia","New Zealand","Nicaragua","Niger","Nigeria","North Korea","Norway","Oman","Pakistan","Palau","Palestine","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Poland","Portugal","Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Pierre & Miquelon","Samoa","San Marino","Sao Tome and Principe","Saudi Arabia","Senegal","Serbia","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Korea","South Sudan","Spain","Sri Lanka","St Kitts & Nevis","St Lucia","St Vincent","Sudan","Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Timor L'Este","Togo","Tonga","Trinidad & Tobago","Tunisia","Turkey","Turkmenistan","Turks & Caicos","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States of America","Uruguay","Uzbekistan","Vanuatu","Vatican City","Venezuela","Vietnam","Virgin Islands (US)","Yemen","Zambia","Zimbabwe"];

/*initiate the autocomplete function on the "myInput" element, and pass along the countries array as possible autocomplete values:*/
autocomplete(document.getElementById("myInput"));

function convertNumberToWords(amount) {
    var words = new Array();
    words[0] = '';
    words[1] = 'One';
    words[2] = 'Two';
    words[3] = 'Three';
    words[4] = 'Four';
    words[5] = 'Five';
    words[6] = 'Six';
    words[7] = 'Seven';
    words[8] = 'Eight';
    words[9] = 'Nine';
    words[10] = 'Ten';
    words[11] = 'Eleven';
    words[12] = 'Twelve';
    words[13] = 'Thirteen';
    words[14] = 'Fourteen';
    words[15] = 'Fifteen';
    words[16] = 'Sixteen';
    words[17] = 'Seventeen';
    words[18] = 'Eighteen';
    words[19] = 'Nineteen';
    words[20] = 'Twenty';
    words[30] = 'Thirty';
    words[40] = 'Forty';
    words[50] = 'Fifty';
    words[60] = 'Sixty';
    words[70] = 'Seventy';
    words[80] = 'Eighty';
    words[90] = 'Ninty';
    amount = amount.toString();
    var atemp = amount.split(".");
    var number = atemp[0].split(",").join("");
    var n_length = number.length;
    var words_string = "";
    if (n_length <= 9) {
        var n_array = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        var received_n_array = new Array();
        for (var i = 0; i < n_length; i++) {
            received_n_array[i] = number.substr(i, 1);
        }
        for (var i = 9 - n_length, j = 0; i < 9; i++, j++) {
            n_array[i] = received_n_array[j];
        }
        for (var i = 0, j = 1; i < 9; i++, j++) {
            if (i == 0 || i == 2 || i == 4 || i == 7) {
                if (n_array[i] == 1) {
                    n_array[j] = 10 + parseInt(n_array[j]);
                    n_array[i] = 0;
                }
            }
        }
        value = "";
        for (var i = 0; i < 9; i++) {
            if (i == 0 || i == 2 || i == 4 || i == 7) {
                value = n_array[i] * 10;
            } else {
                value = n_array[i];
            }
            if (value != 0) {
                words_string += words[value] + " ";
            }
            if ((i == 1 && value != 0) || (i == 0 && value != 0 && n_array[i + 1] == 0)) {
                words_string += "Crores ";
            }
            if ((i == 3 && value != 0) || (i == 2 && value != 0 && n_array[i + 1] == 0)) {
                words_string += "Lakhs ";
            }
            if ((i == 5 && value != 0) || (i == 4 && value != 0 && n_array[i + 1] == 0)) {
                words_string += "Thousand ";
            }
            if (i == 6 && value != 0 && (n_array[i + 1] != 0 && n_array[i + 2] != 0)) {
                words_string += "Hundred and ";
            } else if (i == 6 && value != 0) {
                words_string += "Hundred ";
            }
        }
        words_string = words_string.split("  ").join(" ");
    }
    return words_string;
}

var totalDeposit;
var totalExpenses;
var MyfinalDeposit;
var MyfinalExpenses;

$('#clickToShow').click(()=>{
      // alert("")
      hideMyData(1);
    });
$('#clickToHide').click(()=>{
      // alert("")
      hideMyData(0);
    });


    function hideMyData(status){
      if(status){
        $('.blackCSS').show();
       $('#clickToShow').hide();
        $('#clickToHide').show();
      }else{
        $('.blackCSS').hide();
       $('#clickToShow').show();
        $('#clickToHide').hide();
        
      }

    }
// Toggle the Find button between idle and "searching" states.
function setSearchLoading(on) {
  var $btn = $('#search');
  if (on) {
    $btn.data('label', $btn.html());
    $btn.addClass('is-loading').html('<span class="rk-btn-spin"></span> Searching…');
  } else {
    $btn.removeClass('is-loading').html($btn.data('label') || '<i class="ti-search"></i> Find');
  }
}

$('#search').click(()=>{
    setSearchLoading(true);
    $.ajax({
        url: "<?php echo base_url(); ?>admin/report/search",
        type: "POST",
        dataType: 'json',
        data:{'search_name':$('#myInput').val()},
        success: function (a) {
          $('#mainContent').addClass('report-has-data');
          console.log("************search**************",a);
          var abc = $('#myInput').val();
          abc = abc.split("_")[0]
          $('title').text(moment().format('DD-MM-YYYY')+"_"+abc);
          findMyExpenses(a);
          findMyDesposit(a);
          finalDeposit(a);
          finalExpenses(a);
          fetchsearchReport(a)
          mykisanvahi(a)
          $('#mykisanvahiamount').text(parseFloat(a.kisanvahi_Amount.Amount).toFixed(2))
          if(a.kisanvahi_Amount.is_Kisan){
            $("#account_holder").prop("checked", true);
          }else{
            $("#account_holder").prop("checked", false);
          }
          $('#mykisanvahiquantity').text((parseFloat(a.kisanvahi_Amount.Quantity).toFixed(2)));
          $('#kisanvahiUTRNo').text(parseFloat(a.UTR_Amount.Amount).toFixed(2));
          $('#kisanvahiUTRNoQuantity').text(parseFloat(a.UTR_Amount.Quantity).toFixed(2));
          $('#kisanvahiUTRNoCount').text(parseFloat(a.UTR_Amount.Count).toFixed(2));
          $('#kisanvahiUTRNoCount').text(parseFloat(a.UTR_Amount.Count).toFixed(2));
          $('#total_reg').text(a.totalMappedKisanVahi.kisan_id);
          $('#acn_detail').text(a.getAccountName.purchaser_account_no+'_'+a.getAccountName.bank_name+'_'+a.getAccountName.ifsc_code);
          setSearchLoading(false);
        },
        error: function () {
            setSearchLoading(false);
            alert("Error");
        }
        });
})
//mykisanvahicount
function mykisanvahi(a){
  console.log(a)
    $.ajax({
        url: "<?php echo base_url(); ?>admin/report/mytotalkisanvahi",
        type: "POST",
        dataType: 'json',
        data:{'search_name':$('#myInput').val()},
        success: function (a) {
          console.log("***********mytotalkisanvahi ***************",a)
          $('#mykisanvahicount').text(a.totalcount)
          var finalPayemnt =  parseInt( $('#mykisanvahiamount').text()) - parseInt( $('#kisanvahiUTRNo').text());
          $('#kisanvahiUTRNaam').text(finalPayemnt)
         
          
         
        },
        error: function () {    
            alert("Error");  
        }
        });
}
function changeFunc(val){
  var myFunction_expenese_var = 0;
  $('#myInput').val(($('#mySelect :selected').text()))
  document.getElementById('myInput').dispatchEvent(new Event('acc:resync'));
  $.ajax({
        url: "<?php echo base_url(); ?>admin/report/search",
        type: "POST",
        dataType: 'json',
        data:{'search_name':$('#myInput').val()},
        success: function (a) {
          $('#mainContent').addClass('report-has-data');
          console.log("************search**************",a)
         // alert("")
         
          findMyExpenses(a);
          findMyDesposit(a);
          finalDeposit(a);
          finalExpenses(a);
          fetchsearchReport(a)
        },
        error: function () {
            alert("Error");
        }
        });
}
function fetchsearchReport(){

    var selectElem = $("#mySelect");
    $('#mySelect').find('option').remove();
    $.ajax({
        url: "<?php echo base_url(); ?>admin/report/fetchsearchReports",
        type: "POST",
        dataType: 'json',
        data:{'search_name':$('#myInput').val()},
        success: function (a) {
          console.log("***********fetchsearchReports***************",a)
            // Iterate over object and add options to select
            $.each(a, function(index, value){
                $("<option/>", {
                    value: value.account_no,
                    text: value.name + '_' + value.account_no,
                    class:'optionval',
                   // 'onclick': reload(value.name + '_' + value.account_no)
                }).appendTo(selectElem);
            });
        },
        error: function () {
            alert("Error");
        }
        
})

}

 function findMyDesposit(val){
    totalDeposit = '';
    $('#deposit').text('');
   // $('#expense').text('');
    totalDeposit = parseInt(val.deposit.deposit);
    console.log('totalDeposit',totalDeposit)
    if(isNaN(totalDeposit)){
      $('#deposit').text('कोई जमा नही है')
    }else{
      $('#deposit').text(totalDeposit +' ₹/-  ' + convertNumberToWords(totalDeposit))
    }
  } 

  function findMyExpenses(val){
    totalExpenses = '';
   // $('#deposit').text('');
    $('#expense').text('');
    totalExpenses = parseInt(val.expenses.expenses);
    kisanvahiName = parseInt(val.expenses.kisanvahiName);
    jama = parseInt(val.expenses.jama);
    console.log('findMyExpenses',totalExpenses)
    if(isNaN(totalExpenses)){
      $('#expense').text('कोई नाम नही है')
    }else{
      $('#expense').text(totalExpenses  +' ₹/-  ' + convertNumberToWords(totalExpenses))
      $('#expense_nagad').text(jama  +' ₹/-  ' + convertNumberToWords(jama))
      $('#expense_kisanvahi').text(kisanvahiName  +' ₹/-  ' + convertNumberToWords(kisanvahiName))
    }
  } 

  function finalDeposit(val){
    console.log('valvalvalval',val.Finaldeposit)
    MyfinalDeposit = '';
    $('#MyfinalDeposit').text('');
    if(val.Finaldeposit){
    MyfinalDeposit = parseInt(val.Finaldeposit);
    $('#MyfinalDeposit').text(MyfinalDeposit  +' ₹/-  ' +convertNumberToWords(MyfinalDeposit));
    }else{
    $('#MyfinalDeposit').text('कोई जमा शेष नही है');
    }
  }  

   function finalExpenses(val){
    //console.log('valvalvalval',val)
    Finalexpenses = '';
    $('#MyFinalExpenses').text('');
    if(val.Finalexpenses){
      Finalexpenses = parseInt(val.Finalexpenses);
    $('#MyFinalExpenses').text(Finalexpenses  +' ₹/-  ' +convertNumberToWords(Finalexpenses));
    }else{
    $('#MyFinalExpenses').text('कोई नाम शेष नही है');

    }
   
  }     


//runmytable


</script>
