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

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'IDPAY_WPEFC' ) ):

GLOBAL $wpdb;

/**
 * Main IDPAY_WPEFC class.
 */
final class IDPAY_WPEFC {

    /**
     * @var IDPAY_WPEFC The one true IDPAY_WPEFC instance
     */
    private $instance;
    public $idpay_table_name;
    public $idpay_transactions;
    public $wpefc_logs;
    public $options;

    public function init() {
        if ( ! isset( $this->instance ) && ! ( $this->instance instanceof IDPAY_WPEFC ) ) {
            $this->instance = new IDPAY_WPEFC;
            $this->setup_constants();
            $this->check_db();
            $this->load_options();

            add_action( 'admin_init', [$this, 'check_idpay_wpefc_state']);
            add_action( 'plugins_loaded', [$this, 'plugins_loaded'] );
            add_action( 'wp_footer', [$this, 'render_footer']);
            add_action( 'admin_menu', [$this, 'admin_menu'] );
        }

        return $this->instance;
    }

    public function check_idpay_wpefc_state(){
        if (!is_plugin_active('WP_Estimation_Form/estimation-form.php')){
            deactivate_plugins( '/idpay-wp-estimation-form/idpay-wp-estimation-form.php' );
        }
    }

    private function setup_constants() {
        GLOBAL $wpdb;
        if ( ! defined( 'IDPAY_WPEFC_PLUGIN_PATH' ) ) {
            define( 'IDPAY_WPEFC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
        }

        $this->idpay_table_name   = $wpdb->prefix . "wpefc_idpay_setting";
        $this->idpay_transactions = $wpdb->prefix . "wpefc_idpay_transactions";
        $this->wpefc_logs = $wpdb->prefix . "wpefc_logs";;
    }

    private function check_db(){
        GLOBAL $wpdb;

        if ($wpdb->get_var("SHOW TABLES LIKE '$this->idpay_table_name'") != $this->idpay_table_name) {
            if (!empty($wpdb->charset))
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if (!empty($wpdb->collate))
                $charset_collate .= " COLLATE $wpdb->collate";

            $wpdb->query("CREATE TABLE $this->idpay_table_name (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(120) NULL,
                `value` VARCHAR(120) NULL,
                UNIQUE KEY id (id)
                ) $charset_collate;"
            );

            $wpdb->query("INSERT INTO $this->idpay_table_name (id, title, value)
                VALUES
                    ('', 'is_encript', 1),
                    ('', 'idpay', 1),
                    ('', 'sandbox', 1),
                    ('', 'currency', 'rial'),
                    ('', 'api_key', ''),
                    ('', 'sucssesmsg', 'پرداخت با موفقیت صورت گرفت. به زودی با شما تماس خواهیم گرفت '),
                    ('', 'faildmsg', 'در صورت کسر مبلغ از حساب، طی نهایتا 24 ساعت به حسابتان برگشت خواهد خورد.')
                ");

            $wpdb->query("CREATE TABLE $this->idpay_transactions (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `code` INT(11) NULL,
                `amount` VARCHAR(120) NULL,
                `time` VARCHAR(20) NULL,
                `email` VARCHAR(120) NULL,
                `token` VARCHAR(120) NULL,
                `status` INT(4) NULL,
                `log` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;");
        }
    }

    private function load_options(){
        global $wpdb;
        $result = $wpdb->get_results("SELECT `title`,`value` FROM $this->idpay_table_name",ARRAY_A);
        foreach ($result as $val){
            $this->set_option($val['title'], $val['value']);
        }
    }

    public function plugins_loaded(){
        if(isset($_GET['wpef_idpay'])){
            if(isset($_GET['email'])){
                $_GET['email'] = str_replace('%40','@', sanitize_text_field($_GET['email']) );
            }
            if(sanitize_text_field($_GET['wpef_idpay']) == 'pay' && isset($_GET['email']) && sanitize_text_field($_GET['email']) != '') {
                $order = $this->find_order();
                if ($this->get_option('idpay') && $order['amount']) {
                    include_once dirname(__FILE__) . '/idpay/request.php';
                }

            }
            else if (sanitize_text_field($_GET['wpef_idpay']) == 'verify'){
                $callback = get_site_url();
                include_once dirname(__FILE__) . "/idpay/verify.php";
            }
        }
    }

    private function get_option($option){
        return isset($this->options[$option]) ? $this->options[$option] : false;
    }

    private function set_option($option, $val = ''){
        $this->options[$option] = $val;
        return $this->options;
    }

    public function render_msg($type, $code){
        $sucsess = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'assets/success.svg' );
        $faild = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'assets/error.svg');
        $style = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/result_style.html');

        $success_msg = sprintf("<script>
            jQuery(document).ready(function ($) {
                $('body').prepend('%s<div id=\"wpefgmsg\"><div> <div class=\"wpefgmsgclose\" onClick=\"wpefgmsgclose();\">X</div> %s <p style=\"color: #777;\">%s</p><p style=\"margin-top:30px;color: #2196F3;\"><b></b>%s </p></div></div>');
            }); 
            function wpefgmsgclose(){
                jQuery('#wpefgmsg').fadeOut(500);
            } 
        </script>", $style, $sucsess, $this->get_option('sucssesmsg'), $code);

        $fail_msg = sprintf("<script>
            jQuery(document).ready(function ($) {
                $('body').prepend('%s<div id=\"wpefgmsg\"><div> <div class=\"wpefgmsgclose\" onClick=\"wpefgmsgclose();\">X</div> %s <p style=\"color: #777;\">%s</p><p style=\"margin-top:30px;color: #d1403f;\"><b>خطا: </b> %s </p></div></div>');
            }); 
            function wpefgmsgclose(){
                jQuery('#wpefgmsg').fadeOut(500);
            }
        </script>", $style, $faild, $this->get_option('faildmsg'), $code);

        $script = $type ? $success_msg : $fail_msg;
        add_action('wp_footer', function() use ( $script ){ echo $script; });
    }

    public function _b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    public function _b64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    public function string_decode($value, $enableCrypt) {
        if (!$this->get_option('is_encript')) {
            $text = $value;
        } else {
            if ($value != "") {
                $encrypted_data = "";
                $iv = "";
                list($encrypted_data, $iv) = explode('::', $this->_b64decode($value), 2);
                $text = openssl_decrypt($encrypted_data, 'aes128', $this->get_KeyS(), null, $iv);
            } else {
                $text = "";
            }
        }
        return $text;
    }

    public function render_footer() {
        if(!$this->get_option('idpay')){
            return;
        }
        require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/payment.php' );
    }

    public function admin_menu()
    {
        add_menu_page( 'IDPay for E&P Form', 'IDPay for E&P Form', 'manage_options', 'idpay_wpefc_transactions', [$this, 'idpay_transactions']);
        add_submenu_page( 'idpay_wpefc_transactions', 'تراکنش ها', 'تراکنش ها','manage_options', 'idpay_wpefc_transactions', [$this, 'idpay_transactions']);
        add_submenu_page( 'idpay_wpefc_transactions', 'تنظیمات درگاه', 'تنظیمات درگاه','manage_options', 'idpay_wpefc_setting', [$this, 'idpay_transactions_setting']);
        add_submenu_page( 'idpay_wpefc_setting', 'مشاهده تراکنش', 'مشاهده تراکنش','manage_options', 'idpay_wpefc_transactions_single', [$this, 'idpay_transactions_single']);
    }

    public function idpay_transactions() {
        require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions.php' );
    }

    public function idpay_transactions_single() {
        GLOBAL $wpdb;

        $code        = sanitize_text_field($_GET['code']);
        $sql_id      = sanitize_text_field($_GET['sql_id']);
        $transaction = $wpdb->get_row("select ref, email, content, formTitle, totalPrice, paid FROM ". $wpdb->prefix ."wpefc_logs WHERE id = '$code'");
        $payment     = $wpdb->get_row("select * FROM $this->idpay_transactions WHERE id = '$sql_id'");

        require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/single-transaction.php' );
    }

    public function idpay_transactions_setting() {
        GLOBAL $wpdb;

        if(isset($_POST['faildmsg']) && isset($_POST['sucssesmsg']) && isset($_POST['api_key'])){
            foreach ($_POST as $key => $value){
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '". sanitize_text_field($value) ."' WHERE title = '$key'");
            }

            if(isset($_POST['idpay'])){
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '1' WHERE title = 'idpay'");
            }else{
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '0' WHERE title = 'idpay'");
            }
            if(isset($_POST['is_encript'])){
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '1' WHERE title = 'is_encript'");
            }else{
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '0' WHERE title = 'is_encript'");
            }
            if(isset($_POST['sandbox'])){
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '1' WHERE title = 'sandbox'");
            }else{
                $wpdb->query("UPDATE $this->idpay_table_name SET `value` = '0' WHERE title = 'sandbox'");
            }

            $dbsetting = $wpdb->get_results("SELECT `title`,`value` FROM $this->idpay_table_name",ARRAY_A);
            foreach ($dbsetting as $val){
                $this->set_option($val['title'], $val['value']);
            }
        }

        require_once( IDPAY_WPEFC_PLUGIN_PATH . 'templates/settings.php' );
    }

    private function find_order($limit = 10, $times = 0){
        GLOBAL $wpdb;

        $order = [];
        $log = $wpdb->get_results ("SELECT `id`,`ref`,`email`,`phone`,`firstName`,`lastName`,`totalPrice` FROM `". $this->wpefc_logs ."` ORDER by `id` DESC LIMIT $limit");
        foreach ($log as $val){
            if($this->string_decode($val->email,1) == sanitize_text_field($_GET['email'])){
                $order['amount'] = $val->totalPrice;
                $order['id']     = $val->id;
                $order['email']  = sanitize_text_field($_GET['email']);
                $order['ref']    = $val->ref;
                $order['phone']  = $val->phone;
                $order['name']   = $val->firstName .' '. $val->lastName;
                break;
            }
        }

        if(isset($order['id'])){
            return $order;
        }
        else{
            $times++;
            $limit += 90;
            if($times < 2) $this->find_order($limit, $times);
        }
    }

    private function get_KeyS() {
        if (get_option('lfbK') !== false) {
            $key = get_option('lfbK');
        } else {
            $key = md5(uniqid(rand(), true));
            update_option('lfbK', $key);
        }
        return $key;
    }

}

endif;

$IDPAY_WPEFC = new IDPAY_WPEFC();
return $IDPAY_WPEFC->init();
