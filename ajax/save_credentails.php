<?php
include "../include/config_new.php";

use Mirakl\MMP\Shop\Client\ShopApiClient;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;

if(isset($_REQUEST['mirakl_connect_client_id']) && isset($_REQUEST['mirakl_connect_client_secret']) && isset($_REQUEST['mirakl_connect_seller_company_id']) && isset($_REQUEST['bc_storehash']) && isset($_REQUEST['bc_accesstoken'])){
  $mirakl_connect_client_id           = $_REQUEST['mirakl_connect_client_id'];
  $mirakl_connect_client_secret       = $_REQUEST['mirakl_connect_client_secret'];
  $mirakl_connect_seller_company_id   = $_REQUEST['mirakl_connect_seller_company_id'];
  $bc_storehash                       = $_REQUEST['bc_storehash'];
  $bc_accesstoken                     = $_REQUEST['bc_accesstoken'];

  $update_store_cred_sql = "UPDATE tbl_stores SET mirakl_client_id='".$mirakl_connect_client_id."', mirakl_client_secret='".$mirakl_connect_client_secret."', mirakl_seller_company_id='".$mirakl_connect_seller_company_id."' WHERE is_active='1' and is_deleted='0' and storehash='".$bc_storehash."'";

  if ($conn->query($update_store_cred_sql) === TRUE) {
    echo "Record updated successfully";
  } else {
    echo "Error updating record: " . $conn->error;
  }
}else{
    echo "Unauthorized";
}
?>