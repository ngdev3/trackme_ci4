<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | CI3 compatibility — BASEPATH
 | --------------------------------------------------------------------------
 | Views and helpers copied 1:1 from the legacy CI3 app open with
 |   defined('BASEPATH') OR exit('No direct script access allowed');
 | CI4 has no BASEPATH (it uses SYSTEMPATH), so rendering such a view would
 | exit with "No direct script access allowed". Define it as a harmless alias
 | so every ported CI3 view/helper passes that guard.
 */
defined('BASEPATH') || define('BASEPATH', defined('SYSTEMPATH') ? SYSTEMPATH : ROOTPATH);

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/*
 |--------------------------------------------------------------------------
 | CI3 form-helper compatibility shims
 |--------------------------------------------------------------------------
 | Ported CI3 views call form_error()/validation_errors(), which don't exist
 | in CI4 and 500 the page. These shims read validation errors from flashdata
 | (set on a failed submit + redirect) and return '' on a fresh GET — so every
 | ported add/edit view renders instead of crashing. Defined here because
 | Constants.php is always loaded, regardless of a controller's $helpers.
 */
if (! function_exists('form_error')) {
    function form_error(string $field = '', string $open = '<span class="text-danger">', string $close = '</span>'): string
    {
        $errors = session()->getFlashdata('errors');
        $errors = is_array($errors) ? $errors : [];
        $msg = $errors[$field] ?? '';
        return $msg !== '' ? $open . $msg . $close : '';
    }
}
if (! function_exists('validation_errors')) {
    function validation_errors(string $open = '<p class="text-danger">', string $close = '</p>'): string
    {
        $errors = session()->getFlashdata('errors');
        $errors = is_array($errors) ? $errors : [];
        $out = '';
        foreach ($errors as $msg) {
            $out .= $open . $msg . $close;
        }
        return $out;
    }
}
if (! function_exists('get_logical_data')) {
    // CI3 shim: the global service on/off flag (SELECT status FROM service).
    // Cached per request; returns the row object (->status) or false.
    function get_logical_data()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $db = \Config\Database::connect();
            $cached = $db->tableExists('service') ? ($db->table('service')->select('status')->get()->getRow() ?: false) : false;
        } catch (\Throwable $e) {
            $cached = false;
        }
        return $cached;
    }
}
