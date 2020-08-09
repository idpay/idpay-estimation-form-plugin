<?php
/*
Plugin Name: IDPay Estimation Form
Description: IDPay payment gateway for WP Estimation Form.
Plugin URI: https://github.com/idpay/estimation-form
Version: 1.0.0
Author: meysamrazmi, vispa
Author URI: https://idpay.ir/
License: GPLv2 or later
*/

define( 'IDPAY_WPEFC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

GLOBAL $wpdb;
$idpay_table_name = $wpdb->prefix . "wpefc_idpay_setting";
$idpay_transactions = $wpdb->prefix . "wpefc_idpay_transactions";

function check_idpay_wpefc_state(){
    if (!is_plugin_active('WP_Estimation_Form/estimation-form.php')){
        deactivate_plugins( '/idpay-wp-estimation-form/idpay-wp-estimation-form.php' );
    }
}

add_action('admin_init', 'check_idpay_wpefc_state');

if ($wpdb->get_var("SHOW TABLES LIKE '$idpay_table_name'") != $idpay_table_name) {
    if (!empty($wpdb->charset))
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    if (!empty($wpdb->collate))
        $charset_collate .= " COLLATE $wpdb->collate";

    $sql = "CREATE TABLE $idpay_table_name (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`title` VARCHAR(120) NULL,
		`value` VARCHAR(120) NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
    $wpdb->query($sql);
    $wpdb->query("INSERT INTO $idpay_table_name (id, title, value)
        VALUES
            ('', 'is_encript', 1),
            ('', 'idpay', 1),
            ('', 'sandbox', 1),
            ('', 'currency', 'rial'),
            ('', 'api_key', ''),
            ('', 'sucssesmsg', 'پرداخت با موفقیت صورت گرفت. به زودی با شما تماس خواهیم گرفت '),
            ('', 'faildmsg', 'در صورت کسر مبلغ از حساب، طی نهایتا 24 ساعت به حسابتان برگشت خواهد خورد.')
        ");
    $sql = "CREATE TABLE $idpay_transactions (
      `id` mediumint(9) NOT NULL AUTO_INCREMENT,
      `code` INT(11) NULL,
      `amount` VARCHAR(120) NULL,
      `time` VARCHAR(20) NULL,
      `email` VARCHAR(120) NULL,
      `token` VARCHAR(120) NULL,
      `status` INT(4) NULL,
      `log` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
      UNIQUE KEY id (id)
		) $charset_collate;";
    $wpdb->query($sql);
}

$dbsetting = $wpdb->get_results("SELECT `title`,`value` FROM $idpay_table_name",ARRAY_A);
$options = [];
foreach ($dbsetting as $val){
    $options[$val['title']] = $val['value'];
}
include_once dirname(__FILE__) . '/result.php';

if(isset($_GET['wpef_idpay'])){
    if(isset($_GET['email'])){
        $_GET['email'] = str_replace('%40','@',sanitize_text_field($_GET['email']) );
    }
    if($_GET['wpef_idpay'] == 'pay' && isset($_GET['email']) && $_GET['email'] != '') {
        $info = find_order();
        if ($options['idpay'] && $info['amount']) {
            include_once dirname(__FILE__) . '/idpay/request.php';
        }

    }
    else if (sanitize_text_field($_GET['wpef_idpay']) == 'verify'){
        $callback = get_site_url();
        include_once dirname(__FILE__) . "/idpay/verify.php";
    }
}

$orderlimit = 10;
$find_orderloop = 0;
function find_order(){
    GLOBAL $find_orderloop;
    GLOBAL $wpdb;
    GLOBAL $orderlimit;
    $ef_table_name = $wpdb->prefix . "wpefc_logs";
    $info = array(
        'ammout' => '',
        'id' => '',
        'email' => '',
        'ref' => '',
        'phone' => '',
        'name' => '',
    );
    $log = $wpdb->get_results ("SELECT `id`,`ref`,`email`,`phone`,`firstName`,`lastName`,`totalPrice` FROM `$ef_table_name` ORDER by `id` DESC LIMIT 100");
    foreach ($log as $val){
        if(string_decode($val->email,1) == sanitize_text_field($_GET['email'])){
            $info['amount'] = $val->totalPrice;
            $info['id'] = $val->id;
            $info['email'] = sanitize_text_field($_GET['email']);
            $info['ref'] = $val->ref;
            $info['phone'] = $val->phone;
            $info['name'] = $val->firstName .' '. $val->lastName;
            break;
        }

    }
    if(!$info['id']){
        $find_orderloop++;
        $orderlimit = 200;
        if($find_orderloop < 2) find_order();
    }
    else{
        return $info;
    }
}

function get_KeyS() {
    if (get_option('lfbK') !== false) {
        $key = get_option('lfbK');
    } else {
        $key = md5(uniqid(rand(), true));
        update_option('lfbK', $key);
    }
    return $key;
}

function string_decode($value, $enableCrypt) {
    GLOBAL $options;
    if (!$options['is_encript']) {
        $text = $value;
    } else {
        if ($value != "") {
            $encrypted_data = "";
            $iv = "";
            list($encrypted_data, $iv) = explode('::', _b64decode($value), 2);
            $text = openssl_decrypt($encrypted_data, 'aes128', get_KeyS(), null, $iv);
        } else {
            $text = "";
        }
    }
    return $text;
}

function _b64encode($string) {
    $data = base64_encode($string);
    $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
    return $data;
}

function _b64decode($string)
{
    $data = str_replace(array('-', '_'), array('+', '/'), $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}


add_action('wp_footer', 'print_default');
function print_default() {
    GLOBAL $options;
    if(!$options['idpay']){
        return;
    }
    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/payment.php' );
}

add_action( 'admin_menu', 'wpse_idpay_register' );
function wpse_idpay_register()
{
    add_menu_page('My Custom Page', 'IDPay for E&P Form', 'manage_options', 'ep_transactions','ep_transactions');
    add_submenu_page( 'ep_transactions', 'تراکنش ها', 'تراکنش ها','manage_options', 'ep_transactions','ep_transactions');
    add_submenu_page( 'ep_transactions', 'تنظیمات درگاه', 'تنظیمات درگاه','manage_options', 'ep_transactions_setting','ep_transactions_setting');
    add_submenu_page( 'ep_transactions', 'single', '','manage_options', 'ep_transactions_single','ep_transactions_single');
}

function ep_transactions() {
    GLOBAL $wpdb;
    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'assets/jdf.php' );
    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions.php' );
}

function ep_transactions_single() {
    GLOBAL $wpdb;
    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'assets/jdf.php' );

    $code        = sanitize_text_field($_GET['code']);
    $sql_id      = sanitize_text_field($_GET['sql_id']);
    $transaction = $wpdb->get_row("select  ref,email,content,formTitle,totalPrice,paid FROM ". $wpdb->prefix ."wpefc_logs WHERE id = '$code'");
    $payment     = $wpdb->get_row("select * FROM ". $wpdb->prefix ."wpefc_idpay_transactions WHERE id = '$sql_id'");

    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/single-transaction.php' );
}

