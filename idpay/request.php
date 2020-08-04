<?php

require_once dirname(__FILE__) . '/idpay_function.php';
GLOBAL $wpdb;

$error = 0;
$amount = str_replace(',', '', $info['amount']);
$time = time();
$sql = "INSERT INTO $idpay_transactions (`code`,`amount`,`time`,`email`,`token`,`status`,`log`) 
        VALUES ('$info[id]','$amount','$time','$info[email]','', '0', '')";
if($wpdb->query($sql) == TRUE){
    $dblastid = $wpdb->insert_id;
}else{
    result_msg(0,100);
}
$Description = $info['ref'];  // Required
$CallbackURL = get_home_url()."?wpef_idpay=verify&payment=idpay&id=$dblastid&order_id=$info[id]";

$idpay 	= new idpay();
$amount = $amount * ( ($options['currency'] == 'toman') ? 10 : 1 );

$result = $idpay->request($options['api_key'], $info['id'], $amount, $Description, $info['email'], $info['phone'], $info['name'], $CallbackURL, $options['sandbox']);

if (isset($result["Status"]) && $result["Status"] == 1) {
    $sql = "UPDATE $idpay_transactions SET token = '$result[Token]', log = 'انتقال به بانک' WHERE `id` = '$dblastid'";
    if($wpdb->query($sql)){
        $idpay->redirect($result["StartPay"]);
    }
    else{
        $sql = "UPDATE $idpay_transactions SET status = '200' WHERE `id` = '$dblastid'";
        $wpdb->query($sql);
        result_msg(0,'در ارتباط با دیتابیس خطا رخ داده است.');
    }

} else {
    $sql = "UPDATE $idpay_transactions SET status = '201', log = '$result[Message]' WHERE `id` = '$dblastid'";
    result_msg(0, $result['Message']);
}

