<?php

if (!defined('ABSPATH')) {
    exit;
}

$websiteurl = esc_url(get_site_url()) ;
$time = date_i18n('Y/n/d G:m', $payment->time);
$amount = number_format($payment->amount);

if (strpos($transaction->content, '<form ') || strpos($transaction->content, '<strong>')) {
    $persianversion = str_replace('<strong>[order_type]</strong>', '', $transaction->content);

    $lastPos = 0;
    $positions = array();
    $toReplaceDefault = array();
    $toReplaceBy = array();
    while (($lastPos = strpos($persianversion, '<span class="lfb_value">', $lastPos)) !== false) {
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen('<span class="lfb_value">');
        $fileStartPos = $lastPos;
        $lastSpan = strpos($persianversion, '</span>', $fileStartPos);
        $value = substr($persianversion, $fileStartPos, $lastSpan - $fileStartPos);
        $toReplaceDefault[] = '<span class="lfb_value">' . $value . '</span>';
        $toReplaceBy[] = '<span class="lfb_value">' . $value . '</span>';
    }
    foreach ($toReplaceBy as $key => $value) {
        $persianversion = str_replace($toReplaceDefault[$key], $toReplaceBy[$key], $persianversion);
    }
    echo '<div class="lfb_logContainer">'. esc_html($persianversion) .'</div>';

} else {
    echo '<div class="lfb_logContainer">'.
        str_replace('<strong>[order_type]</strong>','', wp_specialchars_decode($transaction->content)) .
        '</div>';
}
?>
<div>
    <h2 style="margin: 20px 0 40px;">گزارش درگاه آیدی پی</h2>
    <table class="idpay-log <?php echo esc_html($transaction->paid) ? 'success-log': 'failed-log';?>">
        <thead>
        <tr>
            <th>مبلغ <?php echo esc_html($transaction->paid) ? 'پرداختی': '(پرداخت نشده)';?></th>
            <th>کدتراکنش</th>
            <th>زمان</th>
            <th>نام فرم</th>
            <th>لاگ</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?php echo esc_html($amount); ?></td>
            <td><?php echo esc_html($payment->token); ?></td>
            <td><?php echo esc_html($time); ?></td>
            <td><?php echo esc_html($transaction->formTitle); ?></td>
            <td>
                <pre style="text-align: left;direction: ltr;"><?php echo esc_html($payment->log); ?></pre>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<a class="button-primary back-button"
   href='<?php echo esc_html($websiteurl); ?>/wp-admin/admin.php?page=idpay_wpefc_transactions<?php echo esc_html($transaction->paid) ? '': '&unsuccess';?>'>بازگشت</a>
<?php echo file_get_contents(IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions_style.html'); ?>
