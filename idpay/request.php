<?php

require_once dirname(__FILE__) . '/idpay_function.php';
GLOBAL $wpdb;

$idpay 	     = new idpay_WPEFC_functions();
$amount      = str_replace(',', '', $order['amount']);
$time        = time();
$Description = $order['ref'];  // Required

$sql = "INSERT INTO $this->idpay_transactions (`code`,`amount`,`time`,`email`,`token`,`status`,`log`) VALUES ('$order[id]','$amount','$time','$order[email]','', '0', '')";

if($wpdb->query($sql) == TRUE){
    $dblastid = $wpdb->insert_id;
} else {
    $this->render_msg(0, 'در ارتباط با دیتابیس خطایی رخ داده است.');
}

$CallbackURL = get_home_url()."?wpef_idpay=verify&payment=idpay&sub_id=$dblastid&order_id=$order[id]";
$amount      = $amount * ( ($this->get_option('currency') == 'toman') ? 10 : 1 );

$result = $idpay->request($this->get_option('api_key'), $order['id'], $amount, $Description, $order['email'], $order['phone'], $order['name'], $CallbackURL, $this->get_option('sandbox'));

if (isset($result["Status"]) && $result["Status"] == 1) {
    $sql = "UPDATE $this->idpay_transactions SET token = '$result[Token]', log = 'انتقال به بانک' WHERE `id` = '$dblastid'";
    if($wpdb->query($sql)){
        $idpay->redirect($result["StartPay"]);
    }
    else{
        $sql = "UPDATE $this->idpay_transactions SET status = '200' WHERE `id` = '$dblastid'";
        $wpdb->query($sql);
        $this->render_msg(0,'در ارتباط با دیتابیس خطا رخ داده است.');
    }

} else {
    $sql = "UPDATE $this->idpay_transactions SET status = '201', log = '$result[Message]' WHERE `id` = '$dblastid'";
    $this->render_msg(0, $result['Message']);
}

