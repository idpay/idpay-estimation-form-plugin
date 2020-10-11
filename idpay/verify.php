<?php
require_once dirname(__FILE__) . '/idpay_function.php';
GLOBAL $wpdb;

$status    = !empty($_POST['status'])  ? sanitize_text_field($_POST['status'])   : (!empty($_GET['status'])  ? sanitize_text_field($_GET['status'])   : NULL);
$track_id  = !empty($_POST['track_id'])? sanitize_text_field($_POST['track_id']) : (!empty($_GET['track_id'])? sanitize_text_field($_GET['track_id']) : NULL);
$Token     = !empty($_POST['id'])      ? sanitize_text_field($_POST['id'])       : (!empty($_GET['id'])      ? sanitize_text_field($_GET['id'])       : NULL);
$order_id  = !empty($_POST['order_id'])? sanitize_text_field($_POST['order_id']) : (!empty($_GET['order_id'])? sanitize_text_field($_GET['order_id']) : NULL);
$params    = !empty($_POST['id']) ? $_POST : $_GET;

$sub_id = isset($_GET['sub_id']) ? sanitize_text_field($_GET['sub_id']) : '';
$logid  = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';

$sql    = "SELECT * FROM $this->idpay_transactions WHERE `token` = '$Token' AND `id` = '$sub_id'";
$transaction = $wpdb->get_row($sql);

if(empty($order_id) || empty($Token) || empty($sub_id) || empty($logid) || empty($transaction->token) || $transaction->token != $Token || $order_id != $logid){
    $this->render_msg(0,'پارامتر های ورودی اشتباه هستند.');
}
else{
    $idpay 	= new idpay_WPEFC_functions();

    if ($status == 10) {
        $result = $idpay->verify($this->get_option('api_key'), $Token, $logid, $this->get_option('sandbox'));

        if (isset($result["Status"]) && $result["Status"] == 1) {
            $wpdb->query("UPDATE $this->idpay_transactions 
            SET status = '100',
                token = '$result[Track_id]',
                log = '$result[log]' 
            WHERE token = '$Token' AND id = '$sub_id'");

            $wpdb->query("UPDATE ". $this->wpefc_logs ." SET paid = '1' WHERE id = '$logid'");

            $this->render_msg(1, $result["Message"]);
        } else {
            $wpdb->query("UPDATE $this->idpay_transactions 
            SET log = '". print_r(sanitize_text_field($params), true) ."' 
            WHERE token = '$Token' AND id = '$sub_id'");

            $this->render_msg(0, $result["Message"]);
        }

    } else {
        $wpdb->query("UPDATE $this->idpay_transactions 
            SET log = '". print_r(sanitize_text_field($params), true) ."' 
            WHERE token = '$Token' AND id = '$sub_id'");

        $wpdb->query("UPDATE $this->idpay_transactions SET `status` = '$status' WHERE `token` = '$Token'");
        $this->render_msg(0, sprintf('%s (کد: %s). کد رهگیری: %s', $idpay->getStatus($status), $status, $track_id ) );
    }
}
