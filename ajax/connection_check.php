<?php
require_once '../vendor/autoload.php';
include "../include/config_new.php";

if(isset($_REQUEST['mirakl_connect_client_id']) && isset($_REQUEST['mirakl_connect_client_secret']) && isset($_REQUEST['mirakl_connect_seller_company_id'])){
    $mirakl_connect_client_id           = $_REQUEST['mirakl_connect_client_id'];
    $mirakl_connect_client_secret       = $_REQUEST['mirakl_connect_client_secret'];
    $mirakl_connect_seller_company_id   = $_REQUEST['mirakl_connect_seller_company_id'];

    $m_access_token = miraklAccessToken($mirakl_connect_client_id,$mirakl_connect_client_secret,$mirakl_connect_seller_company_id);

    if(strpos($m_access_token,'invalid_') !== false){
        echo "Unauthorized";
    }else{
        echo "Authorized";
    }
}else{
    echo "Unauthorized";
}
?>