<?php
require_once dirname(__FILE__) . '/idpay_function.php';
GLOBAL $wpdb;

$status   = isset($_POST['status'])? sanitize_text_field( $_POST['status'] ) : '';
$track_id = isset($_POST['track_id'])? sanitize_text_field( $_POST['track_id'] ) : '';
$Token    = isset($_POST['id'])? sanitize_text_field( $_POST['id'] ) : '';
$order_id = isset($_POST['order_id'])? sanitize_text_field( $_POST['order_id'] ) : '';
$amount   = isset($_POST['amount'])? sanitize_text_field( $_POST['amount'] ) : '';
$card_no  = isset($_POST['card_no'])? sanitize_text_field( $_POST['card_no'] ) : '';
$date     = isset($_POST['date'])? sanitize_text_field( $_POST['date'] ) : '';

$id     = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
$logid  = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';

$sql    = "SELECT * FROM $this->idpay_transactions WHERE `token` = '$Token' AND `id` = '$id'";
$transaction = $wpdb->get_row($sql);

if(empty($order_id) || empty($Token) || empty($id) || empty($logid) || empty($transaction->token) || $transaction->token != $Token || $order_id != $logid){
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
            WHERE token = '$Token' AND id = '$id'");

            $wpdb->query("UPDATE ". $this->wpefc_logs ." SET paid = '1' WHERE id = '$logid'");

            $this->render_msg(1, $result["Message"]);
        } else {
            $wpdb->query("UPDATE $this->idpay_transactions 
            SET log = '". print_r(sanitize_text_field($_POST), true) ."' 
            WHERE token = '$Token' AND id = '$id'");

            $this->render_msg(0, $result["Message"]);
        }

    } else {
        $wpdb->query("UPDATE $this->idpay_transactions 
            SET log = '". print_r(sanitize_text_field($_POST), true) ."' 
            WHERE token = '$Token' AND id = '$id'");

        $wpdb->query("UPDATE $this->idpay_transactions SET `status` = '$status' WHERE `token` = '$Token'");
        $this->render_msg(0, sprintf('%s (کد: %s). کد رهگیری: %s', $idpay->getStatus($status), $status, $track_id ) );
    }
}
