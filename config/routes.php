<?php
defined('BASEPATH') OR exit('No direct script access allowed');


$route['default_controller'] = 'login';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


$route['ws_update_inventery_operation/(:any)/(:any)/(:any)/(:any)/(:any)'] = "admin/web_services/WebApiModuleSale/inv_update_product_stock/$1/$2/$3/$4/$5/$6";

$route['ws-checkAppVersion'] = "admin/web_services/web_services/checkAppVersion";//1
$route['ws-login'] = "login";//2
$route['ws-login-v2'] = "login";
$route['ws-get-customer-list'] = "admin/web_services/web_services/get_customer_list";//3
$route['ws-get-products'] = "admin/web_services/web_services/get_product_list";//4
$route['ws-place-order-v2'] = "admin/web_services/web_services/place_order";
$route['ws-get-return-productslist'] = "admin/web_services/web_services/get_product_return_list";

$route['ws-get-previous-dues'] = "admin/web_services/web_services/get_previous_dues";//5
$route['ws-place-order'] = "admin/web_services/web_services/place_order"; //6
$route['ws-added-sale-list']= "admin/web_services/web_services/added_sale_list"; //7
$route['ws-get-order-details']= "admin/web_services/web_services/order_details_id"; //8
$route['ws-get-previous-invoice-list']= "admin/web_services/web_services/previous_invoice_list"; //9
$route['ws-get-product-details-by-id']= "admin/web_services/web_services/product_details_by_id"; //10
$route['ws-get-customer-details-by-id']= "admin/web_services/web_services/getcustomer_by_id"; //11

$route['ws-cust-details-by-id']= "admin/web_services/web_services/getclient_by_id"; //12

$route['ws-get-categories']= "admin/web_services/web_services/getcategories"; //13
$route['ws-get-picking-list']= "admin/web_services/web_services/get_picking_list"; //14
$route['ws-get-picking-details']= "admin/web_services/web_services/get_picking_details"; //15
$route['ws-confirm-picking']= "admin/web_services/web_services/confirm_picking"; //16
$route['ws-get-banner']= "admin/web_services/web_services/get_banner_list"; //17
$route['ws-update-ean-number']= "admin/web_services/web_services/update_ean_number";//18
$route['ws-search-products'] = "admin/web_services/web_services/search_product_by_patter"; //19

$route['ws-manifest-list'] ="admin/web_services/web_services/manifest_list"; //20
$route['ws-order-list-by-manifest-id'] ="admin/web_services/web_services/order_list_by_manifest_id"; //21
$route['ws-mark-not-delivered-reason'] ="admin/web_services/web_services/mark_not_delivered_reason"; //22
$route['ws-mark-parcel-delivery'] ="admin/web_services/web_services/mark_parcel_delivery"; //23 
$route['ws-get-due-invoice'] ="admin/web_services/web_services/get_due_invoice"; //24
$route['ws-update-payment'] ="admin/web_services/web_services/customer_update_payment"; //25

$route['ws-logout']= "login/logout"; //26

$route['ws-addtocart'] = "admin/web_services/web_services/addtocart"; //27
$route['ws-getcart_details'] = "admin/web_services/web_services/getcart_details"; //28
$route['ws-clear-cart'] = "admin/web_services/web_services/clear_cart"; //29
$route['ws-update-price'] = "admin/web_services/web_services/update_price"; //30
$route['ws-clear-cart-by-cart-id'] = "admin/web_services/web_services/clear_cart_by_cart_id"; //31

$route['ws-get_payment_collection'] = "admin/web_services/web_services/payment_collection"; //32
$route['ws-customer-ledger'] = "admin/web_services/web_services/customerledger"; //33
$route['ws-customer-ledger-app'] = "admin/web_services/web_services/customerledger_app"; //34

$route['ws-change-password'] = "admin/web_services/web_services/changepassword"; //35
$route['ws-promotions-rules'] = "admin/web_services/web_services/promotions_rules"; //36
$route['ws-update-profile-pic'] = "admin/web_services/web_services/changeprofile"; //37


$route['ws-get-orders-for-front-sheet']="admin/web_services/web_services/add_orders_front_sheet"; //38
$route['ws-create-edit-front-sheet']="admin/web_services/web_services/create_edit_front_sheet"; //39
$route['ws-front-sheet-list']="admin/web_services/web_services/front_sheet_list"; //40
$route['ws-front-sheet-order-list']="admin/web_services/web_services/front_sheet_order_list"; //41