function ep_transactions_setting() {
    GLOBAL $wpdb;
    GLOBAL $options;
    GLOBAL $idpay_table_name;

    if(isset($_POST['faildmsg']) && isset($_POST['sucssesmsg']) && isset($_POST['api_key'])){
        foreach ($_POST as $key => $value){
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '". $value ."' WHERE title = '$key'");
        }

        if(isset($_POST['idpay'])){
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '1' WHERE title = 'idpay'");
        }else{
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '0' WHERE title = 'idpay'");
        }
        if(isset($_POST['is_encript'])){
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '1' WHERE title = 'is_encript'");
        }else{
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '0' WHERE title = 'is_encript'");
        }
        if(isset($_POST['sandbox'])){
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '1' WHERE title = 'sandbox'");
        }else{
            $wpdb->query("UPDATE $idpay_table_name SET `value` = '0' WHERE title = 'sandbox'");
        }

        $dbsetting = $wpdb->get_results("SELECT `title`,`value` FROM $idpay_table_name",ARRAY_A);
        foreach ($dbsetting as $val){
            $options[$val['title']] = $val['value'];
        }
    }

    require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/settings.php' );
}

function _find_between($string,$start,$end,$greedy = false) {
    $start = preg_quote($start, '/');
    $end   = preg_quote($end, '/');

    $format = '/(%s)(.*';
    if (!$greedy) $format .= '?';
    $format .= ')(%s)/';

    $pattern = sprintf($format, $start, $end);
    preg_match($pattern, $string, $matches);

    return $matches[2];
}

