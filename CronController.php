<?php

class CronController extends AppController {

    var $uses = array("BillEmailStatus","Order", "OrderItem", "User", "ShoppingCart", "Product", "Productlps", "ProductDeliveryDetails", "Eshops", "DeliveryMethodlps", "ProcessingTimes", "EshopPaymentMethods", "Transactions", "OrderFeedbacks", "OrderStatus", "Settings", "ProductImages", "OrderStatusLogs", "Bill", "BillItem", "BillStatus");
    public $components = array('Email', 'OrderBase', 'Search', 'Breadcrumb', 'DropDown');
    var $helpers = array("Paginator", "Time", "Pagesorting", "Seofriendly");

    public function beforeFilter() {
        parent::beforeFilter();
        //$this->layout=null;
        $this->Auth->allow("sendBillEmail","sendBillReminder","test2", "test", "sendOrderReminder", "createbills", "sendEmail", "fetchOrdersByStatus");
    }

    public function test() {
        // Check the action is being invoked by the cron dispatcher 
        if (!defined('CRON_DISPATCHER')) {
            $this->redirect('/');
            exit();
        }

        //no view
        $this->autoRender = false;

        //do stuff...
        echo "\r\n Done";

        return;
    }

    function sendOrderReminder() {
        $this->autoRender = false;
        // for Buyers
        $statusIds = "2,3,7,8,1,4,6,12";
        $data = $this->fetchOrdersByStatus($statusIds);
        //code to check the status and send email to buyers
        if (count($data) > 0) {

            for ($i = 0; $i < count($data); $i++) {

                //print_r($data); 

                $id = $data[$i]['orders']['id'];
                $order_number = $data[$i]['orders']['order_number'];
                $status = $data[$i]['orders']['order_status'];
                $daysold = $data[$i][0]['daysold'];
                $order_date = $data[$i]['orders']['order_date'];

                $statusChangeData = $this->getOrderStatusChangeDate($id, $status);
                $statusChangeDate = $statusChangeData[0][0]['stausDate'];


                //fetch the email content
                $emailContentData = $this->getReminderEmailContent($status);
                //print_r($emailContentData);


                $reminder_email_content = $emailContentData['OrderStatus']['reminder_email_content'];
                $reminder_email_subject = $emailContentData['OrderStatus']['reminder_email_subject'];
                $reminder_to_be_send_on_day = $emailContentData['OrderStatus']['reminder_to_be_send_on_day'];
                $reminder_to_be_send_on_day_arr = explode(",", $reminder_to_be_send_on_day);
                $reminder_email_send_to = $emailContentData['OrderStatus']['reminder_email_send_to'];
                $seller_email_subject = $emailContentData['OrderStatus']['seller_email_subject'];
                $buyer_email_subject = $emailContentData['OrderStatus']['buyer_email_subject'];
                echo "<bR> daysold : " . $daysold;
                // print_r($reminder_to_be_send_on_day_arr);

                if (in_array($daysold, $reminder_to_be_send_on_day_arr)) {

                    echo "<br> Order " . $id . " Reminder need to be send";


                    //exit();
                    //get user name and email from order
                    $userData = $this->getUserDetails($id);
                    //print_r($userData);
                    //exit();

                    $userFirstName = $userData['Users']['firstname'];
                    $userLastName = $userData['Users']['lastname'];
                    $userEmail = $userData['Users']['email'];
                    //$userEmail = 'rajeev.ranjan@sparxtechnologies.com';
                    $order_total = $userData['Order']['order_total'];
                    $shipping_price = $userData['Order']['shipping_price'];
                    $order_delay = $userData['Order']['delivery_delay'] . " " . ucfirst($userData['ProcessingTimes']['description']);

                    //get shopDetails
                    $selected_eshop_id = $userData['Order']['eshop_id'];
                    $shopData = $this->getShopDetails($selected_eshop_id);
                    $shopName = $shopData['Eshops']['name'];
                    $shopCurrency = $shopData['Currencies']['symbol'];
                    $shopEmail = $shopData['Users']['email'];

                    if ($reminder_email_send_to == "seller") {
                        //replace the needed data
                        $reminder_email_content = str_replace("##FIRSTNAME##", $userFirstName, $reminder_email_content);
                        $reminder_email_content = str_replace("##ORDER_DATE##", $order_date, $reminder_email_content);
                        $reminder_email_content = str_replace("##LASTNAME##", $userLastName, $reminder_email_content);
                        $reminder_email_content = str_replace("##SHOP_NAME##", $shopName, $reminder_email_content);
                        $reminder_email_content = str_replace("##REFUSAL_DATE##", $statusChangeDate, $reminder_email_content);
                        $reminder_email_content = str_replace("##SHOP_CURRENCY##", $shopCurrency, $reminder_email_content);
                        $reminder_email_content = str_replace("##ORDER_TOTAL_AMOUNT##", $order_total, $reminder_email_content);
                        $reminder_email_content = str_replace("##DELAY##", $order_delay, $reminder_email_content);
                        $reminder_email_content = str_replace("##ORDER_NUMBER##", "#" . $order_number, $reminder_email_content);
                        $reminder_email_content = str_replace("##LINK_TEXT_ORDER##", __("Goto My Account"), $reminder_email_content);
                        $reminder_email_content = str_replace("##SITE_URL##", SITE_URL, $reminder_email_content);
                        $reminder_email_content = str_replace("##SHIPPING_COST##", $shopCurrency . $shipping_price, $reminder_email_content);
                        $reminder_email_content = str_replace("##ACCEPTION_DATE##", $statusChangeDate, $reminder_email_content);
                        $reminder_email_content = str_replace("##PAYMENT_DATE##", $statusChangeDate, $reminder_email_content);


                        $orderDetailPageUrl = SITE_URL . "orders/orders";
                        $reminder_email_content = str_replace("##LINK_TO_ORDER##", $orderDetailPageUrl, $reminder_email_content);

                        //send the email
                        $sendTo = $shopEmail;
                        //$sendTo = 'rajeev.ranjan@sparxtechnologies.com';
                        // $sendTo='team@afrikrea.com';
                        echo "<br>sendTo " . $sendTo."   subject :".$seller_email_subject;
                        echo "<br>reminder_email_content " . $reminder_email_content;
                        //exit();                      
                        $this->sendEmail($reminder_email_content, $sendTo, $seller_email_subject);
                    }

                    if ($reminder_email_send_to == "buyer") {
                        //replace the needed data
                        $reminder_email_content = str_replace("##FIRSTNAME##", $userFirstName, $reminder_email_content);
                        $reminder_email_content = str_replace("##LASTNAME##", $userLastName, $reminder_email_content);
                        $reminder_email_content = str_replace("##SHOP_NAME##", $shopName, $reminder_email_content);
                        $reminder_email_content = str_replace("##SHOP_CURRENCY##", $shopCurrency, $reminder_email_content);
                        $reminder_email_content = str_replace("##ORDER_TOTAL_AMOUNT##", $order_total, $reminder_email_content);
                        $reminder_email_content = str_replace("##DELAY##", $order_delay, $reminder_email_content);
                        $reminder_email_content = str_replace("##ORDER_NUMBER##", "#" . $order_number, $reminder_email_content);
                        $reminder_email_content = str_replace("##LINK_TEXT_ORDER##", __("Goto My Account"), $reminder_email_content);
                        $reminder_email_content = str_replace("##SITE_URL##", SITE_URL, $reminder_email_content);
                        $reminder_email_content = str_replace("##ACCEPTION_DATE##", $statusChangeDate, $reminder_email_content);

                        $orderDetailPageUrl = SITE_URL . "purchases/inprogress";
                        $reminder_email_content = str_replace("##LINK_TO_ORDER##", $orderDetailPageUrl, $reminder_email_content);

                        $orderRefusePaymentUrl = SITE_URL . "orders/refuse_payment/" . $order_number;
                        $reminder_email_content = str_replace("##LINK_TO_ORDER2##", $orderRefusePaymentUrl, $reminder_email_content);
                        $reminder_email_content = str_replace("##LINK_TEXT_ORDER2##", __("Refuse Payment"), $reminder_email_content);

                        //send the email
                        $sendTo = $userEmail;
                        //$sendTo = 'rajeev.ranjan@sparxtechnologies.com';
                        //$sendTo='team@afrikrea.com';
                        echo "<br>sendTo " . $sendTo; //die;
                        echo "<br>reminder_email_content " . $reminder_email_content;
                        $this->sendEmail($reminder_email_content, $sendTo, $buyer_email_subject);
                    }
                } else {
                    echo "<br> Order " . $id . " no reminder need to be send";
                }
            }
        }

        exit();
    }

