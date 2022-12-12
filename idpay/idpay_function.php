<?php
/*
 * IDPay Advanced Class
 * version 	: 1.1.0
 * author 	: IDPay
 * e-mail 	: support@idpay.ir
 * website 	: https://idpay.ir
*/



class idpay_WPEFC_functions
{
    public function getStatus($status_code)
    {
        switch ($status_code) {
            case 1:
                return 'پرداخت انجام نشده است';
                break;
            case 2:
                return 'پرداخت ناموفق بوده است';
                break;
            case 3:
                return 'خطا رخ داده است';
                break;
            case 4:
                return 'بلوکه شده';
                break;
            case 5:
                return 'برگشت به پرداخت کننده';
                break;
            case 6:
                return 'برگشت خورده سیستمی';
                break;
            case 7:
                return 'انصراف از پرداخت';
                break;
            case 8:
                return 'به درگاه پرداخت منتقل شد';
                break;
            case 10:
                return 'در انتظار تایید پرداخت';
                break;
            case 100:
                return 'پرداخت تایید شده است';
                break;
            case 101:
                return 'پرداخت قبلا تایید شده است';
                break;
            case 200:
                return 'به دریافت کننده واریز شد';
                break;
            default :
                return 'خطای ناشناخته';
                break;
        }
    }

    public function call_gateway_endpoint( $url, $args ) {
        $number_of_connection_tries = 4;
        while ( $number_of_connection_tries ) {
            $response = wp_safe_remote_post( $url, $args );
            if ( is_wp_error( $response ) ) {
                $number_of_connection_tries --;
                continue;
            } else {
                break;
            }
        }
        return $response;
    }

    function filled_message( $message, $track_id, $order_id ) {
        return str_replace( [ "{track_id}", "{order_id}" ], [
            $track_id,
            $order_id,
        ], $message );
    }

    public function redirect($url)
    {
        $url = esc_url($url);
        @header('Location: '. $url);
        echo esc_html("<meta http-equiv='refresh' content='0; url={$url}' />");
        echo esc_html("<script>window.location.href = '{$url}';</script>");
        exit;
    }

    public function request($Api_key, $Order_id, $Amount, $Description="", $Email="", $Mobile="", $Name="", $CallbackURL, $SandBox=false)
    {
        $data = [
            'order_id' => $Order_id,
            'amount'   => $Amount,
            'name'     => $Name,
            'phone'    => $Mobile,
            'mail'     => $Email,
            'desc'     => $Description,
            'callback' => $CallbackURL,
        ];
        $headers = [
            'Content-Type' => 'application/json',
            'X-API-KEY'    => $Api_key,
            'X-SANDBOX'    => $SandBox ? 'true' : 'false',
        ];
        $args = [
            'body'    => json_encode( $data ),
            'headers' => $headers,
            'timeout' => 30,
        ];

        $response = $this->call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment', $args );
        if ( is_wp_error( $response ) )
        {
            return array(
                "Status" 	=> 0,
                "Message" 	=> "Network Error #:" . $response->get_error_message(),
                "Token"	    => "",
                "StartPay"  => "",
            );
        }

        $http_status = wp_remote_retrieve_response_code( $response );
        $result      = wp_remote_retrieve_body( $response );
        $result      = json_decode( $result );

        if ( $http_status != 201 || empty( $result ) || empty( $result->id ) || empty( $result->link ) )
        {
            return array(
                "Status" 	=> 0,
                "Message" 	=> "$result->error_message (code: $result->error_code)",
                "Token"	    => "",
                "StartPay"  => ""
            );
        } else {
            return array(
                "Status" 	=> 1,
                "Message" 	=> 'success',
                "Token" 	=> $result->id,
                "StartPay"  => $result->link
            );
        }
    }

    public function verify($Api_key, $Id, $Order_id, $SandBox=false)
    {
        $data = [
            'id'       => $Id,
            'order_id' => $Order_id,
        ];
        $headers = [
            'Content-Type' => 'application/json',
            'X-API-KEY'    => $Api_key,
            'X-SANDBOX'    => $SandBox ? 'true' : 'false',
        ];
        $args = [
            'body'    => json_encode( $data ),
            'headers' => $headers,
            'timeout' => 30,
        ];

        $response = $this->call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment/verify', $args );
        if ( is_wp_error( $response ) ) {
            return array(
                "Status" 	=> 0,
                "Message" 	=> "Network Error #:" . $response->get_error_message(),
                "Id"        => $Id,
                "Track_id"  => '',
            );
        }

        $http_status = wp_remote_retrieve_response_code( $response );
        $result      = wp_remote_retrieve_body( $response );
        $result      = json_decode( $result );

        if ( $http_status != 200 ) {
            return array(
                "Status" 	=> 0,
                "Message" 	=> sprintf( 'خطا: %s (کد: %s)', $result->error_message, $result->error_code ),
                "Id" 	    => "",
                "Track_id"  => '',
            );
        }
        else if ( $result->status != 100 )
        {
            return array(
                "Status" 	=> 0,
                "Message" 	=> sprintf( 'خطا: %s (کد: %s). کد رهگیری: %s', $this->getStatus($result->status), $result->status, $result->track_id ),
                "Id" 	    => "",
                "Track_id"  => '',
            );
        } else {
            return array(
                "Status" 	=> 1,
                "Message" 	=> sprintf('کد رهگیری:%s (شماره سفارش: %s)', $result->track_id, $Order_id ),
                "Id" 	    => $result->id,
                "Track_id"  => $result->track_id,
                "log"       => print_r($result, true)
            );
        }
    }

    public function isNotDoubleSpending($database,$reference, $order_id, $transaction_id)
    {
        global $wpdb;
        $sql = sprintf("SELECT * FROM %s WHERE `code` = %s", $reference, $order_id);
        $transaction = $database->get_row($wpdb->prepare($sql));
        if (!empty($transaction->token)) {
            return $transaction->token == $transaction_id;
        }
        return false;
    }
}
