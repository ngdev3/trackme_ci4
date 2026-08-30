<style>
    .help-block{
        color:red;
    }
</style>

<?php
$data = $this->session->userdata('publisher_info');

?>
<main class="main-content bgc-grey-100">
                <div id="mainContent">
				<h4 class="c-grey-900 mT-10 mB-30"><?=$breadcum;?></h4>
                    <div class="row gap-20 masonry pos-r">
                        <div class="masonry-sizer col-md-12"></div>
                        <div class="masonry-item col-md-12">
                            <div class="bgc-white p-20 bd">
                                <div id="errormsg" style="color:red"></div>

                                <div class="col-dm-12">
                                    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
                                    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
                                </div>

                                 <?php echo form_open(current_url(), array('class' => '', 'id' => 'teamForm')); ?>
                                        
                                    <div class="form-row pT-20 ">
                                            <div class="form-group offset-md-3 col-md-5">
                                                <label for="frst_nme" class="input-label"> Password *</label>
                                                <input type="password" class="input-content form-control" id="old_pwd" name="password" value="" placeholder="Password" maxlength="40">
                                            </div>
                                        </div>

										<div class="form-row">
                                            <div class="form-group offset-md-3 col-md-5">
                                                <label for="email_id" class="input-label"> New Password * </label>
                                                <input type="password" class="input-content form-control" name="new_password" value="" id="email_ids" placeholder="New Password">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group offset-md-3 col-md-5">
                                                <label for="email_id" class="input-label">Confirm Password * </label>
                                                <input type="password" class="input-content form-control" name="confirm_password" value="" id="email_id" placeholder="Confirm Password">
                                            </div>
                                        </div>


										<div class="text-center my-4">
											<button type="submit" class="btn btn-primary nxt-btn">Submit</button>
										</div>
                                    </form>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
            <!-- END PAGE CONTENT-->
<script type="text/javascript" src="<?=base_url()?>assets/validate.js"></script>

 <script>

//  validate mobile number takes only numeric values
$(document).ready(function() {
    $("#mo").keydown(function (e) {
		$('#mobile_number_id').html('');

        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
             // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
             // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
			$('#mobile_number_id').html('');
            e.preventDefault();
        }
		else{
			$('#mobile_number_id').html("");
		}
    });
});


$("input[name='password']").keypress(function( e ) {
    if(e.which === 32) 
        return false;
});
 $("input[name='new_password']").keypress(function( e ) {
    if(e.which === 32) 
        return false;
});
$("input[name='confirm_password']").keypress(function( e ) {
    if(e.which === 32) 
        return false;
});

//Our validation script will go here.
    $(document).ready(function(){
    
       //custom validation rule - text only
      $.validator.addMethod("alphnumOnly", 
                              function(value, element) {
                                  return /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+).{5,}$/.test(value);
                              }, 
                              "Password should be alphanumeric and not less than 6 characters"
      );
$.validator.addMethod("is_password_match", function(value,element) {
 var newPass =   $("input[name='new_password']").val();
 if(newPass==value){
     return true;
 }
 return false;
},'');  

});
 $("#teamForm").validate({
    rules: {
        password:{
            required: true,
        },
        new_password:{
            required: true,
            alphnumOnly: true,
        },
        confirm_password:{
            required: true,
			alphnumOnly: true,
            is_password_match:true,
        }
    },
    messages:{
        password:{
               required: '<div class="help-block" style="color:red"> Password is required.</div>',
            },
            new_password:{
               required: '<div class="help-block" style="color:red"> New password is required.</div>',
               alphnumOnly: '<div class="help-block" style="color:red"> Password should be alphanumeric and not less than 6 characters.</div>'
            },
            confirm_password:{
               required: '<div class="help-block" style="color:red"> Confirm password is required.</div>',
               alphnumOnly: '<div class="help-block" style="color:red"> Password should be alphanumeric and not less than 6 characters.</div>',
               is_password_match:'<span class="help-block" style="color:red">New Password & Confirm New Password should be same.</span>',
              }
        }
});

	</script>