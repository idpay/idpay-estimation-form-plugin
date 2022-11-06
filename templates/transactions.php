<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

GLOBAL $wpdb;
$websiteurl      = get_site_url();
$url             = $websiteurl. '/wp-admin/admin.php?page=idpay_wpefc_transactions';
$epstatus        = isset($_GET['unsuccess'])? false : true;

?>
<div class="wrap <?php echo $epstatus? 'success-transactions': 'failed-transactions';?>">
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

        $sql = "select transactions.* , lg.ref, lg.content, lg.formTitle, lg.firstName, lg.lastName 
            FROM $this->idpay_transactions transactions
            LEFT JOIN $this->wpefc_logs lg ON (transactions.code = lg.id) 
            WHERE transactions.status ". ($epstatus ? '=' : '!=') ." '100' 
            ORDER BY id DESC 
            LIMIT $min, $max ";

        $transaction_list = $wpdb->get_results($sql,ARRAY_A);

        $count = "select count(*) FROM $this->idpay_transactions WHERE status ". ($epstatus ? '=' : '!=') ." '100'";
        $count = $wpdb->get_var($count);

        foreach ($transaction_list as $item){
            $time = date_i18n( 'Y/n/d G:m', $item['time']);
            $amount = number_format($item['amount']);
            $content = '<tr>';
            $content .= "<td>$item[code]</td>";
            $content .= "<td>$item[email]</td>";
            $content .= "<td>$item[formTitle]</td>";
            $content .= "<td style=\"direction: ltr;\">$time</td>";
            $content .= "<td>$amount</td>";
            $content .= "<td><a class='button' href='". $url ."_single&code=$item[code]&sql_id=$item[id]'>نمایش جزئیات</a></td>";
            $content .= '</tr>';
            echo $content;
        }
        $paginationbtn = "<ul style='display: flex;justify-content: center'>";
        $feppaged = $paged + 1;
        $beppaged = $paged - 1;
        $unsucces = $epstatus ? '':'&unsuccess';
        if($paged > 1){
            $paginationbtn .= "<li><a class='button' href='$url&pagenumber=$beppaged$unsucces'>صفحه قبل</a></li>";
        }
        if ((($count/$limit)/$paged) > 1){
            $paginationbtn .= "<li><a class='button' href='$url&pagenumber=$feppaged$unsucces'>صفحه بعد</a></li>";
        }

        $paginationbtn .= '</ul>';
        echo '</table>'. $paginationbtn;
        echo file_get_contents( IDPAY_WPEFC_PLUGIN_PATH . 'templates/transactions_style.html');
        ?>
    </table>
</div>