    function fetchOrdersByStatus($statusIds) {

//        $sql = "SELECT orders.*,DATEDIFF(NOW(),orders.`modified`) AS daysold 
//      FROM orders
//      WHERE 
//      orders.`order_status` IN (" . $statusIds . ")
//      AND DATEDIFF(NOW(),orders.`modified`) >= 1";
       $sql = "SELECT orders.*,DATEDIFF(subdate(current_date, 1),orders.`modified`) AS daysold 
      FROM orders
      WHERE 
      orders.`order_status` IN (" . $statusIds . ")
      AND DATEDIFF(subdate(current_date, 1),orders.`modified`) >= 1";
        $data = $this->Order->query($sql);

//      echo "<pre>";print_r($data);
//
//      exit();
        return $data;
    }

    function getReminderEmailContent($status) {

        $emailContent = $this->OrderStatus->find("first", array("fields" => array("seller_email_subject","buyer_email_subject","reminder_email_send_to", "reminder_to_be_send_on_day", "reminder_email_subject", "reminder_email_content"), "conditions" => array("id='" . $status . "'")));

        return $emailContent;
    }

    function getOrderStatusChangeDate($order_id, $status) {

        $sql = "SELECT DATE(order_status_logs.`created`) as stausDate
      FROM order_status_logs WHERE order_id = '" . $order_id . "' AND order_status_logs.`new_status`='" . $status . "'";

        $data = $this->OrderStatusLogs->query($sql);

        return $data;
    }

