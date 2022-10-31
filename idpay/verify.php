<?php
require_once dirname(__FILE__) . '/idpay_function.php';
global $wpdb;

$params = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$status = sanitize_text_field($params['status']);
$track_id = sanitize_text_field($params['track_id']);
$transId = sanitize_text_field($params['id']);
$order_id = sanitize_text_field($params['order_id']);


if (empty($order_id) || empty($transId) || empty($status) || empty($track_id)) {
    $this->render_msg(0, 'پارامتر های ورودی اشتباه هستند.');
}

//Check Double Spending
$idpay = new idpay_WPEFC_functions();
if ($idpay->isNotDoubleSpending($wpdb, $this->idpay_transactions, $order_id, $transId) == false) {
    $this->render_msg(0, 'پارامتر های ورودی اشتباه هستند.');
}
//End : Check Double Spending

//Check Payment Status in Callback
if ($status == 10) {
    $result = $idpay->verify($this->get_option('api_key'), $transId, $order_id, $this->get_option('sandbox'));

//Check Status Verify
    if (isset($result["Status"]) && $result["Status"] == 1) {
        $wpdb->query("UPDATE $this->idpay_transactions SET status = '100',token = '$result[Track_id]',log = '$result[log]' 
                            WHERE token = '$transId' AND code = '$order_id'");

        $wpdb->query("UPDATE " . $this->wpefc_logs . " SET paid = '1' WHERE id = '$order_id'");
        $this->render_msg(1, $result["Message"]);

    } else {
        $json = json_encode($params);
        $wpdb->query("UPDATE $this->idpay_transactions SET log = '$json' WHERE token = '$transId' AND code = '$order_id'");
        $this->render_msg(0, $result["Message"]);
    }

} else {
    $json = json_encode($params);
    //Update Payment Status
    $wpdb->query("UPDATE $this->idpay_transactions SET log = '$json'  WHERE token = '$transId' AND code = '$order_id'");
    //Update Order State
    $wpdb->query("UPDATE $this->idpay_transactions SET `status` = '$status'  WHERE `token` = '$transId'");
    //Redirect
    $this->render_msg(0, sprintf('%s (کد: %s). کد رهگیری: %s', $idpay->getStatus($status), $status, $track_id));
}
