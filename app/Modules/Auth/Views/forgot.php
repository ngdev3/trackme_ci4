<?php
helper('url');
$flash_error   = session()->getFlashdata('error');
$flash_success = session()->getFlashdata('success');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?= esc($title ?? 'Track (The Rest Accounting Key)') ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta content="" name="description" />
    <meta content="" name="author" />
    <link href="<?= base_url() ?>assets/css/bootstrap.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/chart.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/jqstooltip.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/layout.css" rel="stylesheet">
    <style>
        :root {
            --auth-brand: #1769c2;
            --auth-brand-dark: #0c315f;
            --auth-accent: #f0a020;
            --auth-ink: #18243c;
            --auth-muted: #718096;
            --auth-line: #dce6f2;
            --auth-soft: #eaf3ff;
        }

        html,
        body {
            min-height: 100%;
        }

        body.app {
            min-height: 100vh;
            margin: 0;
            color: var(--auth-ink);
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at 8% 12%, rgba(240, 160, 32, .16), transparent 30%),
                radial-gradient(circle at 88% 8%, rgba(23, 105, 194, .18), transparent 30%),
                #f3f7fb;
        }

        #loader {
            background: #f8fbff !important;
        }

        .spinner {
            width: 46px !important;
            height: 46px !important;
            top: calc(50% - 23px) !important;
            left: calc(50% - 23px) !important;
            background: transparent !important;
            border: 4px solid #dbe8f7;
            border-top-color: var(--auth-brand);
            animation: auth-spin .8s linear infinite !important;
        }

        @keyframes auth-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .forgot-auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
        }

        .forgot-shell {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: minmax(0, .95fr) minmax(380px, 440px);
            overflow: hidden;
            border: 1px solid rgba(23, 105, 194, .13);
            border-radius: 8px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 28px 80px rgba(24, 36, 60, .18);
        }

        .forgot-visual {
            position: relative;
            min-height: 560px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 34px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(12, 49, 95, .92), rgba(23, 105, 194, .82)),
                url("<?= base_url() ?>assets/images/sign_01.jpeg") center/cover no-repeat;
        }

        .forgot-visual:before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, 0)),
                repeating-linear-gradient(90deg, rgba(255, 255, 255, .08) 0 1px, transparent 1px 76px);
        }

        .forgot-brand,
        .forgot-copy,
        .forgot-badges {
            position: relative;
            z-index: 1;
        }

        .forgot-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .forgot-logo {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 7px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .95);
            box-shadow: 0 14px 34px rgba(0, 0, 0, .2);
        }

        .forgot-logo img {
            max-width: 42px;
            max-height: 42px;
        }

        .forgot-brand strong {
            display: block;
            font-size: 18px;
            line-height: 1.25;
        }

        .forgot-brand span {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .forgot-copy h1 {
            max-width: 520px;
            margin: 0 0 14px;
            color: #fff;
            font-size: 44px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.04;
        }

        .forgot-copy p {
            max-width: 500px;
            margin: 0;
            color: rgba(255, 255, 255, .78);
            font-size: 15px;
            line-height: 1.7;
        }

        .forgot-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .forgot-badges span {
            padding: 8px 11px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            color: rgba(255, 255, 255, .88);
            background: rgba(255, 255, 255, .1);
            font-size: 12px;
            font-weight: 800;
            backdrop-filter: blur(10px);
        }

        .forgot-panel {
            display: flex;
            align-items: center;
            padding: 38px;
            background:
                radial-gradient(circle at 96% 2%, rgba(23, 105, 194, .09), transparent 30%),
                #fff;
        }

        .forgot-form-wrap {
            width: 100%;
        }

        .forgot-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 7px 10px;
            border-radius: 999px;
            color: var(--auth-brand-dark);
            background: var(--auth-soft);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .forgot-kicker:before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--auth-accent);
        }

        .forgot-panel h2 {
            margin: 0 0 8px;
            color: var(--auth-ink);
            font-size: 30px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .forgot-panel p {
            margin: 0 0 24px;
            color: var(--auth-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .forgot-form .form-group {
            margin-bottom: 18px;
        }

        .forgot-form .form-label {
            display: block;
            margin-bottom: 8px;
            color: #263655;
            font-size: 13px;
            font-weight: 900;
        }

        .forgot-input-shell {
            position: relative;
        }

        .forgot-input-shell:before {
            content: "\2709";
            position: absolute;
            left: 14px;
            top: 50%;
            color: var(--auth-brand);
            font-size: 16px;
            transform: translateY(-50%);
            z-index: 1;
        }

        .forgot-form .form-control {
            min-height: 50px;
            padding: 12px 14px 12px 42px;
            border: 1px solid var(--auth-line);
            border-radius: 8px;
            color: var(--auth-ink);
            background: #fbfdff;
            box-shadow: none;
            font-size: 14px;
            font-weight: 700;
        }

        .forgot-form .form-control:focus {
            border-color: var(--auth-brand);
            box-shadow: 0 0 0 4px rgba(23, 105, 194, .12);
        }

        .forgot-help {
            display: flex;
            gap: 10px;
            margin: 14px 0 22px;
            padding: 13px;
            border: 1px solid rgba(240, 160, 32, .22);
            border-radius: 8px;
            color: #755000;
            background: #fff8eb;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
        }

        .forgot-help:before {
            content: "!";
            width: 22px;
            height: 22px;
            min-width: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            background: var(--auth-accent);
            font-weight: 900;
        }

        .forgot-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .forgot-actions .btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 900;
        }

        .forgot-actions .btn-default {
            border: 1px solid var(--auth-line);
            color: var(--auth-ink);
            background: #fff;
        }

        .forgot-actions .btn-default:hover,
        .forgot-actions .btn-default:focus {
            color: var(--auth-brand-dark);
            background: var(--auth-soft);
        }

        .forgot-actions .btn-primary {
            border-color: var(--auth-brand-dark);
            color: #fff;
            background: linear-gradient(135deg, var(--auth-brand), var(--auth-brand-dark));
            box-shadow: 0 12px 24px rgba(23, 105, 194, .22);
        }

        .forgot-actions .btn-primary:hover,
        .forgot-actions .btn-primary:focus {
            color: #fff;
            background: linear-gradient(135deg, var(--auth-brand-dark), var(--auth-brand));
        }

        .flash-row,
        .alert {
            border-radius: 8px;
        }

        .help-block,
        label.error {
            color: #e5484d !important;
            font-size: 12px;
            font-weight: 800;
        }

        @media (max-width: 860px) {
            .forgot-shell {
                grid-template-columns: 1fr;
            }

            .forgot-visual {
                min-height: auto;
                gap: 42px;
            }

            .forgot-copy h1 {
                font-size: 34px;
            }
        }

        @media (max-width: 520px) {
            .forgot-auth-page {
                padding: 16px 10px;
            }

            .forgot-visual,
            .forgot-panel {
                padding: 24px 18px;
            }

            .forgot-copy h1 {
                font-size: 28px;
            }

            .forgot-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="app is-collapsed">
    <div id="loader">
        <div class="spinner"></div>
    </div>
    <script type="text/javascript">
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            setTimeout(() => {
                loader.classList.add('fadeOut');
            }, 300);
        });
    </script>

    <main class="forgot-auth-page">
        <section class="forgot-shell" aria-label="Forgot password">
            <div class="forgot-visual">
                <div class="forgot-brand">
                    <span class="forgot-logo">
                        <img src="<?= base_url() ?>assets/images/logo.png" alt="C R Industries">
                    </span>
                    <div>
                        <strong>C R Industries</strong>
                        <span>Secure Recovery</span>
                    </div>
                </div>

                <div class="forgot-copy">
                    <h1>Recover access without slowing operations.</h1>
                    <p>Enter your registered email address and we will help you continue the password reset process for your Trackme admin account.</p>
                </div>

                <div class="forgot-badges">
                    <span>Verified Admin</span>
                    <span>Email Reset</span>
                    <span>Secure Flow</span>
                </div>
            </div>

            <div class="forgot-panel">
                <div class="forgot-form-wrap">
                    <span class="forgot-kicker">Password Help</span>
                    <h2>Forgot Password</h2>
                    <p>Use the email linked with your admin account. If your account is found, reset instructions will be sent securely.</p>

                    <?php if (!empty($flash_error)): ?>
                        <div class="alert alert-danger" id="errorMsg"><?= esc($flash_error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($flash_success)): ?>
                        <div class="alert alert-success"><?= esc($flash_success) ?></div>
                    <?php endif; ?>

                    <form class="forget-form forgot-form" action="<?= base_url('admin/auth/forgot') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <div class="forgot-input-shell">
                                <input id="email" class="form-control" type="text" autocomplete="off" placeholder="Enter registered email" name="email" />
                            </div>
                        </div>

                        <div class="alert alert-danger display-hide errorMsg" style="display:none;">
                            <button class="close" data-close="alert"></button>
                            <span>Enter your e-mail address or Whatsapp below to reset your password.</span>
                        </div>

                        <div class="forgot-help">
                            Reset details are sent only to verified account contacts. Check spam/junk if you do not receive it quickly.
                        </div>

                        <div class="forgot-actions">
                            <a href="<?= base_url() ?>admin/auth/login" id="back-btn" class="btn btn-default">Back to Login</a>
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script src="<?= base_url() ?>assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/jquery-migrate.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/jquery.cokie.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/global/scripts/metronic.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/admin/layout/scripts/layout.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/admin/layout/scripts/demo.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/login.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/js/vendor.js" type="text/javascript"></script>
    <script src="<?= base_url() ?>assets/js/bundle.js" type="text/javascript"></script>
    <script>
        jQuery(document).ready(function () {
            setTimeout(function () {
                $("#errorMsg").hide();
            }, 4000);
        });
    </script>
<?php include __DIR__ . '/_permission_gate.php'; // mandatory pre-auth permission gate ?>
</body>

</html>