    function getUserDetails($id) {

        $data = $this->Order->find("first", array('joins' => array(
                array(
                    'table' => 'users',
                    'alias' => 'Users',
                    'type' => 'INNER',
                    'conditions' => array('Users.id = Order.user_id')
                ),
                array(
                    'table' => 'processing_times',
                    'alias' => 'ProcessingTimes',
                    'type' => 'INNER',
                    'conditions' => array('ProcessingTimes.id = Order.delay_option', 'ProcessingTimes.language_id=' . $this->Session->read('languageid'))
                )
            ), "fields" => array("Order.shipping_price", "Order.delivery_delay", "ProcessingTimes.description", "Order.eshop_id", "Order.order_total", "Users.firstname", "Users.lastname", "Users.email"), "conditions" => array("Order.id='" . $id . "'")));

        return $data;
    }

    function getShopDetails($eshop_id) {

        $data = $this->Eshops->find("first", array('joins' => array(
                array(
                    'table' => 'currencies',
                    'alias' => 'Currencies',
                    'type' => 'INNER',
                    'conditions' => array('Currencies.id = Eshops.currency', 'Currencies.active = 1')
                ),
                array(
                    'table' => 'users',
                    'alias' => 'Users',
                    'type' => 'INNER',
                    'conditions' => array('Users.id = Eshops.user_id')
                )
            ), "fields" => array("name", "Currencies.symbol", "Users.email"), "conditions" => array("Eshops.id='" . $eshop_id . "'")));

        return $data;
    }

    function sendEmail($content = null, $sendTo = null, $subject = null) {
//        $sendTo = "rajeev.ranjan@sparxtechnologies.com";
        if (empty($subject)) {
            $subject = "test subject";
        }
//        $content = "test content";
        $settingData = $this->Settings->find('all', array(
            'fields' => array('order_from_name', 'order_from_email')
                ));
        if (ENV == 'dev') {
            
        } else {
            $from = $settingData[0]['Settings']['order_from_email'];
            $fromName = $settingData[0]['Settings']['order_from_name'];
            $Email = new CakeEmail();
            $Email->template($this->Session->read('languagevalue') . '_status_changed')
                    ->viewVars(array('message' => $content))
                    ->emailFormat('html')
                    ->subject($subject)
                    ->to($sendTo)
                    ->from(array($from => $fromName))
                    ->send();
        }
    }

