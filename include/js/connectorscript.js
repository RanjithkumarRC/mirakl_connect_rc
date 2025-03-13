var head = document.getElementsByTagName('HEAD')[0];
var link = document.createElement('link');
link.rel = 'stylesheet';
link.type = 'text/css';
link.href = 'https://apps.royalcyber.org/mirakl_seller/include/css/connectorstyle.css';
head.appendChild(link);

// PDP page
$(document).ready(function(){
    if($(".productView").length > 0){
        $(".productView-details.product-options .productView-options").after('<div class="oth__seller_ctn"><h3 class="sub--headig">Offers List</h3><div id="sellers_list">No Offers Available</div></div>');
        console.log("pdp page");
        var parent_product_sku = BCData.product_attributes.sku;
        if(parent_product_sku){
            var formData = {product_sku:parent_product_sku};
            $(document).find('#sellers_list').html('');
            $.ajax({
                type: "post",
                url: 'https://apps.royalcyber.org/mirakl_seller/MiraklPoductOfferDetails',
                data: formData,
                enctype: 'multipart/form-data',
                success: function (data) {
                    const myJSON = data;
                    const myObj = JSON.parse(myJSON);
                    if(myObj.length == 0){
                        $(document).find('#sellers_list').html('No Offers Available');
                        return false;
                    }
                    console.log(myObj,'myobj');
                    var list_html ="";
                    var list = 1;
                    $.each(myObj,function(obj_key,obj_value){
                        var price = obj_value['price'];
                        var seller_qty = obj_value['quantity'];
                        price = parseFloat(price).toFixed(2);
                        var qty = obj_value['quantity'];
                        console.log(qty,'qty');
                        list_html =list_html + '<div class="oth_seller__row other_seller_container" id="product_offer_'+list+'"><div class="ship__price"><div class="sellership_price">'+price+'</div><div class="ship_chg"><span>+ Shipping charge</span></div></div><div class="info__pro_condition"><div class="pro_condition"><p>'+obj_value['condition']+'</p></div></div><div class="info__qty_seller"><form action="#" class="qnt_frm"><label>Qty</label><div class="form-increment" data-quantity-change><button data-sel="'+list+'" data-id="qty_'+list+'" class="button button--icon qty_dec"><span class="is-srOnly">dec</span><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-down"/></svg></i></button><input class="form-input form-input--incrementTotal qty_fill" id="qty_'+list+'" name="qty_'+list+'" type="tel" value="1" min="1" pattern="[0-9]*" aria-live="polite"><button data-sel="'+list+'" data-id="qty_'+list+'" class="button button--icon qty_inc"><span class="is-srOnly">inc</span><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-up"/></svg></i></button></div></form><div class="sellername"><p>Sold by:</p><p class="seller_name">'+obj_value['shop_name']+'</p></div> <div class="offerid"><input type="hidden" class="mirakl_offer_id" value="'+obj_value['offer_id']+'"><input type="hidden" class="mirakl_offer_price" value="'+price+'"><input type="hidden" class="mirakl_seller_name" value="'+obj_value['shop_name']+'"></div></div><div class="oth_seller_atc"><button class="oth__addcartbtn button button--primary cust_atc" data_seller_qty="'+seller_qty+'" data-ava-id="product_ava_'+list+'" data-id="product_offer_'+list+'" id="atc_'+list+'">Add to Cart</button></div></div><div id="product_ava_'+list+'"></div>';
                        list++;
                    });
                    $(document).find('#sellers_list').append(list_html);
                }
            });
        }

        $(document).on('click','#sellers_list .qty_dec',function(){
            var seller_count = $(this).attr('data-sel');
            var qty = $('#qty_'+seller_count).val();
            if(qty != 1){
                var dec_qty = parseInt(qty)-parseInt(1);
                $('#qty_'+seller_count).val(dec_qty);
            }
        });
        $(document).on('click','#sellers_list .qty_inc',function(){
            var seller_count = $(this).attr('data-sel');
            var qty = $('#qty_'+seller_count).val();
            var inc_qty = parseInt(qty)+parseInt(1);
            $('#qty_'+seller_count).val(inc_qty);
        });

        $(document).on('click','.cust_atc',function(){
            var stock_count =   $(this).attr('data_seller_qty');
            // console.log(stock_count,'stk cnt');
            var offer_id = $(this).parents(".other_seller_container").find(".mirakl_offer_id").val();
            var offer_price = $(this).parents(".other_seller_container").find(".mirakl_offer_price").val();
            var seller_name = $(this).parents(".other_seller_container").find(".mirakl_seller_name").val();
            var offer_qty = $(this).parents(".other_seller_container").find(".qty_fill").val();
            // console.log(offer_qty,'ofr qty');
            var mirakl_offerid_attr = $(".option-MiraklOfferID").attr("attr-id");
            var mirakl_sellername_attr = $(".option-SellerName").attr("attr-id");
        
            var variant_option_array = [];
            if($('.variant_options').length > 0){
                $('.variant_options').each(function(){
                    var option_id = $(this).attr("attr-id");
                    var option_value_id = $('select#attribute_select_'+option_id+' option:selected').val();
                    variant_option_array.push({'option_id': option_id, 'option_value_id': option_value_id});
                });
            }
            if((stock_count) && ((parseInt(offer_qty)) > (parseInt(stock_count)))){
                $('#'+$(this).attr('data-ava-id')).html('<p class="text_red"><span>Not available : </span>Currently we have only '+stock_count+' products available</p>');
                return false;
            }else{
                $('#'+$(this).attr('data-ava-id')).html('');
            }
            
            var post_data = new Object();
            post_data.product_id = $(".productView-options").find('[name="product_id"]').val();
            post_data.quantity = offer_qty;
            post_data.list_price = offer_price;
            post_data.offer_id_attr = mirakl_offerid_attr;
            post_data.offer_id_value = offer_id;
            post_data.seller_name_attr = mirakl_sellername_attr;
            post_data.seller_name_value = seller_name;
            post_data.variant_option_details = JSON.stringify(variant_option_array);
            // console.log(post_data);
            b2bAddToCart(post_data,stock_count);
        });
        function b2bAddToCart(post_data,stock_count) {
            // console.log('Log Cart');
            fetch('/api/storefront/cart?include=lineItems.physicalItems.options', {
                credentials: 'include'
            }).then(function(response) {
                return response.json();
            }).then(function(myJson) {
                // console.log(myJson);
                var cust_add_to_cart = 1;
                let carts = myJson;
                if (carts.length > 0) {
                    if(carts[0]?.lineItems?.physicalItems) {
                    var cart_physicalItems = carts[0].lineItems.physicalItems;
                    if (cart_physicalItems.length > 0) {
                        cart_physicalItems.forEach(function (cart_physicalItem) {
                            if(cart_physicalItem.productId == post_data.product_id){
                                var cart_physicalItem_options = cart_physicalItem.options;
                                if(cart_physicalItem_options.length > 0){
                                    cart_physicalItem_options.forEach(function(cart_physicalItem_option){
                                        if((cart_physicalItem_option.nameId == post_data.offer_id_attr) && (cart_physicalItem_option.valueId == post_data.offer_id_value)){
                                            var cust_total_qty = (parseInt(cart_physicalItem.quantity)) + (parseInt(post_data.quantity));
                                            if((stock_count) && ((parseInt(cust_total_qty)) > (parseInt(stock_count)))){
                                                cust_add_to_cart = 0;
                                                alert('Not available : Currently we have only '+stock_count+' products available, you have already '+cart_physicalItem.quantity+' products in cart.');
                                                return false;
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        }
                    }
                }
                if(cust_add_to_cart == 0){
                    return false;
                }
                var cartId='';
                if (carts.length > 0) {
                    cartId=carts[0].id;
                } 
                post_data.cart_id = cartId;
                console.log(post_data);
                $.ajax({
                    type: "post",
                    url: 'https://apps.royalcyber.org/mirakl_seller/AddProductToCart',
                    enctype: 'multipart/form-data',
                    data: post_data,
                    success: function (data) {
                        var return_response = JSON.parse(data);
                        var redirect_url = return_response['cart_url'];
                        console.log(redirect_url);
                        //redirect_url = redirect_url.replace("https://rc-bigc4.mybigcommerce.com/", "http://localhost:3000/");
                        $.ajax({
                            type: "GET",
                            url: redirect_url,
                            enctype: 'multipart/form-data',
                            success: function (data) {
                                // console.log('success');
                                alert("Product added to cart successfully");
                            }
                        });
                        return false;
                        window.location.href = redirect_url;
                    }
                });
            });
        }

        // Option code
        $(".form").find('[data-product-option-change]').find(".form-field").each(function(){
            if($(this).attr("data-product-attribute") == 'input-text'){
                var bc_attribute_id_string = $(this).find(".form-input").attr('id');
                var bc_attribute_id = bc_attribute_id_string.replace("attribute_text_",'');
                $(this).attr("attr-id",bc_attribute_id)
                var bc_option_label = $(this).find(".form-label").text();
                if(bc_option_label.includes('Mirakl Offer ID')){
                    $(this).addClass("option-MiraklOfferID");
                }
                if(bc_option_label.includes('Seller Name')){
                    $(this).addClass("option-SellerName");
                }
            }
            if($(this).attr("data-product-attribute") == 'set-select'){
                var bc_attribute_id_string = $(this).find(".form-select").attr('id');
                var bc_attribute_id = bc_attribute_id_string.replace("attribute_select_",'');
                $(this).attr("attr-id",bc_attribute_id)
                $(this).addClass("variant_options");
            }
        });

        $(".form").find('[data-product-option-change]').find(".form-field").find('.form-select').on('change',function(){
            $('#sellers_list').html('');
            setTimeout(function() {
                var product_sku = $("[data-product-sku]").text();
                if(product_sku != ''){ 
                    $('#sellers_list').html('');
                    var sku = product_sku;
                    var formData = {product_sku:sku};
                    $.ajax({
                        type: "post",
                        url: 'https://apps.royalcyber.org/mirakl_seller/MiraklPoductOfferDetails',
                        data: formData,
                        enctype: 'multipart/form-data',
                        success: function (data) {
                            const myJSON = data;
                            const myObj = JSON.parse(myJSON);
                            console.log(myObj,'myobj');
                            var list_html ="";
                            var list = 1;
                            $.each(myObj,function(obj_key,obj_value){
                                var price = obj_value['price'];
                                var seller_qty = obj_value['quantity'];
                                price = parseFloat(price).toFixed(2);
                                var qty = obj_value['quantity'];
                                console.log(qty,'qty');
                                list_html =list_html + '<div class="oth_seller__row other_seller_container" id="product_offer_'+list+'"><div class="ship__price"><div class="sellership_price">'+price+'</div><div class="ship_chg"><span>+ Shipping charge</span></div></div><div class="info__pro_condition"><div class="pro_condition"><p>'+obj_value['condition']+'</p></div></div><div class="info__qty_seller"><form action="#" class="qnt_frm"><label>Qty</label><div class="form-increment" data-quantity-change><button data-sel="'+list+'" data-id="qty_'+list+'" class="button button--icon qty_dec"><span class="is-srOnly">dec</span><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-down"/></svg></i></button><input class="form-input form-input--incrementTotal qty_fill" id="qty_'+list+'" name="qty_'+list+'" type="tel" value="1" data-quantity-min="1" min="1" pattern="[0-9]*" aria-live="polite"><button data-sel="'+list+'" data-id="qty_'+list+'" class="button button--icon qty_inc"><span class="is-srOnly">inc</span><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-up"/></svg></i></button></div></form><div class="sellername"><p>Sold by:</p><p class="seller_name">'+obj_value['shop_name']+'</p></div> <div class="offerid"><input type="hidden" class="mirakl_offer_id" value="'+obj_value['offer_id']+'"><input type="hidden" class="mirakl_offer_price" value="'+price+'"><input type="hidden" class="mirakl_seller_name" value="'+obj_value['shop_name']+'"></div></div><div class="oth_seller_atc"><button class="oth__addcartbtn button button--primary cust_atc" data_seller_qty="'+seller_qty+'" data-ava-id="product_ava_'+list+'" data-id="product_offer_'+list+'" id="atc_'+list+'">Add to Cart</button></div></div><div id="product_ava_'+list+'"></div>';
                                list++;
                            });
                            $('#sellers_list').html('');
                            $('#sellers_list').append(list_html);
                        }
                    });
                }else{
                    $('#sellers_list').html('No Seller Available');
                }
            }, 3000);
        });

    }

    // My Account page
    if($(".account-content").find(".account-list").find(".account-listItem").length > 0){
        $(".account-content").find(".account-list").find(".account-listItem").each(function(){
            var bc_order_id_string = $(this).find(".account-product-title a").text();
            var bc_order_id = bc_order_id_string.split("#");
            bc_order_id = bc_order_id[1];
            $(this).find(".account-product").after('<input type="hidden" class="bc_order_id" value="'+bc_order_id+'"><div class="Rtable Rtable--5cols Rtable--collapse" id="table_'+bc_order_id+'"></div>');
        });

        $(document).find(".bc_order_id").each(function( index ) {  
            var bc_order_id = $(this).val();
            $.post("https://apps.royalcyber.org/mirakl_seller/api/get_mirakl_order_details.php",
            {
                bc_order_id: bc_order_id,
            },
            function(data, status){
                const myJSON = data;
                const myObj = JSON.parse(myJSON);
                // console.log(myObj,'myobj');
                var mirakl_order_status_array = [];
                mirakl_order_status_array['STAGING'] = 'Fraud Check Pending';
                mirakl_order_status_array['WAITING_ACCEPTANCE'] = 'Pending Acceptance';
                mirakl_order_status_array['REFUSED'] = 'Rejected';
                mirakl_order_status_array['WAITING_DEBIT'] = 'Pending Debit';
                mirakl_order_status_array['WAITING_DEBIT_PAYMENT'] = 'Debit in Progress';
                mirakl_order_status_array['PAYMENT_COLLECTED'] = 'Customer Payment Collected';
                mirakl_order_status_array['SHIPPING'] = 'Shipping in Progress';
                mirakl_order_status_array['TO_COLLECT'] = 'To Collect';
                mirakl_order_status_array['SHIPPED'] = 'Shipped';
                mirakl_order_status_array['RECEIVED'] = 'Received';
                mirakl_order_status_array['INCIDENT_OPEN'] = 'Incident Open';
                mirakl_order_status_array['INCIDENT_CLOSED'] = 'Incident Closed';
                mirakl_order_status_array['CLOSED'] = 'Closed';
                mirakl_order_status_array['CANCELED'] = 'Canceled';
                mirakl_order_status_array['REFUNDED'] = 'Refunded';
                var total_order_price = 0;
                var tbl_content = '<div class="Rtable-row Rtable-row--head"><div class="Rtable-cell date-cell column-heading"><div class="date_clm Rtable-row--heading"></div></div><div class="Rtable-cell topic-cell column-heading">Order</div><div class="Rtable-cell access-link-cell column-heading"><div class="ord--subTotal Rtable-row--heading"><span>Total</span></div></div> <div class="Rtable-cell replay-link-cell column-heading"><div class="date_clm Rtable-row--heading"><span>Ship To</span></div></div><div class="Rtable-cell shop-cell column-heading">Shop</div><div class="Rtable-cell status-cell column-heading">Status</div><div class="Rtable-cell status-cell column-heading">Action</div></div>';
                var grand_total_line_price = 0;
                $.each(myObj,function(obj_key,obj_value){
                    //console.log(obj_value);
                    var converteddate = obj_value['created_date'];
                    var convertedtime = Date.parse(converteddate);
                    var convertdate = new Date(convertedtime);
                    var mirakl_order_dt = convertdate.getDate()+'-'+(convertdate.getMonth()+1)+'-'+convertdate.getFullYear();

                    var mirakl_order_id = obj_value['id'];
                    var mirakl_shipfirstName = obj_value['customer']['shipping_address']['firstname'];
                    var mirakl_shiplastName = obj_value['customer']['shipping_address']['lastname'];
                    var mirakl_shop = obj_value['shop_name'];
                    var mirakl_ordStaus = mirakl_order_status_array[obj_value['status']['state']];

                    var mirak_companyName = obj_value['customer']['shipping_address']['company'];
                    var mirak_orderCity = obj_value['customer']['shipping_address']['city'];
                    var mirak_orderCountry = obj_value['customer']['shipping_address']['country'];
                    var mirak_orderSate = obj_value['customer']['shipping_address']['state'];
                    var mirak_orderZip = obj_value['customer']['shipping_address']['zip_code'];
                    var mirak_orderAddress_1 = obj_value['customer']['shipping_address']['street_1'];
                    var mirak_orderAddress_2 = obj_value['customer']['shipping_address']['street_2'];
                    var mirak_shippmthName = obj_value['shipping']['type']['label']+' - '+obj_value['shipping']['type']['code'];
                    
                    var shipment_section = '';
                    if(obj_value['shipping']['tracking_number'] != undefined){ 
                        var mirak_orderShipment_id = obj_value['shipping']['tracking_number'];
                        var mirak_orderShipment_name = obj_value['shipping']['carrier'];
                        var mirak_orderShipment_url = obj_value['shipping']['tracking_url'];
                        var shipment_section = '<div class="ship_mth_ctn"><h4>Shipment Details</h4><p>Carrier Name : '+mirak_orderShipment_name+'</p><p>Tracking Number : <a target="_blank" href="'+mirak_orderShipment_url+'">'+mirak_orderShipment_id+'</a></p></div>';
                    }
                    

                    var total_line_price = 0;
                    tbl_content = tbl_content+'<div class="miral_order_details_modal"><div id="mirakl-od-popup_'+mirakl_order_id+'" class="mirakl_modal-box"><header> <div class="ord_modal_cls_btn"> <a href="javascript:void(0);" class="mod-js-modal-close close">X</a> </div> <div class="ord_modal_heading"> <h3>Order Details</h3> </div> <div class="ord_modal_dt"> <div class="ord_modal_shipp_dt"><span>ordered on</span><p> '+mirakl_order_dt+'</p></div> </div> </header><div class="mirakl_modal-body"><section class="ord_clm ship_dtl"> <h4>Shipping Address</h4> <ul class="display_shipadd"> <li class="display_shipadd fullname">'+mirakl_shipfirstName+" "+mirakl_shiplastName +'</li> <li class="display_shipadd address_line1">'+mirak_companyName+'</li> <li class="display_shipadd address_line1">'+mirak_orderAddress_1+'</li> <li class="display_shipadd address_line1">'+mirak_orderAddress_2+'</li> <li class="display_shipadd address_city">'+mirak_orderCity+" "+mirak_orderSate+" "+mirak_orderZip+'</li> <li class="display_shipadd address_cont">'+mirak_orderCountry+'</li></ul><div class="ship_mth_ctn"><h4>Shipping Methods</h4><p>'+mirak_shippmthName+'</p></div>'+shipment_section+' </section><section class="line_orderdtl_clm">';
                    var total_line_order_tax = 0;
                    var total_line_order_product_shipping_price = 0;
                    $.each(obj_value['order_lines'],function(obj_line_key,obj_line_value){
                        console.log(obj_line_value,'line_item');
                        var line_order_qty = obj_line_value['quantity'];
                        var line_ordersku = obj_line_value['offer']['product']['sku'];
                        var line_order_producttitle = obj_line_value['offer']['product']['title'];
                        var line_order_product_price = parseFloat(obj_line_value['offer']['price']).toFixed(2);
                        var line_order_product_price_qty = (parseInt(line_order_qty)*parseFloat(line_order_product_price)).toFixed(2);
                        var line_order_product_shipping_price = parseFloat(obj_line_value['shipping_price']).toFixed(2);
                        total_line_order_product_shipping_price = (parseFloat(total_line_order_product_shipping_price) + parseFloat(line_order_product_shipping_price)).toFixed(2);
                        var line_order_price = obj_line_value['total_price'];
                        var line_order_tax = obj_line_value['taxes'][0]?obj_line_value['taxes'][0]['amount']:'';
                        //var line_order_tax = 0;
                        total_line_order_tax = (parseFloat(total_line_order_tax) + parseFloat(line_order_tax)).toFixed(2);
                        var line_order_totalprice = (parseFloat(line_order_price) + parseFloat(line_order_tax)).toFixed(2);
                        total_line_price = (parseFloat(total_line_price) + parseFloat(line_order_totalprice)).toFixed(2);

                        tbl_content = tbl_content+'<div class="line_orders_row"><ul class="ords_itms"> <li><b>'+line_order_qty+ '</b> X ' +line_order_producttitle+'</li> <li>'+line_ordersku+'</li> </ul> <div class="prod_price"> <p>'+line_order_product_price_qty+'</p></div></div>';
                    });
                    grand_total_line_price = (parseFloat(grand_total_line_price) + parseFloat(total_line_price)).toFixed(2);
                    total_order_price = (parseFloat(total_order_price) + parseFloat(total_line_price)).toFixed(2);

                    tbl_content = tbl_content+'<div class="total_price_ctn"> <ul class="coun_totalshptax">  <li> <span>Tax:</span><p> '+parseFloat(total_line_order_tax).toFixed(2)+'</p></li><li><span>Shipping:</span><p>'+total_line_order_product_shipping_price+'</p> </li> <li> <span>Total price:</span> <p>'+grand_total_line_price+'</p> </li></ul></section></div></div></div></div></div></div>';
                    
                    tbl_content = tbl_content+'<div class="Rtable-row"> <div class="Rtable-cell date-cell"> <div class="Rtable-cell--content"><div class="Rtable-cell--content title_hd_btn">Marketplace</div></div> </div> <div class="Rtable-cell topic-cell"> <div class="Rtable-cell--content replay-link-content">'+mirakl_order_id+'</div> </div> <div class="Rtable-cell access-link-cell"> <div class="Rtable-cell--content title-content"><b>$'+total_line_price+'</b></div> </div> <div class="Rtable-cell replay-link-cell"> <div class="Rtable-cell--content title-content">'+mirakl_shipfirstName+" "+mirakl_shiplastName +'</div> </div> <div class="Rtable-cell shop-cell"> <div class="Rtable-cell--content title-content">'+mirakl_shop+'</div> </div> <div class="Rtable-cell status-cell"> <div class="Rtable-cell--content title-content">'+mirakl_ordStaus+'</div> </div> <div class="Rtable-cell status-cell"> <div class="cust_orderDetail_popup Rtable-cell--content title-content" data-modal-id="mirakl-od-popup_'+mirakl_order_id+'"><button>View Details</button</div> </div> </div>';
                    tbl_content = tbl_content+'</div>';
                });
                tbl_content = tbl_content+'<div class="Rtable-row green-bg"> <div class="Rtable-cell date-cell"> <div class="Rtable-cell--content"></div> </div> <div class="Rtable-cell topic-cell"> <div class="Rtable-cell--content replay-link-content"><b>Total Price</b></div> </div> <div class="Rtable-cell access-link-cell"> <div class="Rtable-cell--content title-content"><b>$'+total_order_price+'</b></div> </div> <div class="Rtable-cell replay-link-cell"> <div class="Rtable-cell--content title-content"></div> </div> <div class="Rtable-cell shop-cell"> <div class="Rtable-cell--content title-content"></div> </div> <div class="Rtable-cell status-cell"> <div class="Rtable-cell--content title-content"></div> </div><div class="Rtable-cell status-cell"> <div class="Rtable-cell--content title-content"></div></div>';
                tbl_content = tbl_content+'</div>';
                $("#table_"+bc_order_id).append(tbl_content);
            });
        });

        $(document).on('click','.cust_orderDetail_popup', function(e) {
            var appendthis =  ("<div class='mod_modal-overlay mod-js-modal-close'></div>");
            e.preventDefault();
            $("body").append(appendthis);
            $("body").addClass("modal-open");
            $(".mod_modal-overlay").fadeTo(500, 0.9);
            $(".js-modalbox").fadeIn(500);
            var modalBox = $(this).attr('data-modal-id');
            $('#'+modalBox).fadeIn($(this).data());
            $(".mod-js-modal-close, .mod_modal-overlay").click(function() {
                $(".mirakl_modal-box, .mod_modal-overlay").fadeOut(500, function() {
                    $(".mod_modal-overlay").remove();
                    $("body").removeClass("modal-open");
                });
            });
            $(window).resize(function() {
                $(".mirakl_modal-box").css({
                top: ($(window).height() - $(".mirakl_modal-box").outerHeight()) / 2,
                left: ($(window).width() - $(".mirakl_modal-box").outerWidth()) / 2
                });
            });
            $(window).resize();
        });
    }

    // Cart page
    if($('[data-cart]').length > 0){
        console.log('cart_page');

        $(document).find(".cart-list .cart-item").each(function( index ) {
            var load_cart_itemid     = $(this).find(".cart-item-quantity .form-increment button").attr('data-cart-itemid');
            var load_cart_ext_qty    = $('#qty-'+load_cart_itemid).val();
            var cart_mirakl_offer_id = '';
            $(this).find(".definitionList .definitionList-key").each(function(){
                var mirakl_offer_text = $.trim($(this).text());
                if(mirakl_offer_text == 'Mirakl Offer ID:'){
                    cart_mirakl_offer_id = $.trim($(this).next().text());
                }
            });

            var cartqQtyInput = '<input id="cust_qty-'+load_cart_itemid+'" class="cart_inc_dec_txt form-input form-input--incrementTotal" type="text" value="'+load_cart_ext_qty+'" mirakl_offer_id="'+cart_mirakl_offer_id+'" cart-itemid="'+load_cart_itemid+'">';
            
            $(this).find(".cart-item-quantity").append('<div class="cust-form-increment"></div>');

            $(this).find(".cart-item-quantity .form-increment").css("display", "none");
            
            $(this).find(".cart-item-quantity .cust-form-increment").append('<button data-btn-action="desc" mirakl_offer_id="'+cart_mirakl_offer_id+'" cart-itemid="'+load_cart_itemid+'" class="cart_inc_dec button button--icon"><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-down"></use></svg></i></button>');
            $(this).find(".cart-item-quantity .cust-form-increment").append(cartqQtyInput);
            $(this).find(".cart-item-quantity .cust-form-increment").append('<button data-btn-action="inc" mirakl_offer_id="'+cart_mirakl_offer_id+'" cart-itemid="'+load_cart_itemid+'" class="cart_inc_dec button button--icon"><i class="icon" aria-hidden="true"><svg><use xlink:href="#icon-keyboard-arrow-up"></use></svg></i></button>');
        });

        $(document).on('change','.cart_inc_dec_txt',function(){
            var mirakl_offer_id = $(this).attr('mirakl_offer_id');
            var cart_itemid     = $(this).attr('cart-itemid');
            var cart_qty        = $('#cust_qty-'+cart_itemid).val();

            var formData                = {offer_id:mirakl_offer_id};
            $.ajax({
                type: "post",
                url: 'https://apps.royalcyber.org/mirakl_seller/api/get_inventory_count_cart',
                enctype: 'multipart/form-data',
                data: formData,
                success: function (response) {
                    const cartQtyJSON   = JSON.parse(response);
                    const cartQty       = cartQtyJSON['avalible_qty'];
                    if(cartQty > cart_qty){
                        fetch('/api/storefront/cart', {   credentials: 'include' })
                        .then(function(response) {   return response.json(); })
                        .then(function(cartJson) {
                            cart_id = cartJson[0]['id'];

                            var lineiteams      = {};
                            $.each(cartJson[0]['lineItems']['physicalItems'], function(key, value) {
                                lineiteams[value.id]    = value.productId;
                            });
                            var cartformData    = {cartId:cart_id,itemId:cart_itemid,quantity:cart_qty,productId:lineiteams[cart_itemid]};
                            $.ajax({
                                type: "post",
                                url: 'https://apps.royalcyber.org/mirakl_seller/api/update_inventory_count_cart',
                                enctype: 'multipart/form-data',
                                data: cartformData,
                                success: function (result) {
                                    if(result == 'success'){
                                        location.reload(true);
                                    }else{
                                        alert('Reload the page and try again...')
                                    }
                                }
                            });
                        });
                    }else{
                        alert('The maximum purchasable quantity is '+cartQty);
                    }
                }
            });
        });

        $(document).on('click','.cart_inc_dec',function(){
            var mirakl_offer_id = $(this).attr('mirakl_offer_id');
            var cart_itemid     = $(this).attr('cart-itemid');
            var btn_action      = $(this).attr('data-btn-action');
            var cart_ext_qty    = $('#qty-'+cart_itemid).val();

            var formData                = {offer_id:mirakl_offer_id};
            $.ajax({
                type: "post",
                url: 'https://apps.royalcyber.org/mirakl_seller/api/get_inventory_count_cart',
                enctype: 'multipart/form-data',
                data: formData,
                success: function (response) {
                    const cartQtyJSON   = JSON.parse(response);
                    const cartQty       = cartQtyJSON['avalible_qty'];
                    var cart_qty        = 0;
                    if(btn_action == 'desc'){
                        cart_qty    = parseInt(cart_ext_qty)-parseInt(1);
                    }else if(btn_action == 'inc'){
                        cart_qty    = parseInt(cart_ext_qty)+parseInt(1);
                    }

                    if(cartQty > cart_qty){
                        var cart_id = '';
                        //$('#qty-'+cart_itemid).val(cart_qty);
                        fetch('/api/storefront/cart', {   credentials: 'include' })
                        .then(function(response) {   return response.json(); })
                        .then(function(cartJson) {
                            cart_id = cartJson[0]['id'];

                            var lineiteams      = {};
                            $.each(cartJson[0]['lineItems']['physicalItems'], function(key, value) {
                                lineiteams[value.id]    = value.productId;
                            });
                            
                            var cartformData    = {cartId:cart_id,itemId:cart_itemid,quantity:cart_qty,productId:lineiteams[cart_itemid]};
                            $.ajax({
                                type: "post",
                                url: 'https://apps.royalcyber.org/mirakl_seller/api/update_inventory_count_cart',
                                enctype: 'multipart/form-data',
                                data: cartformData,
                                success: function (result) {
                                    if(result == 'success'){
                                        location.reload(true);
                                    }else{
                                        alert('Reload the page and try again...')
                                    }
                                }
                            });
                        });
                    }else{
                        alert('The maximum purchasable quantity is '+cartQty);
                    }
                }
            });
        });

        // this.$cartContent = $('[data-cart-content]');
        // $('[data-cart-update]', this.$cartContent).on('click', event => {
        //     console.log('preventDefault');
        //     //const $target = $(event.currentTarget);
        //     event.preventDefault();
        // });
        // $(document).on('click','[data-cart-update]',function(ee){
        //     console.log('preventDefault');
        //     ee.preventDefault();
        // });

        // $("a.nav_returnlink").live("click",function(){
        //     return true;
        //  });
        // $(document).on('click','[data-action="dec"]',function(){
        //     // this.$cartContent = $('[data-cart-content]');
        //     console.log('dec click');
        //     $(document).on('click','[data-cart-update]',function(ee){
        //         console.log('preventDefault');
        //         ee.stopPropagation();
        //     });
        //     // $('[data-cart-update]', this.$cartContent).on('click', event => {
        //     //     event.preventDefault();
        //     // });
        //     //$('[data-cart-update]').off();
        //     //$(document).unbind('click','[data-cart-update]');
        //     //event.stopPropagation();
        //     //return false;
        // });
        // $(document).on('click','[data-action="inc"]',function(){
        //     // this.$cartContent = $('[data-cart-content]');
        //     console.log('inc click');
        //     $(document).on('click','[data-cart-update]',function(ee){
        //         console.log('preventDefault');
        //         ee.stopPropagation();
        //     });
        //     // $('[data-cart-update]', this.$cartContent).on('click', event => {
        //     //     event.preventDefault();
        //     // });
        //     //$('[data-cart-update]').off();
        //     //$(document).unbind('click','[data-cart-update]');
        //     //event.stopPropagation();
        //     //return false;
        // });
    }
});
