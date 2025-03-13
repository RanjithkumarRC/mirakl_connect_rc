<?php
include "/var/www/html/mirakl_connect_rc/include/config_new.php";
ini_set('max_execution_time', 0);
header('Content-Type: text/html; charset=utf-8');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

use Bigcommerce\Api\Client as Bigcommerce;
use BigCommerce\ApiV3\Client as BigcommerceV3;
use Firebase\JWT\JWT;
use Guzzle\Http\Client;
use Handlebars\Handlebars;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use BigCommerce\ApiV2\ResourceModels\Order\Order;
use BigCommerce\ApiV2\ResourceModels\Order\OrderProduct;
use BigCommerce\Tests\V2\V2ApiClientTest;

$store_array = array();
if(isset($_REQUEST['data'])){
    $encode_current_storehash	=  $_REQUEST['data'];
	$decode_current_storehash	=  base64_decode($encode_current_storehash);
    $select_store_sql = "SELECT tbl_stores_id, storehash, access_token, mirakl_client_id, mirakl_client_secret, mirakl_seller_company_id FROM tbl_stores where storehash = '".$decode_current_storehash."' and is_active=1 and is_deleted=0";
}else{
    $select_store_sql = "SELECT tbl_stores_id, storehash, access_token, mirakl_client_id, mirakl_client_secret, mirakl_seller_company_id FROM tbl_stores where is_active=1 and is_deleted=0";
}
$store_result = $conn->query($select_store_sql);

if($store_result->num_rows > 0) {
    while($store_row = $store_result->fetch_assoc()) {
        $store_array[]= $store_row;
    }
}else{
    echo 'Unauthorized';
    die();
}