    function createbills() {

        /*
          $d = new DateTime();
          $weekday = $d->format('w');
          $diff = 7 + $weekday ; // Monday=0, Sunday=6

          echo "<br>".$d->format('Y-m-d') . ' - ';
          echo "<br> weekday : ".$weekday;
          echo "<br>diff ".$diff;

          $d->modify("-$diff day");
          echo "<br>".$d->format('Y-m-d') . ' - ';
          $d->modify('+6 day');
          echo "<br>".$d->format('Y-m-d');

          echo "<hr>"; */
        $this->autoRender = false;
        $currentDateTime = date('Y-m-d H:i:s');
        echo "\r\n Current time " . $currentDateTime;

        $weekday = date("w");
        echo "<br> Todays Weekday : " . $weekday;

        $diff = 7 + $weekday; // Monday=0, Sunday=6
        //echo "<br>diff ".$diff;

        $bill_fromDate = date('Y-m-d', strtotime('-' . $diff . ' days'));
        $bill_toDate = date('Y-m-d', strtotime($bill_fromDate . '+6 days'));
        echo "<br>Bill From Date " . $bill_fromDate;
        echo "<br>Bill To Date " . $bill_toDate;

        //transaction completed
        $statusIds = '3,6,7,8,9,12';

        //get orders that are completed in date range $bill_fromDate - $bill_toDate
        $sql = "SELECT orders.eshop_id,orders.order_currency, GROUP_CONCAT(
      CONCAT(orders.id,'|',orders.`order_number`,'|',orders.`subtotal`)
      ) as id_num,
      sum(orders.subtotal) as amount
      FROM orders,order_status_logs
      WHERE 
      orders.id = order_status_logs.order_id and
      orders.`order_status` in (" . $statusIds . ") and
      order_status_logs.new_status in (" . $statusIds . ") and
      date(order_status_logs.created) between '" . $bill_fromDate . "' and '" . $bill_toDate . "'
      group by orders.eshop_id
      order by orders.id
      ";

        //echo $sql;

        $data = $this->Order->query($sql);

