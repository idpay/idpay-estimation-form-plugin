<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$websiteurl      = get_site_url();
$url             = $websiteurl. '/wp-admin/admin.php?page=ep_transactions';
$ef_table_name   = $wpdb->prefix . "wpefc_logs";
$idpay_transactions = $wpdb->prefix . "wpefc_idpay_transactions";
$epstatus        = isset($_GET['unsuccess'])? false : true;

?>
<div class="wrap">
    <h1>Transactions</h1>
    <p style="margin-bottom: 50px; margin-top: 0px; font-size: 14px; letter-spacing: 1px;"><?php echo $epstatus? 'تراکنش های موفق': 'تراکنش های ناموفق'; ?></p>
    <ul style="display: flex">
        <li><a class="wpefc_transaction_btn" href="<?php echo $url;?>">تراکنش های موفق</a></li>
        <li><a class="wpefc_transaction_btn" href="<?php echo $url ."&unsuccess";?>">تراکنش های ناموفق</a></li>
    </ul>
    <table id="wpefc_transaction">
        <thead>
            <tr>
                <td>شماره سفارش(کد)</td>
                <td>نام و نام خانوادگی</td>
                <td>فرم</td>
                <td>تاریخ</td>
                <td>مبلغ</td>
                <td style="text-align:center ;width: 110px;">نمایش</td>
            </tr>
        </thead>
        <?php
        $paged = isset($_GET['pagenumber'])? sanitize_text_field($_GET['pagenumber']) : 1;
        $limit = 10;
        $min = $paged == 1 ? 0 : ($paged - 1) * $limit;
        $max = $paged * $limit;

        $sql = "select transactions.* , lg.ref, lg.email, lg.content, lg.formTitle, lg.firstName, lg.lastName 
            FROM $idpay_transactions transactions
            LEFT JOIN $ef_table_name lg ON (transactions.code = lg.id) 
            WHERE transactions.status ". ($epstatus ? '=' : '!=') ." '100' 
            ORDER BY id DESC 
            LIMIT $min, $max ";

        $transaction_list = $wpdb->get_results($sql,ARRAY_A);

        $count = "select count(*) FROM $idpay_transactions WHERE status ". ($epstatus ? '=' : '!=') ." '100'";
        $count = $wpdb->get_var($count);

        if(!$epstatus){
            echo '<style>
                    #wpwrap {
                        background: -webkit-linear-gradient(left, #6941a8, #cc5787) !important;
                        background: linear-gradient(to right, #6941a8, #cc5787) !important;
                    }
                    </style>';
        }
        else{
            echo '<style>	
                    #wpwrap{
                        background: -webkit-linear-gradient(left, #25c481, #25b7c4) !important;
                        background: linear-gradient(to right, #25c481, #25b7c4) !important;
                    }
                    </style>';
        }

        foreach ($transaction_list as $item){
            $name = string_decode($item['firstName'], 1).' '.string_decode($item['lastName'], 1);
            $time = date_i18n(get_option('date_format'), $item['time']);
            $amount = number_format($item['amount']);
            $content = '<tr>';
            $content .= "<td>$item[code]</td>";
            $content .= "<td>$name</td>";
            $content .= "<td>$item[formTitle]</td>";
            $content .= "<td>$time</td>";
            $content .= "<td>$amount</td>";
            $content .= "<td style='text-align: center;width: 110px;'><a style='margin: 0; background: transparent; font-weight: bold; padding: 5px 5px;' class='wpefc_transaction_btn' href='". $url ."_single&code=$item[code]&sql_id=$item[id]'>نمایش جزئیات</a></td>";
            $content .= '</tr>';
            echo $content;
        }
        $paginationbtn = "<ul style='display: flex;justify-content: center'>";
        $feppaged = $paged + 1;
        $beppaged = $paged - 1;
        $unsucces = $epstatus ? '':'&unsuccess';
        if($paged > 1){
            $paginationbtn .= "<li><a class='wpefc_transaction_btn' href='$url&pagenumber=$beppaged$unsucces'>صفحه قبل</a></li>";
        }
        if ((($count/$limit)/$paged) > 1){
            $paginationbtn .= "<li><a class='wpefc_transaction_btn' href='$url&pagenumber=$feppaged$unsucces'>صفحه بعد</a></li>";
        }

        $paginationbtn .= '</ul>';
        echo '</table>'. $paginationbtn;
        echo file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions_style.html');
        ?>
    </table>
</div>
