<style>
  * {
    box-sizing: border-box;
  }

  body {
    font: 16px Arial;
  }

  /*the container must be positioned relative:*/
  .autocomplete {
    position: relative;
    display: inline-block;
  }

  input {
    border: 1px solid transparent;
    background-color: #f1f1f1;
    padding: 10px;
    font-size: 16px;
  }

  input[type=text] {
    background-color: #f1f1f1;
    width: 100%;
  }

  input[type=submit] {
    background-color: DodgerBlue;
    color: #fff;
    cursor: pointer;
  }

  .autocomplete-items {
    position: absolute;
    border: 1px solid #d4d4d4;
    border-bottom: none;
    border-top: none;
    z-index: 99;
    /*position the autocomplete items to be the same width as the container:*/
    top: 100%;
    left: 0;
    right: 0;
    overflow-x: scroll;
    overflow-y: scroll;
    height: 200px;
    background: white
  }

  .autocomplete-items div {
    padding: 10px;
    cursor: pointer;
    background-color: #fff;
    border-bottom: 1px solid #d4d4d4;
  }

  /*when hovering an item:*/
  .autocomplete-items div:hover {
    background-color: #e9e9e9;
  }

  /*when navigating through the items using the arrow keys:*/
  .autocomplete-active {
    background-color: DodgerBlue !important;
    color: #ffffff;
  }
