<?php
include "/var/www/html/mirakl_connect_rc/include/config_new.php";
ini_set('max_execution_time', 0);
header('Content-Type: text/html; charset=utf-8');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

use Bigcommerce\Api\Client as Bigcommerce;
use BigCommerce\ApiV3\Client as BigcommerceV3;
use BigCommerce\ApiV3\ResourceModels\Catalog\Product\ProductImage;
use BigCommerce\ApiV3\ResourceModels\Channel\ChannelCurrencyAssignment;

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
    
    $currencyAssignments = $bc_v3_api->channels()->currencyAssignments()->getAll()->getCurrencyAssignments();
    $currency =  $currencyAssignments[0]->default_currency;

    $get_product_count_api = 'https://api.bigcommerce.com/stores/'.$storeHash.'/v2/products/count';
    $method = 'GET';
    $header = ["Accept: application/json","Content-Type: application/json","X-Auth-Token: ".$access_token];

    $bc_product_count_response = BigC_API_Call($get_product_count_api,$method,$header);
    $bc_product_count_response_decoded = json_decode($bc_product_count_response);

    $total_count = $bc_product_count_response_decoded->count;
    $total_page_count = ceil($total_count/250);

    $bc_product_array = array();
    for($loop=1;$loop<=$total_page_count;$loop++){ 
        $get_product_api = 'https://api.bigcommerce.com/stores/'.$storeHash.'/v3/catalog/products?limit=250&include=custom_fields&page='.$loop;
    
        $bc_product_response = BigC_API_Call($get_product_api,$method,$header);
        $bc_product_response_decoded = json_decode($bc_product_response);
        $bc_product_array = array_merge($bc_product_array,$bc_product_response_decoded->data);
    }

    $mirakl_bc_products_sql = 'select tbl_bc_mirakl_product_id,product_name,product_sku,bc_product_id,category_code,main_image,tbl_stores_id,price, discount_price, inventory_level from tbl_bc_mirakl_products where tbl_stores_id = "'.$tbl_stores_id.'" and is_active = 1 and is_deleted = 0';

    $mirakl_bc_products_results = $conn->query($mirakl_bc_products_sql);
    $mirakl_bc_products = array();
    if ($mirakl_bc_products_results->num_rows > 0) {
        while($mbcp_row = $mirakl_bc_products_results->fetch_assoc()) {
            // if($mbcp_row['is_variant'] == 1){
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['bc_product_id']= $mbcp_row['bc_product_id'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['tbl_bc_mirakl_product_id']= $mbcp_row['tbl_bc_mirakl_product_id'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['product_name']= $mbcp_row['product_name'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['product_sku']= $mbcp_row['product_sku'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['category_code']= $mbcp_row['category_code'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['main_image']= $mbcp_row['main_image'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['tbl_stores_id']= $mbcp_row['tbl_stores_id'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['is_variant']= $mbcp_row['is_variant'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['variant_sku']= $mbcp_row['variant_sku'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['variant_id']= $mbcp_row['variant_id'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['sync_status']= $mbcp_row['sync_status'];
            //     $mirakl_bc_products[$mbcp_row['bc_product_id']][$mbcp_row['variant_sku']]['is_update']= $mbcp_row['is_update'];
            // }else{
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['bc_product_id']            = $mbcp_row['bc_product_id'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['tbl_bc_mirakl_product_id'] = $mbcp_row['tbl_bc_mirakl_product_id'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['product_name']             = $mbcp_row['product_name'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['product_sku']              = $mbcp_row['product_sku'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['category_code']            = $mbcp_row['category_code'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['main_image']               = $mbcp_row['main_image'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['tbl_stores_id']            = $mbcp_row['tbl_stores_id'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['price']                    = $mbcp_row['price'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['discount_price']           = $mbcp_row['discount_price'];
                $mirakl_bc_products[$mbcp_row['bc_product_id']]['inventory_level']          = $mbcp_row['inventory_level'];
            // }
        }
    }

    $product_list = [];
    if(count($bc_product_array) > 0){
        $i=0;
        foreach($bc_product_array as $individual_product){
            $marketplace_product = 0;
            foreach($individual_product->custom_fields as $check_marketplace_product){
                if($check_marketplace_product->name == 'marketplace' && $check_marketplace_product->value == 'true'){
                    $marketplace_product = 1;
                }
            }

            if($marketplace_product == 1){
                // echo '<pre>'; print_r($individual_product); echo '<pre>';
                $bc_product_id = $individual_product->id;
                $bc_product_categories = $individual_product->categories;
                foreach($bc_product_categories as $bc_product_categorie){
                    $getCategory = Bigcommerce::getCategory($bc_product_categorie);
                    $category_name = $getCategory->name;
                }

                $imageResponse = $bc_v3_api->catalog()->product($bc_product_id)->images()->getAll();
                $product_image = $imageResponse->getProductImages()[0]->url_standard;

                $discount_price = NULL;

                if($individual_product->sale_price){
                    $discount_price = $individual_product->sale_price;
                }

                if(array_key_exists($individual_product->id,$mirakl_bc_products)){
                    if(($mirakl_bc_products[$bc_product_id]['product_name'] != $individual_product->name) || ($mirakl_bc_products[$bc_product_id]['product_sku'] != $individual_product->sku) || ($mirakl_bc_products[$bc_product_id]['category_code'] != $category_name) || ($mirakl_bc_products[$bc_product_id]['main_image'] != $product_image) || ($mirakl_bc_products[$bc_product_id]['price'] != $individual_product->price) || ($mirakl_bc_products[$bc_product_id]['discount_price'] != $discount_price) || ($mirakl_bc_products[$bc_product_id]['inventory_level'] != $individual_product->inventory_level)){

                        $product_list['products'][$i]['id']                     = $individual_product->id;
                        // $product_list['products'][$i]['id']                     = 'iphone12-test';
                        $product_list['products'][$i]['gtins'][$i]['value']     = $individual_product->sku;
                        $product_list['products'][$i]['titles'][$i]['value']    = $individual_product->name;
                        $product_list['products'][$i]['titles'][$i]['locale']   = 'en_US';
                        $product_list['products'][$i]['images'][$i]['url']      = $product_image;
                        // $product_list['products'][$i]['images'][$i]['url']      = 'https://picsum.photos/200';
                        $product_list['products'][$i]['standard_prices'][$i]['price']['amount']     = $individual_product->price;
                        $product_list['products'][$i]['standard_prices'][$i]['price']['currency']   = $currency;
                        if($individual_product->sale_price){
                            $product_list['products'][$i]['discount_prices'][$i]['price']['amount']     = $individual_product->sale_price;
                            $product_list['products'][$i]['discount_prices'][$i]['price']['currency']   = $currency;
                        }
                        $product_list['products'][$i]['quantities'][$i]['available_quantity']   = $individual_product->inventory_level;

                        $products_update_sql = "update tbl_bc_mirakl_products set product_name = '".$individual_product->name."', product_sku = '".$individual_product->sku."', category_code = '".$category_name."', main_image = '".$product_image."', price = '".$individual_product->price."', discount_price = '".$discount_price."', inventory_level = '".$individual_product->inventory_level."' where tbl_bc_mirakl_product_id = ".$mirakl_bc_products[$bc_product_id]['tbl_bc_mirakl_product_id'];

                        $conn->query($products_update_sql);
                    }
                }else{
                        $product_list['products'][$i]['id']                     = $individual_product->id;
                        // $product_list['products'][$i]['id']                     = 'iphone12-test';
                        $product_list['products'][$i]['gtins'][$i]['value']     = $individual_product->sku;
                        $product_list['products'][$i]['titles'][$i]['value']    = $individual_product->name;
                        $product_list['products'][$i]['titles'][$i]['locale']   = 'en_US';
                        $product_list['products'][$i]['images'][$i]['url']      = $product_image;
                        // $product_list['products'][$i]['images'][$i]['url']      = 'https://picsum.photos/200';
                        $product_list['products'][$i]['standard_prices'][$i]['price']['amount']     = $individual_product->price;
                        $product_list['products'][$i]['standard_prices'][$i]['price']['currency']   = $currency;
                        if($individual_product->sale_price){
                            $product_list['products'][$i]['discount_prices'][$i]['price']['amount']     = $individual_product->sale_price;
                            $product_list['products'][$i]['discount_prices'][$i]['price']['currency']   = $currency;
                        }
                        $product_list['products'][$i]['quantities'][$i]['available_quantity']   = $individual_product->inventory_level;

                    $products_save_sql = "INSERT INTO tbl_bc_mirakl_products (product_name, product_sku, bc_product_id, category_code, main_image, price, discount_price, inventory_level, tbl_stores_id, is_active, is_deleted, created_at) VALUES ('".$individual_product->name."','".$individual_product->sku."','".$bc_product_id."','".$category_name."','".$product_image."','".$individual_product->price."','".$discount_price."','".$individual_product->inventory_level."','".$tbl_stores_id."', '1', '0', ".time()." )";

                    $conn->query($products_save_sql);
                }

                $i++;
            }
        }
    }

    if(count($product_list) > 0){
        $json_product_list = json_encode($product_list,JSON_UNESCAPED_SLASHES);
        
        $mirakl_URL     = miraklConnectURL();
        $m_access_token = miraklAccessToken($mirakl_client_id,$client_secret,$seller_company_id);
        
        $upsertProducts_api = $mirakl_URL.'/api/products';
        $m_method           = 'POST';
        $m_header           = ["Content-Type: application/json","Authorization: ".$m_access_token];

        $push_product_data = Mirakl_PRODUCT_POST_API_Call($upsertProducts_api,$m_method,$m_header,$json_product_list);
        $mirakl_status_code = miraklConnectStatus();
        echo $mirakl_status_code[$push_product_data['status_code']];

        // echo '<pre>'; print_r($push_product_data); echo '<pre>';
    }else{
        echo "No product to sync (or) All product are already sync";
    }
}
?>