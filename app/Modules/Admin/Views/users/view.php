<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <h4 class="c-grey-900 mT-10 mB-30">User Profile</h4>
            <div class="row">
                <div class="col-md-12">
                    <div class="bgc-white bd bdrs-3 p-20 mB-20">
                        <div class="d-flex justify-content-end" style="text-align:right;margin-bottom:10px;">
                            <a href="<?= base_url('admin/users/listing') ?>" class="btn btn-secondary btn-sm mr-2">Back</a>
                            <a href="<?= base_url('admin/users/edit/' . ID_encode($users->id)) ?>" class="btn btn-primary btn-sm">Edit User</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr><th class="table_bg" scope="col">User Id</th><th scope="col"><?= (int) $users->id; ?></th></tr>
                            </thead>
                            <tbody>
                                <tr><th class="table_bg">First Name</th><td><?= esc($users->first_name); ?></td></tr>
                                <tr><th class="table_bg">Last Name</th><td><?= esc($users->last_name); ?></td></tr>
                                <tr><th class="table_bg">Email</th><td><?= esc($users->email); ?></td></tr>
                                <tr><th class="table_bg">Mobile</th><td><?= esc($users->mobile); ?></td></tr>
                                <tr><th class="table_bg">PAN Number</th><td><?= esc($users->pan_number); ?></td></tr>
                                <tr><th class="table_bg">Address</th><td><?= esc($users->address); ?></td></tr>
                                <tr><th class="table_bg">Status</th><td><?= esc($users->status); ?></td></tr>
                                <tr><th class="table_bg">User Type</th><td><?= esc($users->user_type); ?></td></tr>
                                <tr><th class="table_bg">Default Firm</th><td><?= esc($users->firm_name ?? '—'); ?></td></tr>
                                <tr><th class="table_bg">Super Admin</th><td><?= ((int) ($users->isSuperAdmin ?? 0) === 1) ? 'Yes' : 'No'; ?></td></tr>
                                <tr><th class="table_bg">Added Date</th><td><?= ! empty($users->added_date) ? date('d M Y h:i A', strtotime($users->added_date)) : '-'; ?></td></tr>
                                <tr><th class="table_bg">Last Login</th><td><?= ! empty($users->last_login) ? date('d M Y h:i A', strtotime($users->last_login)) : '-'; ?></td></tr>
                                <tr><th class="table_bg">Remark</th><td><?= nl2br(esc((string) ($users->remark ?? ''))); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