</style>
<main id="myclsid" class="main-content bgc-grey-100">
  <div id="mainContent">
    <div class="container-fluid">
      <!--<h4 class="c-grey-900 mT-10 mB-30"> List </h4>-->
      <div class="row">
        <div class="masonry-item col-md-12">
          <div class="bgc-white p-20 bd">
            <h6 class="c-grey-900">Add Document</h6>

            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
            <?php if (!empty($upload_error)) { ?>
              <div class="alert alert-danger"><?php echo $upload_error; ?></div>
            <?php } ?>
            <div class="mT-30">
              <?php echo form_open_multipart(current_url(), array('class' => '', 'id' => 'ciatyform_id', )); ?>

              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="inputState2">Doc Name *</label>
                  <?php $name = @$result->name;
                  $postvalue = @$_POST['name'];
                  echo form_input(array('type' => 'text', 'name' => 'name', 'id' => 'name', 'class' => 'form-control', 'placeholder' => 'Document Name', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                  <label class="error">
                    <div class="help-block" style="color:red"> <?php echo form_error('name'); ?></div>
                  </label>
                </div>
                <div class="form-group col-md-4">
                  <label for="inputState2">Start Date *</label>
                  <?php $start_date = @$result->start_date;
                  $postvalue = @$_POST['start_date'];
                  echo form_input(array('type' => 'text', 'name' => 'start_date', 'id' => 'start_date', 'class' => 'datepicked form-control', 'placeholder' => 'Start Date', 'value' => !empty($postvalue) ? $postvalue : $start_date));
                  ?>
                  <label class="error">
                    <div class="help-block" style="color:red">
                      <?php echo form_error('start_date'); ?>
                    </div>
                  </label>
                </div>
                <div class="form-group col-md-4">
                  <label for="inputState2">Valid Upto *</label>
                  <?php $end_date = @$result->end_date;
                  $postvalue = @$_POST['end_date'];
                  echo form_input(array('type' => 'text', 'name' => 'end_date', 'id' => 'end_date', 'class' => 'datepicked form-control', 'placeholder' => 'Valid Upto', 'value' => !empty($postvalue) ? $postvalue : $end_date));
                  ?>
                  <label class="error">
                    <div class="help-block" style="color:red">
                      <?php echo form_error('end_date'); ?>
                    </div>
                  </label>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="inputState2">Document No </label>
                  <?php
                  $name = @$result->remark;
                  $postvalue = @$_POST['remark'];
                  //                                                    $val = !empty($postvalue)? $postvalue:$name;
                  echo form_textarea(array('rows' => '4', 'name' => 'remark', 'id' => 'remark', 'maxlength' => '1000', 'class' => 'form-control', 'placeholder' => 'Document No', 'value' => !empty($postvalue) ? $postvalue : $name));
                  ?>
                  <label class="error">
                    <div class="help-block" style="color:red">
                      <?php echo form_error('remark'); ?>
                    </div>
                  </label>
                </div>
                <div class="form-group col-md-6">
                  <label for="document_file">Upload Document <?php echo empty($result) ? '*' : ''; ?></label>
                  <input type="file" name="document_file" id="document_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv">
                  <small class="text-muted">Allowed: PDF, Word, Excel, image, TXT, CSV. Max size: 1 GB.</small>
                  <?php if (!empty($result->document_file)) { ?>
                    <div class="mT-10">
                      <a class="btn btn-xs btn-success" href="<?php echo base_url('admin/document/download/' . ID_encode($result->id)); ?>">
                        <i class="fa fa-download"></i> Download current file
                      </a>
                      <?php if (!empty($result->original_file_name)) { ?>
                        <span class="text-muted"><?php echo htmlspecialchars($result->original_file_name, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php } ?>
                    </div>
                  <?php } ?>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="inputState2">Status *</label>
                  <select id="inputState2" class="form-control" name="status">
                    <?php $status = !empty($_POST['status']) ? $_POST['status'] : @$result->status; ?>
                    <option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                  </select>
                </div>
                <div class="form-group col-md-8">
                  <?php $is_common = isset($_POST['is_common']) ? $_POST['is_common'] : @$result->is_common; ?>
                  <label>Firm Scope</label>
                  <div class="checkbox checkbox-circle checkbox-info peers ai-c">
                    <div class="peer">
                      <input type="checkbox" name="is_common" id="is_common" value="1" <?php echo !empty($is_common) ? 'checked' : ''; ?>>
                      <label for="is_common">Common for all firms</label>
                    </div>
                  </div>
                  <small class="text-muted">Use this for documents that should appear once and remain same after switching firm.</small>
                </div>
              </div>
              <div class="form-group">
                <div class="checkbox checkbox-circle checkbox-info peers ai-c text-center">
                  <div class="peer">
                    <button type="submit" class="btn btn-primary"> Submit </button>
                    <a href="<?php echo base_url('admin/document/listing/'); ?>"><button type="button"
                        class="btn btn-primary"> Cancel </button></a>
                  </div>
                </div>
              </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
</main>

<script>
  $(function () {
    $(".datepicked").datepicker({
      dateFormat: "dd-mm-yy"
    });

    // Set current date in start_date only while adding a new document.
    if ($("#start_date").val().trim() == "") {
      $("#start_date").datepicker("setDate", new Date());
    }

    // Validate dates
    $("#start_date, #end_date").on("change", function () {

      let start = $("#start_date").datepicker("getDate");
      let end = $("#end_date").datepicker("getDate");

      if (start && end) {

        // Start date should not be greater than end date
        if (start > end) {

          alert("Start Date should be less than or equal to End Date");

          $("#end_date").val("").css("border", "2px solid red");

        } else {

          $("#end_date").css("border", "");
        }
      }
    });

    // Datepicker
    $(".datepicked").datepicker({
      dateFormat: "dd-mm-yy",
      minDate: 0
    });

    if ($("#start_date").val().trim() == "") {
      $("#start_date").datepicker("setDate", new Date());
    }

    // Form Validation
    $("#ciatyform_id").on("submit", function (e) {

      let isValid = true;

      // Remove old errors
      $(".custom-error").remove();

      // Name Validation
      if ($("#name").val().trim() == "") {
        $("#name").after('<div class="custom-error text-danger">Please enter Doc Name</div>');
        isValid = false;
      }

      // Start Date Validation
      if ($("#start_date").val().trim() == "") {
        $("#start_date").after('<div class="custom-error text-danger">Please select Start Date</div>');
        isValid = false;
      }

      // End Date Validation
      if ($("#end_date").val().trim() == "") {
        $("#end_date").after('<div class="custom-error text-danger">Please select Valid Upto Date</div>');
        isValid = false;
      }

      // Date Compare Validation
      let startDate = $("#start_date").datepicker("getDate");
      let endDate = $("#end_date").datepicker("getDate");

      if (startDate && endDate) {

        if (endDate <= startDate) {
          $("#end_date").after('<div class="custom-error text-danger">Valid Upto Date should be greater than Start Date</div>');
          isValid = false;
        }
      }

      // Remark Validation
      if ($("#remark").val().trim() == "") {
        $("#remark").after('<div class="custom-error text-danger">Please enter Document No</div>');
        isValid = false;
      }

      <?php if (empty($result)) { ?>
      if ($("#document_file").val().trim() == "") {
        $("#document_file").after('<div class="custom-error text-danger">Please upload document file</div>');
        isValid = false;
      }
      <?php } ?>

      // Status Validation
      if ($("select[name='status']").val() == "") {
        $("select[name='status']").after('<div class="custom-error text-danger">Please select Status</div>');
        isValid = false;
      }

      // Stop Submit
      if (!isValid) {
        e.preventDefault();
      }

    });


  });

</script>
