<?php
if ( ! defined( "ABSPATH" ) ) {
	exit;
}

$websiteurl = get_home_url();
$idpay_payment = "<div class=\'bank_paymnt\' title=\'idpay\' style=\'margin: 5px 5px;cursor: pointer;ppadding: 5px; background: #eee; border-radius: 5px; border: 4px solid #ddd; width: 150px; position: relative; padding: 15px 0;\'><img width=\'150\' style=\'margin: auto;\' src=\'$websiteurl/wp-content/plugins/idpay-wp-estimation-form/assets/logo.svg\'><h3 style=\'font-size: 13px; margin: 5px 0;\'>درگاه آیدی پی</h3></div>";
$wpesfpayments = "<div style=\'display: flex;justify-content: center\'>$idpay_payment</div>";
$style = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/result_style.html');
?>

<script>jQuery(document).ready(function ($) {
    var baseUrl = '<?php echo $websiteurl ?>';
    var paymentredirect;
    paymentloader();
    function paymentloader() {
        if($('#lfb_bootstraped').length){
            var email = $('#lfb_bootstraped input[name=email]').val();
            var curentprice = $('.progress-bar-price span').text().match(/\d+/);
            if($('#finalText').css('display') != 'none' && curentprice != 0 && email != ''){
                paymentredirect = 1;
                d5payments();
            }
        }
    setTimeout(function () {
        if(!paymentredirect)
        paymentloader();
    },500)
}

function d5payments() {
    var email = $('#lfb_bootstraped input[name=email]').val();
    $('body').prepend('<?php echo $style?><div id=\"wpefgmsg\"><div> <div class=\"wpefgmsgclose\" onClick=\"wpefgmsgclose();\">X</div> <h1 style=\"margin: -20px 0 0 0;\"> پرداخت امن با آیدی پی</h1><p style=\"color: #777; margin-bottom: 10px;\">درگاه پرداخت کلیه کارت های عضو شبکه شتاب</p> <?php echo $wpesfpayments ?> <a style=\"background: #03A9F4; color: #fff; border-radius: 5px; width: 80%; display: block; margin: 0 auto; margin-top: 21px; line-height: 35px; margin-bottom: -10px; font-size: 14px;\" href=\"'+baseUrl+'/?wpef_idpay=pay&email='+email+'\">پرداخت</a></div></div>');
    setTimeout(function() {
        $('.bank_paymnt').click(function() {
          $('.bank_paymnt').css({'border': '4px solid #ccc'});
          $(this).css({'border': '4px solid #03A9F4'});
          $('#wpefgmsg a').attr('href',baseUrl+'/?wpef_idpay=pay&email='+email+'&payment='+$(this).attr('title'));
        });
        $('.wpefgmsgclose').click(function() {
          $('#wpefgmsg').fadeOut(500);
        })
    },50)
}
})</script>";