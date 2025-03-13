<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once '/var/www/html/mirakl_connect_rc/vendor/autoload.php';

use Mirakl\MCI\Shop\Client\ShopApiClient as Mirakl_Client;
use Bigcommerce\Api\Client as Bigcommerce;
use BigCommerce\ApiV3\Client as BigcommerceV3;

$dotenv = new Dotenv\Dotenv('/var/www/html/mirakl_connect_rc/');
$dotenv->load();

$conn = new mysqli(getenv('DATABASE_SERVER'), getenv('DATABASE_USERNAME'), getenv('DATABASE_PASSWORD'), getenv('DATABASE_NAME'));
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function configureBCApiNew($storeHash,$access_token)
{
	Bigcommerce::configure(array(
		'client_id' => clientId(),
		'auth_token' => $access_token,
		'store_hash' => $storeHash
	));
}
function clientId()
{
	$clientId = getenv('BC_CLIENT_ID');
	return $clientId ?: '';
}
function countryOfOrigin()
{
	$str_countryOfOrigin = getenv('COUNTRY_OF_ORIGIN');
	$countryOfOrigin = str_replace('_',' ',$str_countryOfOrigin);
	return $countryOfOrigin ?: '';
}
function PDPTemplate()
{
	$PDPTemplate = getenv('PDP_TEMPLATE');
	return $PDPTemplate ?: '';
}
function enableProductReviews()
{
	$enableProductReviews = getenv('ENABLE_PRODUCT_REVIEWS');
	return $enableProductReviews ?: '';
}
function enableWishlist()
{
	$enableWishlist = getenv('ENABLE_WISHLIST');
	return $enableWishlist ?: '';
}
function enableGiftwrap()
{
	$enableGiftwrap = getenv('ENABLE_GIFTWRAP');
	return $enableGiftwrap ?: '';
}
function enableHBCPoints()
{
	$enableHBCPoints = getenv('ENABLE_HBC_POINTS');
	return $enableHBCPoints ?: '';
}
function enableQuicklook()
{
	$enableQuicklook = getenv('ENABLE_QUICKLOOK');
	return $enableQuicklook ?: '';
}
function miraklConnectURL()
{
	$miraklConnectURL = getenv('MIRAKL_CONNECT_URL');
	return $miraklConnectURL ?: '';
}
function URLSplit($url){
	$split_url = explode("://",$url);
	$store_url = str_replace('/','',$split_url[1]);
	return $store_url;
}
function miraklConnectStatus()
{
	$status_code    = [];
    $status_code['200'] = 'OK - Request succeeded.';
    $status_code['201'] = 'Created - Request succeeded and resource created.';
    $status_code['202'] = 'Accepted - Request accepted for processing.';
    $status_code['204'] = 'No Content - Request succeeded but does not return any content.';
    $status_code['400'] = 'Bad Request - Parameter errors or bad method usage.';
    $status_code['401'] = 'Unauthorized - API call without authentication.';
    $status_code['403'] = 'Forbidden - Access to the resource is denied.';
    $status_code['404'] = 'Not Found - The resource does not exist.';
    $status_code['405'] = 'Method Not Allowed - The HTTP method (GET, POST, PUT, DELETE) is not allowed for this resource.';
    $status_code['406'] = 'Not Acceptable - The requested response content type is not available for this resource.';
    $status_code['410'] = 'Gone - The resource is permanently gone.';
    $status_code['415'] = 'Unsupported Media Type - The entity content type sent to the server is not supported.';
    $status_code['429'] = 'Too many requests - Rate limits are exceeded.';
    $status_code['500'] = 'Internal Server Error - The server encountered an unexpected error.';
	return $status_code;
}
function BigC_API_Call($api,$method,$header){
	$curl = curl_init();
	curl_setopt_array($curl, [
	CURLOPT_URL => $api,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
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

function Mirakl_PRODUCT_POST_API_Call($api,$method,$header,$post_fields){
	$curl = curl_init();
	curl_setopt_array($curl, [
	CURLOPT_URL => $api,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => $method,
	CURLOPT_POSTFIELDS => $post_fields,
	CURLOPT_HTTPHEADER => $header,
	]);
	$response = curl_exec($curl);
	$err = curl_error($curl);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	curl_close($curl);
	if ($err) {
		$result['status_code'] 	= $httpCode;
		$result['result']		= $err;
		return $result;
	} else {
		$result['status_code'] 	= $httpCode;
		$result['result']		= $response;
		return $result;
	}
}

function miraklAccessToken($mirakl_client_id,$client_secret,$seller_company_id){
	$api	= 'https://auth.mirakl.net/oauth/token';
	$header = ["Content-Type: application/x-www-form-urlencoded"];
	$method	= 'POST';
	$post_fields	= 'grant_type=client_credentials&client_id='.$mirakl_client_id.'&client_secret='.$client_secret.'&audience='.$seller_company_id;

	$curl = curl_init();
	curl_setopt_array($curl, [
	CURLOPT_URL => $api,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => $method,
	CURLOPT_POSTFIELDS => $post_fields,
	CURLOPT_HTTPHEADER => $header,
	]);
	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	if ($err) {
		return $err;
	} else {
		$response_result = json_decode($response);
		if(is_object($response_result)){
			if(isset($response_result->token_type) && isset($response_result->access_token)){
				$result = $response_result->token_type.' '.$response_result->access_token;
			}elseif(isset($response_result->error)){
				$result = $response_result->error.' '.$response_result->error_description;
			}
		}else{
			$result = $response_result['token_type'].' '.$response_result['access_token'];
		}

		return $result;
	}

}
?>