$route['ws-lock-picking-list']="admin/web_services/web_services/lock_picking_list"; //42

$route['ws-get-past-orders-for-productId']="admin/web_services/web_services/past_orders_for_productId"; //43 
$route['ws-return-request']="admin/web_services/web_services/return_request";  //44
$route['ws-return-request-list']="admin/web_services/web_services/return_request_list"; //45

$route['ws-get-return-request-details'] = "admin/web_services/web_services/return_request_details"; //46
$route['ws-assign-vehicle-to-manifest'] = "admin/web_services/web_services/vehicle_to_manifest"; //47
$route['ws-get-vehicles'] = "admin/web_services/web_services/get_vehicles"; //48



$route['ws-update-physical-stock'] ="admin/web_services/web_services/update_physical_stock"; //49
$route['ws_get_stock_products_list'] ="admin/web_services/web_services/get_product_stock_take"; //50

// webApi
//Dashbord Count


$route['brochure'] = 'api/Contraformcont'; //49
$route['contraformcont/brochure_add'] = 'api/Contraformcont/brochure_add'; //50
$route['brochurelist'] = 'api/Contraformcont/brochure_list'; //51
$route['download_pdf/(:num)'] = 'api/Contraformcont/download_pdf/$1'; //52
$route['download_pdf1/(:num)'] = 'api/Contraformcont/download_pdf1/$1'; //53

$route['get_dashboard_count'] = 'admin/web_services/WebApiModuleDashboard/get_dashboard_count';//1

//Sales Module
$route['sales/saleslist_test'] = "admin/web_services/WebApiModuleSale/saleslist";//2
$route['sales/get_new_fs'] = "admin/web_services/WebApiModuleSale/get_new_fs";//3
$route['sales/get_approve_fs'] = "admin/web_services/WebApiModuleSale/get_approve_fs";//4
$route['order/history'] = "admin/web_services/WebApiModuleSale/sales_history";//5
$route['sales/add_orders_front_sheet'] = "admin/web_services/WebApiModuleSale/add_orders_front_sheet";
$route['frontsheet/add_order']="admin/web_services/WebApiModuleSale/add_orders_front_sheet"; //38

//Picking Module
$route['getPicker'] = "admin/web_services/WebApiModulePicking/getPicker";//6
$route['create/picking'] = 'admin/web_services/WebApiModulePicking/picking_print_list';//7
$route['picking/list'] = 'admin/web_services/WebApiModulePicking/picking_list';//8
$route['picking/get'] = 'admin/web_services/WebApiModulePicking/get_picking_history';//9
$route['short/picklist'] = 'admin/web_services/WebApiModulePicking/shortpick_list';//10
$route['picking/add_order_list'] = 'admin/web_services/WebApiModulePicking/add_order_list';
$route['edit_picking_order_list'] = 'admin/web_services/WebApiModulePicking/edit_picking_order_list';
$route['add_order_picking_list'] = 'admin/web_services/WebApiModulePicking/add_order_picking_list';
$route['update_picker'] = 'admin/web_services/WebApiModulePicking/update_picker';
$route['remove_order_picklist'] = 'admin/web_services/WebApiModulePicking/remove_order_picklist';
$route['remove_picklist'] = 'admin/web_services/WebApiModulePicking/remove_picklist';
$route['get_view_picking_history'] = 'admin/web_services/WebApiModulePicking/get_view_picking_history';
$route['get_view_picklist'] = "admin/web_services/WebApiModulePicking/get_view_picklist";
$route['get_short_list'] = 'admin/web_services/WebApiModulePicking/short_list';
$route['view_sales_details'] = 'admin/web_services/WebApiModulePicking/view_sales_details';
//Proforma Invoice
$route['proforma/invoice'] = 'admin/web_services/WebApiModuleProformaInvoice/picking_proforma';//11


//Manifest List
$route['manifest/list'] = 'admin/web_services/WebApiModuleDelivery/get_manifest_list';//12
$route['del/list'] = 'admin/web_services/WebApiModuleDelivery/deliveredlistshow'; //13
$route['udel/list'] = 'admin/web_services/WebApiModuleDelivery/undeliveredlistshow';//14