foreach($store_array as $ind_stores){
    $storeHash                  = $ind_stores['storehash'];
    $access_token               = $ind_stores['access_token'];
    $tbl_stores_id              = $ind_stores['tbl_stores_id'];
    $mirakl_client_id           = $ind_stores['mirakl_client_id'];
    $client_secret              = $ind_stores['mirakl_client_secret'];
    $seller_company_id          = $ind_stores['mirakl_seller_company_id'];

    configureBCApiNew($storeHash,$access_token);
    $client_id = clientId();
    $bc_v3_api = new BigcommerceV3($storeHash, $client_id, $access_token);

    $miraklApiURL   = miraklConnectURL();
    $miraklApiKey   = miraklAccessToken($mirakl_client_id,$client_secret,$seller_company_id);

    $check_mc_order_dataexists = "select tbl_mirakl_connect_orders_id , mirakl_order_id, mirakl_order_status, sync_status_mirakl, bc_order_id, sync_status_bc, bc_order_status from tbl_mirakl_connect_orders where tbl_stores_id = 3";
    $check_mc_dataexists_result = $conn->query($check_mc_order_dataexists);

    $mirakl_orders_ids      = [];
    $mirakl_orders_details  = [];
    $bc_orders_ids          = [];
    $bc_orders_details      = [];
    if($check_mc_dataexists_result->num_rows > 0) {
        while($order_rows = $check_mc_dataexists_result->fetch_assoc()) {
          $mirakl_orders_ids[]= $order_rows['mirakl_order_id'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['tbl_mirakl_connect_orders_id'] = $order_rows['tbl_mirakl_connect_orders_id'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['mirakl_order_id'] = $order_rows['mirakl_order_id'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['mirakl_order_status'] = $order_rows['mirakl_order_status'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['bc_order_status'] = $order_rows['bc_order_status'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['sync_status_bc'] = $order_rows['sync_status_bc'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['sync_status_mirakl'] = $order_rows['sync_status_mirakl'];
          $mirakl_orders_details[$order_rows['mirakl_order_id']]['bc_order_id'] = $order_rows['bc_order_id'];
          // echo "<pre>";
          // print_r($mirakl_orders_details);
          // echo "</pre>";
          
  
            if(!empty($order_rows['bc_order_id'])){
                $bc_orders_ids[] = $order_rows['bc_order_id'];
                $bc_orders_details[$order_rows['bc_order_id']]['tbl_mirakl_connect_orders_id'] = $order_rows['tbl_mirakl_connect_orders_id'];
                $bc_orders_details[$order_rows['bc_order_id']]['mirakl_order_id'] = $order_rows['mirakl_order_id'];
                $bc_orders_details[$order_rows['bc_order_id']]['mirakl_order_status'] = $order_rows['mirakl_order_status'];
                $bc_orders_details[$order_rows['bc_order_id']]['bc_order_status'] = $order_rows['bc_order_status'];
                $bc_orders_details[$order_rows['bc_order_id']]['sync_status_bc'] = $order_rows['sync_status_bc'];
                $bc_orders_details[$order_rows['bc_order_id']]['sync_status_mirakl'] = $order_rows['sync_status_mirakl'];
                $bc_orders_details[$order_rows['bc_order_id']]['bc_order_id'] = $order_rows['bc_order_id'];
            }
        }
    }

    $mirakl_orders_ids[1] = 'abcd';
    if(empty($mirakl_orders_ids)){
        // $get_orders_api = $miraklApiURL.'/orders';
        // $method = 'GET';
        // $header = ["Accept: application/json","Content-Type: application/json","Authorization: ".$miraklApiKey];

        $get_orders_api     = $miraklApiURL.'/api/orders';
        $m_method           = 'GET';
        $m_header           = ["Accept: application/json","Content-Type: application/json","Authorization: ".$miraklApiKey];

        $get_mirakl_orders_lists = Mirakl_API_Call($get_orders_api,$m_method,$m_header);
        $mirakl_order_lists      = json_decode($get_mirakl_orders_lists);

        if($mirakl_order_lists->data){
            foreach($mirakl_order_lists->data as $mirakl_order_list){
                // $insert_mc_order_sql = "INSERT INTO tbl_mirakl_connect_orders (mirakl_order_id, mirakl_order_status, tbl_stores_id, sync_status_mirakl,is_update_mirakl,created_at) VALUES ('".$mirakl_order_list->order_id."','".$mirakl_order_list->order_state."',3, 'PENDING',0, ".time()." )";
                $insert_mc_order_sql = "INSERT INTO tbl_mirakl_connect_orders (mirakl_order_id, mirakl_order_status, tbl_stores_id, sync_status_mirakl,is_update_mirakl,created_at) VALUES ('".$mirakl_order_list->order_id."','".$mirakl_order_data->status->state."','".$tbl_stores_id."', 'PENDING',0, ".time()." )";
                if ($conn->query($insert_mc_order_sql) === TRUE) {
                    echo "New record created successfully";
                } else {
                    echo "Error: " . $insert_mc_order_sql . "<br>" . $conn->error;
                }
            }
        }
    }else{
        $order_update_datetime      = date(DATE_ISO8601, strtotime('-48 hour'));
        $order_update_date_array    = explode('+',$order_update_datetime);
        $order_update_date          = $order_update_date_array[0]."Z";
        $get_orders_api             = $miraklApiURL.'/api/orders?updated_from='.$order_update_date;
        $m_method                   = 'GET';
        $m_header                   = ["Accept: application/json","Content-Type: application/json","Authorization: ".$miraklApiKey];

        $get_mirakl_orders_lists    = Mirakl_API_Call($get_orders_api,$m_method,$m_header);
        $mirakl_order_lists         = json_decode($get_mirakl_orders_lists);
        
        if($mirakl_order_lists->data){
            foreach ($mirakl_order_lists->data as $get_order_result) {
                if(array_key_exists($get_order_result->order_id,$mirakl_orders_details)){
                    if($get_order_result->order_state != $mirakl_orders_details[$get_order_result->order_id]['mirakl_order_status']){
                        $update_mirakl_order_status = 'update tbl_mirakl_connect_orders set mirakl_order_status = "'.$get_order_result->order_state.'", is_update_mirakl = 1, sync_status_mirakl = "PENDING", updated_at_mirakl = '.time().' where tbl_mirakl_connect_orders_id = '.$mirakl_orders_details[$get_order_result->order_id]['tbl_mirakl_connect_orders_id'];
    
                        if ($conn->query($update_mirakl_order_status) === TRUE) {
                            echo "Updated successfully";
                        } else {
                            echo "Error: " . $update_mirakl_order_status . "<br>" . $conn->error;
                        }
                    }
                }else{
                    $insert_order_sql = "INSERT INTO tbl_mirakl_connect_orders (mirakl_order_id, mirakl_order_status, tbl_stores_id, sync_status_mirakl, created_at) VALUES ('".$get_order_result->order_id."','".$get_order_result->order_state."',3, 'PENDING', ".time()." )";
    
                    if ($conn->query($insert_order_sql) === TRUE) {
                        echo "New record created successfully";
                    } else {
                        echo "Error: " . $insert_order_sql . "<br>" . $conn->error;
                    }
                }
            }
        }
    }

    // Fetching orders status from BigCommerce
    // tbl_mirakl_connect_orders
    if(!empty($bc_orders_details)){
        $last_updated_date  = date(DATE_ISO8601, strtotime('-48 hour'));
        $bc_order_count     = Bigcommerce::getOrdersCount();

        $total_page_count   = ceil($bc_order_count/250);
        $filter_array       = [];
        $bc_order_page_array= [];
        $filter_array['min_date_modified'] = $last_updated_date;
        for($loop=1;$loop<=$total_page_count;$loop++){
            $filter_array['limit']  = 250;
            $filter_array['page']   = $loop;

            $bc_orders_array        = Bigcommerce::getOrders($filter_array);
            $bc_order_page_array    = array_merge($bc_order_page_array,$bc_orders_array);
        }


        foreach($bc_orders_array as $individual_order){
            $bc_order_id = $individual_order->id;
            if(in_array($bc_order_id,$bc_orders_ids)){
                // echo $individual_order->status_id;
                if(($individual_order->status_id != $bc_orders_details[$bc_order_id]['bc_order_status']) && ($bc_orders_details[$bc_order_id]['sync_status_mirakl'] != 'PENDING') && ($individual_order->status_id == 2)){
                    $bc_shipment_orders_array = Bigcommerce::getShipments($bc_order_id);
                    if((!empty($bc_shipment_orders_array[0]->shipping_provider)) || (!empty($bc_shipment_orders_array[0]->tracking_carrier))){
                        $update_bc_order_status = 'update tbl_mirakl_connect_orders set bc_order_status = "'.$individual_order->status_id.'", is_update_bc = 1, sync_status_bc = "PENDING", updated_at_bc = '.time().' where tbl_mirakl_connect_orders_id  = '.$bc_orders_details[$bc_order_id]['tbl_mirakl_connect_orders_id'];

                        if ($conn->query($update_bc_order_status) === TRUE) {
                            echo "Updated successfully";
                        } else {
                            echo "Error: " . $update_bc_order_status . "<br>" . $conn->error;
                        }
                    }
                }
                /*
                shipping tracking
                or24 - validate shipping order(proced to shipment)
                */
            }
        }
    }
}


  
  

   
    //Mirakl Connect API Call 
    //$order_list_array = array();
    // $get_orders_api = $miraklApiURL.'/orders?limit=40';
    // $method = 'GET';
    // $header = ["Accept: application/json","Content-Type: application/json","Authorization: ".$miraklApiKey];

    // $get_mirakl_orders_lists = Mirakl_API_Call($get_orders_api,$method,$header);
    // $mirakl_order_lists = json_decode($get_mirakl_orders_lists);
   

    // foreach($mirakl_order_lists->orders as $mirakl_order_list){
       
    //     $insert_mc_order_sql = "INSERT INTO tbl_mirakl_connect_orders (mirakl_order_id, mirakl_order_status, tbl_stores_id, sync_status_mirakl,is_update_mirakl,created_at) VALUES ('".$mirakl_order_list->order_id."','".$mirakl_order_list->order_state."',3, 'PENDING',0, ".time()." )";
    //     if ($conn->query($insert_mc_order_sql) === TRUE) {
    //         echo "New record created successfully";
    //     } else {
    //         echo "Error: " . $insert_mc_order_sql . "<br>" . $conn->error;
    //     }
    // }

    //get the mirakl_order_id from DB
    // $allowed_orders = "";
    // $tbl_mirakl_connect_order_data = "SELECT * FROM tbl_mirakl_connect_orders where tbl_stores_id = 3 and sync_status_mirakl = 'PENDING' AND (is_update_mirakl = 0 OR bc_order_id is NULL ) AND mirakl_order_status not in ('WAITING_ACCEPTANCE','WAITING_DEBIT_PAYMENT','REFUSED','CANCELED','CLOSED')";
    
    // $all_mc_order_result = $conn->query($tbl_mirakl_connect_order_data);
    // if ($all_mc_order_result->num_rows > 0) {
    //     $i=0;
    //     while($all_mirakl_order_row = $all_mc_order_result->fetch_assoc()) {
    //         if($i==0){
    //             $allowed_orders .= $all_mirakl_order_row['mirakl_order_id'];
    //             }else{
    //             $allowed_orders .= ','.$all_mirakl_order_row['mirakl_order_id'];
    //             }
    //         $i++;
             
    //     }
   

    //     echo $allowed_orders;
        
    // }
    // die;

    //     $get_each_orders_api = $miraklApiURL.'/orders?order_ids='.$allowed_orders;
    //     $method = 'GET';
    //     $header = ["Accept: application/json","Content-Type: application/json","Authorization: ".$miraklApiKey];
    
    //     $get_mirakl_each_orders= Mirakl_API_Call($get_each_orders_api,$method,$header);
    //     $get_mirakl_each_orders_decode = json_decode($get_mirakl_each_orders);
      
    //     foreach ($get_mirakl_each_orders_decode->orders as $miraklConnectOrder) { 
    //         $miraklconnect_order_decoded[] = $miraklConnectOrder;
    //         // echo "<pre>";
    //         // print_r($miraklconnect_order_decoded);
    //         // echo "</pre>";
        
    //     }

    //     foreach($miraklconnect_order_decoded as $mirakl_order_data){
    //         $mc_order_data = [];
    //         $mc_order_id = $mirakl_order_data->order_id;
    //         $mc_order_status = $mirakl_order_data->order_state;

    //         // product data
    //         $bc_order_product_array = [];
    //         foreach($mirakl_order_data->order_lines as $individualOrderProduct) {
    //             $mirakl_individual_product_array = [];
    //             $mirakl_individual_product_array['name'] = $individualOrderProduct->product_title;
    //             // $mirakl_individual_product_array['product_id'] = $individualOrderProduct->offer->id;  (bigcommerce product id map)
    //             $mirakl_individual_product_array['quantity'] = $individualOrderProduct->quantity;
    //             $mirakl_individual_product_array['price_inc_tax'] = $individualOrderProduct->price;
    //             $mirakl_individual_product_array['price_ex_tax'] = $individualOrderProduct->price;
    //             // $mirakl_individual_product_array['status_id'] = $individualOrderProduct->status->state;
    //             $bc_order_product_array[] = $mirakl_individual_product_array;
    //         }
    //         $order_data['products'] = $bc_order_product_array;

    //         //billing address
    //         $order_data['billing_address']['first_name'] = $mirakl_order_data->customer->billing_address->firstname;
    //         $order_data['billing_address']['last_name'] = $mirakl_order_data->customer->billing_address->lastname;
    //         // $order_data['billing_address']['company'] = $mirakl_order_data->customer->billing_address->company;
    //         $order_data['billing_address']['street_1'] = $mirakl_order_data->customer->billing_address->street_1;
    //         // $order_data['billing_address']['street_2'] = $mirakl_order_data->customer->billing_address->street_2;
    //         $order_data['billing_address']['city'] = $mirakl_order_data->customer->billing_address->city;
    //         $order_data['billing_address']['state'] = $mirakl_order_data->customer->billing_address->state;
    //         $order_data['billing_address']['zip'] = $mirakl_order_data->customer->billing_address->zip_code;
    //         // $order_data['billing_address']['country'] = $mirakl_order_data->customer->billing_address->country;
    //         $order_data['billing_address']['country'] = "United States";

    //         // $order_data['billing_address']['country_iso2'] = $mirakl_order_data->customer->billing_address->country_iso_code;
    //         $order_data['billing_address']['country_iso2'] = 'US';
    //         $order_data['billing_address']['phone'] = $mirakl_order_data->customer->billing_address->phone;
    //         // $customer_email = explode("-",$mirakl_order_data->customer->customer_id);
    //         $order_data['billing_address']['email'] = "vaishnavi.ar@royalcyber.com";

    //         $mirakl_bc_order_status_array = [];
    //         $mirakl_bc_order_status_array['WAITING_ACCEPTANCE'] = '7'; //Awaiting Payment
    //         $mirakl_bc_order_status_array['REFUSED'] = '5'; //Cancelled
    //         $mirakl_bc_order_status_array['WAITING_DEBIT'] = '7'; //Awaiting Payment
    //         $mirakl_bc_order_status_array['WAITING_DEBIT_PAYMENT'] = '7'; //Awaiting Payment
    //         $mirakl_bc_order_status_array['PAYMENT_COLLECTED'] = '11'; //Awaiting Fulfillment
    //         $mirakl_bc_order_status_array['SHIPPING'] = '9'; //Awaiting Shipment
    //         $mirakl_bc_order_status_array['TO_COLLECT'] = '8'; //Awaiting Pickup
    //         $mirakl_bc_order_status_array['SHIPPED'] = '2'; //Shipped
    //         $mirakl_bc_order_status_array['RECEIVED'] = '10'; //Completed
    //         $mirakl_bc_order_status_array['CLOSED'] = '10'; //Completed
    //         $mirakl_bc_order_status_array['CANCELED'] = '5'; //Completed
    //         $mirakl_bc_order_status_array['REFUNDED'] = '4'; //Refunded

    //         $order_data['status_id'] = $mirakl_bc_order_status_array[$mc_order_status];
    //         // echo "<pre>";
    //         // print_r($order_data);
    //         // echo "</pre>";

    //         // shipping address
    //         $order_shipping_data = [];
    //         $order_shipping_data['first_name'] = $mirakl_order_data->customer->shipping_address->firstname;
    //         $order_shipping_data['last_name'] = $mirakl_order_data->customer->shipping_address->lastname;
    //         // $order_shipping_data['company'] = $mirakl_order_data->customer->shipping_address->company;
    //         $order_shipping_data['street_1'] = $mirakl_order_data->customer->shipping_address->street_1;
    //         // $order_shipping_data['street_2'] = $mirakl_order_data->customer->shipping_address->street_2;
    //         $order_shipping_data['city'] = $mirakl_order_data->customer->shipping_address->city;
    //         $order_shipping_data['state'] = $mirakl_order_data->customer->shipping_address->state;
    //         $order_shipping_data['zip'] = $mirakl_order_data->customer->shipping_address->zip_code;
    //         $order_shipping_data['country'] = "Australia";
    //         // $order_shipping_data['country'] = $mirakl_order_data->customer->shipping_address->country;
    //         // $order_shipping_data['country_iso2'] = $mirakl_order_decoded->customer->shipping_address->firstname;
    //         $order_shipping_data['country_iso2'] = 'AU';
    //         // $order_shipping_data['phone'] = $mirakl_order_data->customer->shipping_address->phone;
    //         $order_shipping_data['email'] = "vaishnavi.ar@royalcyber.com";
    //         $order_data['shipping_addresses'][] = $order_shipping_data;

           
    //         // echo '<pre>'; print_r($order_data); echo '</pre>';
            
    //         try {
    //             $json_order_data = json_encode($order_data);
    //             // echo $json_order_data;
    //             $BC_order_response = Bigcommerce::createOrder($json_order_data);
    //             // echo "<pre>";
    //             // print_r($BC_order_response);
    //             // echo "</pre>";
                
    //             if($BC_order_response->id != 'undefined') {
    //                 $BC_order_id = '';
    //                 $BC_order_id = $BC_order_response->id;
    //                 $BC_order_status_id = $BC_order_response->status_id;
    //                 echo $mc_order_id;
                    
    //                 $update_bc_order_query    =   "update tbl_mirakl_connect_orders set sync_status_mirakl ='COMPLETED',  bc_order_id=".$BC_order_id.", bc_order_status='".$BC_order_status_id."', updated_at_mirakl=".time().", is_update_mirakl = 0 where mirakl_order_id = '".$mc_order_id."' and tbl_stores_id = 3";
                
    //                 if ($conn->query($update_bc_order_query) === TRUE) {
    //                             echo "New record created successfully";
    //                         } else {
    //                             echo "Error: " . $update_bc_order_query . "<br>" . $conn->error;
    //                         }
    //             }
        
    //         } catch (\Exception $e) {
    //             // An exception is thrown if the requested object is not found or if an error occurs
    //             // var_dump($e);
    //             echo "<pre>";
    //             echo "**********";
    //             print_r($e);
    //             echo "</pre>";
    //         }


    //     }
    
    function Mirakl_API_Call($api,$method,$header){
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => $api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 30,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $header,
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }
?>