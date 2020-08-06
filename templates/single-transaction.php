<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$websiteurl = get_site_url();
$time       = date_i18n('y/n/d G:m', $payment->time);
$amount     = number_format($payment->amount);

if(strpos($transaction->content, '<form ') || strpos($transaction->content, '<strong>')){
    $persianversion =  str_replace('<strong>[order_type]</strong>','',$transaction->content);
    /*
        $encodedemail = _find_between($persianversion, '<span class="lfb_value">', '</span>');
        $decodedemail = string_decode($encodedemail,1);
        echo str_replace($encodedemail,$decodedemail,$persianversion);
    */

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
        $toReplaceBy[] = '<span class="lfb_value">' . string_decode($value, 1) . '</span>';
    }
    foreach ($toReplaceBy as $key => $value) {
        $persianversion = str_replace($toReplaceDefault[$key], $toReplaceBy[$key], $persianversion);
    }
    echo $persianversion;

}else{
    echo str_replace('<strong>[order_type]</strong>','',string_decode($transaction->content,1));
}
if($transaction->paid):?>
    <div>
        <table style='margin: 0 auto; line-height: 30px; text-align: center;'>
            <thead>
            <tr>
                <th>مبلغ پرداخت شده</th><th>کدتراکنش</th><th>زمان پرداخت</th><th>نام فرم</th><th>لاگ</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $amount; ?></td>
                    <td><?php echo $payment->token; ?></td>
                    <td><?php echo $time; ?></td>
                    <td><?php echo $transaction->formTitle; ?></td>
                    <td><pre style="text-align: left;direction: ltr;"><?php echo $payment->log; ?></pre></td>
                </tr>
            </tbody>
        </table>
        <p style='opacity: 0'>.</p>
    </div>
    <a style='padding: 10px; margin: 10px auto; display: block; width: 200px; text-align: center; text-decoration: none; color: #fff; font-size: 15px; border-radius: 5px; background: linear-gradient(to right, #00edcd, #00c1ca); box-shadow: 2px 2px 5px #00c1ca82;' href='<?php echo $websiteurl;?>/wp-admin/admin.php?page=ep_transactions'>بازگشت</a>
<?php else: ?>
    <div>
        <table style='margin: 0 auto; line-height: 30px; text-align: center;'>
            <thead style='background: linear-gradient(to right, #d5280c, #b7004c);'>
            <tr>
                <th>مبلغ (پرداخت نشده)</th><th>کدتراکنش</th><th>زمان پرداخت</th><th>نام فرم</th><th>لاگ</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $amount; ?></td>
                    <td><?php echo $payment->token; ?></td>
                    <td><?php echo $time; ?></td>
                    <td><?php echo $transaction->formTitle; ?></td>
                    <td><pre style="text-align: left;direction: ltr;"><?php echo $payment->log; ?></pre></td>
                </tr>
            </tbody>
        </table>
        <p style='opacity: 0'>.</p>
    </div>
    <a style='padding: 10px; margin: 10px auto; display: block; width: 200px; text-align: center; text-decoration: none; color: #fff; font-size: 15px; border-radius: 5px; background: linear-gradient(to right, #00edcd, #00c1ca); box-shadow: 2px 2px 5px #00c1ca82;' href='<?php echo $websiteurl;?>/wp-admin/admin.php?page=ep_transactions'>بازگشت</a>
<?php endif;?>
<?php echo file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions_style.html'); ?>

<style>
    #adminmenuwrap{
        box-shadow: 0 0 20px #00000078;
    }
    #wpcontent{
        background: #fff;
        background-image: url(https://s3-us-west-2.amazonaws.com/s.cdpn.io/382994/image.jpg);
        background-size: cover;
        min-height: 100vh;
    }
    #wpbody{
        padding: 4% 6%;
    }
    #wpbody-content>div{
        width: 90%;
        background: #ffffffd4;
        margin: 20px auto;
        float: none;
        border-radius: 5px;
    }
    #wpbody-content .clear{
        display: none;
    }
    #wpbody-content p {
        color: #333 !important;
    }
    #wpbody-content hr {
        border: none;
        border-bottom: 1px solid #00BCD4 !important;
    }
    #wpcontent h2,#wpcontent strong{
        color: #00BCD4 !important;
    }
    #wpefc_transaction, #wpbody-content table{
        border: 1px solid #55b7dc !important;
    }
    #sfb_summaryTotalTr {
        background: #a9a9a945;
    }
    #wpcontent thead{
        background: linear-gradient(to right, #00edcd, #00c1ca);
    }
    #wpcontent thead span{
        color:#fff
    }
    #wpcontent tbody td,#wpcontent tbody span{
        color:#333 !important;
        font-size: 14px !important;
    }
    .update-nag,.notice,.media-upload-form .notice, .media-upload-form div.error, .wrap .notice, .wrap div.error, .wrap div.updated,.notice-error, div.error{display: none !important;}
</style>