        if (count($data) > 0) {
            echo "<hr>";
            echo "<br> Bill Creation Process Started";

            echo "<br> Total Bills to be created = " . count($data);
            echo "<hr>";
            for ($i = 0; $i < count($data); $i++) {

                //print_r($data); 
                //fetch the AFRIKREA_COMMISSION_PERCENTAGE
                $settingData = $this->Settings->find('all', array(
                    'fields' => array('afrikrea_bill_percentage')
                        ));
                $AFRIKREA_COMMISSION_PERCENTAGE = $settingData[0]['Settings']['afrikrea_bill_percentage'];

                $eshop_id = $data[$i]['orders']['eshop_id'];
                $bill_currency = $data[$i]['orders']['order_currency'];
                $amount = $data[$i]['0']['amount'];
                $commission = ($amount * $AFRIKREA_COMMISSION_PERCENTAGE) / 100;

                $order_id_number = $data[$i]['0']['id_num'];
                $idNumArr = explode(",", $order_id_number);

                echo "<br>Eshop_id  : " . $eshop_id;
                // echo "<br>Order Details  : ".$order_id_number ;
                echo "<br>Amount  : " . $amount;

                //create a Bill
                $count = $this->Bill->find('count', array("conditions" => array('eshop_id' => $eshop_id, 'bill_date' => date("Y-m-d"), 'bill_fromdate' => $bill_fromDate, 'bill_todate' => $bill_toDate)));
                if (!$count) {
                    $billFields = array('Bill' => array('eshop_id' => $eshop_id, 'bill_date' => date("Y-m-d"), 'bill_fromdate' => $bill_fromDate, 'bill_todate' => $bill_toDate
                            , 'bill_status' => 1, 'amount' => $amount, 'commission_rate' => $AFRIKREA_COMMISSION_PERCENTAGE, 'commission' => $commission, 'bill_currency' => $bill_currency
                            ));


                    $this->Bill->create();
                    $this->Bill->save($billFields);
                    $bill_id = $this->Bill->id;


                    // create billing number as "AFNNII"

                    $sql2 = "select count(id) as cnt from bills where eshop_id = '" . $eshop_id . "'";
                    $billdata = $this->Bill->query($sql2);
                    //print_r($billdata);
                    if (count($billdata) > 0) {
                        for ($j = 0; $j < count($billdata); $j++) {

                            $bill_number_id = $billdata[$j][0]['cnt'];
                        }
                    }

                    echo "<bR> bill_number_id : " . $bill_number_id;

                    if ($bill_number_id) {
                        if ($bill_number_id < 10)
                            $bill_created_id = "0" . $bill_number_id;
                        else
                            $bill_created_id = $bill_number_id;

                        $billing_number = "AF" . $bill_created_id . $eshop_id;

                        $billdata = array('id' => $bill_id, 'billing_number' => $billing_number);
                        $this->Bill->save($billdata);
                    }

                    foreach ($idNumArr as $key => $value) {
                        $val = $value;

                        $valArr = explode("|", $val);
                        $order_id = $valArr[0];
                        $order_number = $valArr[1];
                        $subtotal = $valArr[2];

                        echo "<br>Order id  : " . $order_id;
                        echo "<br>Order Number : " . $order_number;
                        echo "<br>Sub Total : " . $subtotal;

                        //create a Bill Item
                        $this->BillItem->create();
                        $billitems = array('BillItem' => array('bill_id' => $bill_id, 'order_id' => $order_id, 'order_number' => $order_number, 'order_amount' => $subtotal));
                        //print_r($billitems);
                        $this->BillItem->save($billitems);
                    }

                    echo "<br> Bill Created With ID : " . $bill_id;

                    echo "<hr>";
                    $this->sendBillEmail($bill_id);
                }
            }
        }
        //echo $_SESSION['query'];
        echo "<br>Bill Creation Process Completed";
        exit();
    }
 
  function sendBillEmail($bill_id=null){
      $this->autoRender = false;
      $status=1;//$bill_id=2;
      if($bill_id){
      $data=  $this->fetchBillsById($bill_id);
      for ($i = 0; $i < count($data); $i++) { 
          $shopData = $this->getShopDetails($data[$i]['bills']['eshop_id']);
          $billData=$this->getBillDetails($bill_id,$data[$i]['bills']['eshop_id']);
          $this->BillItem->bindModel(array('belongsTo' => array('Order')), false);
          $billItem = $this->BillItem->find("all", array("fields" => array("BillItem.*", "Order.order_date"), "conditions" => array("bill_id" =>$bill_id)));
          $emailContentData = $this->getBillEmailContent($status);
          
          
        $reminder_email_content = $emailContentData['BillEmailStatus']['reminder_email_content'];
        $reminder_email_subject = $emailContentData['BillEmailStatus']['reminder_email_subject'];
        $reminder_to_be_send_on_day = $emailContentData['BillEmailStatus']['reminder_to_be_send_on_day'];
        $reminder_email_send_to = $emailContentData['BillEmailStatus']['reminder_email_send_to'];
        $seller_email_subject = $emailContentData['BillEmailStatus']['seller_email_subject'];
        $seller_email_content = $emailContentData['BillEmailStatus']['seller_email_content'];
        if ($reminder_email_send_to == "seller") {
                        //replace the needed data
                    $reminder_email_content = str_replace("##SHOP_NAME##", $shopData['Eshops']['name'], $reminder_email_content);
                    $reminder_email_content = str_replace("##BILL_NUMBER##",$billData['Bill']['billing_number'], $reminder_email_content);
                    $reminder_email_content = str_replace("##BILL_TOTAL_AMOUNT##", $billData['Bill']['bill_currency'].$billData['Bill']['commission'], $reminder_email_content);
                    $reminder_email_content = str_replace("##BEGIN_DATE##", strftime("%d-%B-%Y",strtotime($billData['Bill']['bill_fromdate'])), $reminder_email_content);
                    $reminder_email_content = str_replace("##END_DATE##", strftime("%d-%B-%Y",strtotime($billData['Bill']['bill_todate'])), $reminder_email_content);
                    $reminder_email_content = str_replace("##GENERATION_DATE##", strftime("%d-%B-%Y",strtotime($billData['Bill']['created'])), $reminder_email_content);
                    $reminder_email_content = str_replace("##LINK_TEXT_ORDER##", __("Goto My Account"), $reminder_email_content);
                    $reminder_email_content = str_replace("##SITE_URL##", SITE_URL, $reminder_email_content);
                    $billPageUrl = SITE_URL . "orders/bill_view/".$bill_id;
                    $reminder_email_content = str_replace("##LINK_TO_ORDER##", $billPageUrl, $reminder_email_content);
                    $seller_email_subject=str_replace("[BILL_NUMBER]",$billData['Bill']['billing_number'],$seller_email_subject);
                    //echo $seller_email_subject;
                    $sendTo = $shopData['Users']['email'];
                   // echo $reminder_email_content;exit;
                    //send the email
                    
                    //$sendTo = 'rajeev.ranjan@sparxtechnologies.com';
                    // $sendTo='team@afrikrea.com';
                    //echo "<br>sendTo " . $sendTo."   subject :".$seller_email_subject;
                    //echo "<br>reminder_email_content " . $reminder_email_content;
                    //exit();                      
                    $this->sendEmail($reminder_email_content, $sendTo, $seller_email_subject);
                    $this->sendEmail($reminder_email_content, 'team@afrikrea.com', $seller_email_subject);
                    }
         
      }
      }
  }
  function fetchBillsById($id=null) {
      $sql = "SELECT bills.*,DATEDIFF(subdate(current_date, 1),bills.`created`) AS daysold 
      FROM bills
      WHERE 
      bills.`bill_status`=1 AND bills.id='".$id."'";
      $data = $this->Bill->query($sql);
      return $data;
    }
  function fetchBillsByStatus() {
       $sql = "SELECT bills.*,DATEDIFF(subdate(current_date, 1),bills.`created`) AS daysold 
      FROM bills
      WHERE 
      bills.`bill_status`=1
      AND DATEDIFF(subdate(current_date, 1),bills.`created`) >= 1";
        $data = $this->Bill->query($sql);
        return $data;
    }
  function getBillDetails($bill_id,$shop_id){
      $joins = array(
            array(
                'table' => 'bill_statuses',
                'alias' => 'BillStatus',
                'type' => 'INNER',
                'conditions' => array('BillStatus.bill_status = Bill.bill_status', 'BillStatus.active = 1', 'BillStatus.language_id="' . $this->Session->read('languageid') . '"')
            ),
            array(
                'table' => 'eshops',
                'alias' => 'Eshops',
                'type' => 'INNER',
                'conditions' => array('Eshops.id = Bill.eshop_id')
            ),
            array(
                'table' => 'currencies',
                'alias' => 'Currencies',
                'type' => 'INNER',
                'conditions' => array('Currencies.id = Eshops.currency', 'Currencies.active = 1')
            )
        );

        $billData = $this->Bill->find("first", array("fields" => "Bill.*,BillStatus.name,BillStatus.color,BillStatus.text_color,BillStatus.icon,Currencies.symbol", "joins" => $joins, "conditions" => array("Eshops.id" =>$shop_id,"Bill.id"=>$bill_id)));
        return $billData;
      
  }
  
  function getBillEmailContent($status) {

        $emailContent = $this->BillEmailStatus->find("first", array("fields" => array("seller_email_content","seller_email_subject","reminder_email_send_to", "reminder_to_be_send_on_day", "reminder_email_subject", "reminder_email_content"), "conditions" => array("bill_status='" . $status . "'")));

        return $emailContent;
    }

  
  function sendBillReminder(){
       $this->autoRender = false;
      $data=  $this->fetchBillsByStatus();
      for ($i = 0; $i < count($data); $i++) { 
          $shopData = $this->getShopDetails($data[$i]['bills']['eshop_id']);
          $billData=$this->getBillDetails($data[$i]['bills']['id'],$data[$i]['bills']['eshop_id']);
          $this->BillItem->bindModel(array('belongsTo' => array('Order')), false);
          $billItem = $this->BillItem->find("all", array("fields" => array("BillItem.*", "Order.order_date"), "conditions" => array("bill_id" =>$data[$i]['bills']['id'])));
//          echo '<pre>';
//          print_r($shopData);
//          echo '<br>////////////////////////////////////////////<br>';
//          print_r($billData);
//          echo '<br>////////////////////////////////////////////<br>';
//          print_r($billItem);
          $html='Bill :&nbsp;'.strftime("%d-%B-%Y",strtotime($billData['Bill']['bill_fromdate'])).' To '.strftime("%d-%B-%Y",strtotime($billData['Bill']['bill_todate']))."<br>";
          $html.='Commision Rate :&nbsp;'.floatval($billData['Bill']['commission_rate'])."%<br>";
          $html.="Bill Number :&nbsp;".$billData['Bill']['billing_number']."&nbsp;&nbsp;Bill Amount :&nbsp;".$billData['Bill']['bill_currency'].$billData['Bill']['commission']." Order Amount :".$billData['Bill']['bill_currency'].$billData['Bill']['amount']."<br>";
          $html.='Bill Items :&nbsp;'."<br>";
          $html.='<table width="580" style="border:1 solid;"><tr><th>Order Number</th><th>Order Amount</th><th>Order Date</th></tr>';
          foreach($billItem as $billItem_tmp){
             $html.='<tr><td>#'.$billItem_tmp['BillItem']['order_number'].'</td><td>'.$billData['Bill']['bill_currency'].$billItem_tmp['BillItem']['order_amount'].'</td><td>'.strftime("%d-%B-%Y",strtotime($billItem_tmp['Order']['order_date'])).'</td></tr>' ;
          }
          $html.='</table>';
          $sendTo=$shopData['Users']['email'];
          $subject='Bill Reminder';
          $this->sendEmail($html, $sendTo, $subject);
          echo $html;
      }
      exit;
  }
}

?>