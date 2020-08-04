<?php
$script_actionmsg = '';
function result_msg($type,$code)
{
    global $options;
    global $script_actionmsg;
    $sucsess = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'assets/success.svg' );
    $faild = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'assets/error.svg');
    $style = file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/result_style.html');

    $script_actionmsg1 = "<script>
        jQuery(document).ready(function ($) {
            $('body').prepend('$style<div id=\"wpefgmsg\"><div> <div class=\"wpefgmsgclose\" onClick=\"wpefgmsgclose();\">X</div> $sucsess <p style=\"color: #777;\">$options[sucssesmsg]</p><p style=\"margin-top:30px;color: #2196F3;\"><b></b>$code </p></div></div>');
        }); 
        function wpefgmsgclose(){
            jQuery('#wpefgmsg').fadeOut(500);
        } 
    </script>";

    $script_actionmsg2 = "<script>
    jQuery(document).ready(function ($) {
            $('body').prepend('$style<div id=\"wpefgmsg\"><div> <div class=\"wpefgmsgclose\" onClick=\"wpefgmsgclose();\">X</div> $faild <p style=\"color: #777;\">$options[faildmsg]</p><p style=\"margin-top:30px;color: #d1403f;\"><b>خطا: </b> $code </p></div></div>');
        }); 
        function wpefgmsgclose(){
            jQuery('#wpefgmsg').fadeOut(500);
        } </script>";
    $script_actionmsg = $type ? $script_actionmsg1 : $script_actionmsg2;
    add_action('wp_footer', 'print_footer_function');
}

function print_footer_function() {
    GLOBAL $script_actionmsg;
    echo $script_actionmsg;
}