$route['manifest_list/get_mark_delivery'] = 'admin/web_services/WebApiModuleDelivery/get_mark_delivery';

$route['dlist/isConfirm'] = 'admin/web_services/WebApiModuleDelivery/get_isConfirm_data';
$route['ws-get-vehicles_web'] = "admin/web_services/WebApiModuleDelivery/get_vehicles_web";
//Account Module

$route['sage/sagelist'] = 'admin/web_services/WebApiModuleAccount/get_invoice';//15
$route['pushall/invoices'] = 'admin/web_services/WebApiModuleAccount/all_push_invoice';//16

$route['payment/receive'] = 'admin/web_services/WebApiModuleAccount/payment_received';
//Master Api

$route['getDriver'] = 'admin/web_services/WebApiModuleMaster/getDriver';//17
//$route['ws-get-vehicles'] = "admin/web_services/web_services/get_vehicles"; alerady use 


//Purchase Module
$route['purchase/grnlist'] = "admin/web_services/WebApiModulePurchase/grnlist";
$route['product/stockTakeList'] = "admin/web_services/WebApiModulePurchase/stockTakeList";
$route['purchase/grnlist'] = "admin/web_services/WebApiModulePurchase/grnlist";
$route['purchase/purchaseHistory'] = "admin/web_services/WebApiModulePurchase/purchaseHistory";

$route['ws-get-business-types'] = "admin/web_services/web_services/get_business_types";
$route['ws-get-business-categories'] = "admin/web_services/web_services/get_business_categories";
$route['ws-add-or-edit-customer-details'] = "admin/web_services/web_services/add_edit_customer_details";
$route['ws-get-all-customer-list'] = "admin/web_services/web_services/get_all_customer_list";
$route['ws-get-company-details'] = "admin/web_services/web_services/get_company_details";

$route['ws-get-company-setting'] = "admin/web_services/web_services/get_company_setting";
$route['ws-place-order-v2'] = "admin/web_services/web_services/place_order";
$route['ws-get-catalogue-details'] = "admin/web_services/web_services/catalogue_details";


//trip code start

$route['ws-get-manifests-to-add-in-trip'] = "admin/web_services/web_services/get_manifest_list_to_add_trip";
$route['ws-create-new-trip'] = "admin/web_services/web_services/create_new_trip";
$route['ws-my-trips'] = "admin/web_services/web_services/my_trip";

$route['ws-get-order-by-trip-id'] = "admin/web_services/web_services/get_order_by_trip_id";

$route['ws-get-trip-summary'] = "admin/web_services/web_services/trip_summary";

$route['get_dashboard_count_api'] = 'admin/web_services/web_services/get_dashboard_count_api';
$route['get_dashboard_count_api_v2'] = 'admin/web_services/web_services/get_dashboard_count_api_version_2';



///trip web api
$route['update_trip_vehicle'] = 'admin/web_services/WebApiModuleTrip/update_trip_vehicle';
$route['get-trip-summary'] = "admin/web_services/WebApiModuleTrip/trip_summary";
$route['reassign_order_by_manager'] = "admin/web_services/WebApiModuleTrip/reassign_order";
$route['complete_trip'] = "admin/web_services/WebApiModuleTrip/complete_trip";
$route['order_undelivered'] = "admin/web_services/WebApiModuleTrip/order_undelivered";
$route['get_trip_list'] = 'admin/web_services/WebApiModuleTrip/trip_list';
$route['get_complete_trip_list'] = 'admin/web_services/WebApiModuleTrip/complete_trip_list';
$route['get_trip_summary_report'] = 'admin/web_services/WebApiModuleReport/get_trip_summary_report';
$route['get_trip_summary_details'] = 'admin/web_services/WebApiModuleTrip/get_trip_summary_details';
$route['get-vehicles_web'] = 'admin/web_services/WebApiModuleTrip/get_vehicles';

//purchases

$route['ws-get-po-requests'] = 'admin/web_services/web_services/get_po_requests';
$route['ws-get-po-details-by-po-id'] = 'admin/web_services/web_services/po_details_by_po_id';
$route['ws-submit-grn'] = 'admin/web_services/web_services/add_grn';