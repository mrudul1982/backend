<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, authorization');


class WebApiModuleTrip extends CI_Controller {
	
	public function __construct() 
	{
		parent::__construct();
		
		$this->digital_upload_path = 'files/';
		$this->upload_path = 'assets/uploads/';
		$this->thumbs_path = 'assets/uploads/thumbs/';
		$this->image_types = 'gif|jpg|jpeg|png|tif';
		$this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
		$this->allowed_file_size = '1024';
		$this->load->library('upload');
		$this->load->model('sales_model');
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); // OK status for preflight request
    exit();


}
}


public function trip_list()
{
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);

	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];

	$condition = "sma_trip.status = 'Intransit' ";
	
	$totalRecords = $this->db->query("select COUNT(*) AS allcount FROM sma_trip WHERE $condition ORDER BY sma_trip.id DESC")->row()->allcount;

	$data = $this->db->query("select sma_trip.id,sma_trip.trip_number,sma_trip.status,sma_trip.createdOn,sma_users.username as driver,sma_vehicle.model_name as vehicle FROM `sma_trip` LEFT JOIN sma_users on sma_users.id=sma_trip.driver_id LEFT JOIN sma_vehicle on sma_vehicle.id=sma_trip.vehicle_id 
		WHERE $condition AND (trip_number LIKE '%$searchValue%' OR sma_users.username LIKE '%$searchValue%' 
		OR sma_vehicle.model_name LIKE '%$searchValue%')
		ORDER BY sma_trip.id DESC LIMIT $start, $length")->result();

	$data1 = [];
	foreach ($data as $r) {
		$date = new DateTime($r->createdOn);
		$formattedDate = $date->format('d-m-Y');
		$data1[] = [
			"id" => $r->id,
			"date" =>$formattedDate,
			"trip_number" =>$r->trip_number,
			"driver" => $r->driver,
			"vehicle" => $r->vehicle,
			"status" => $r->status
		];
	}

	$result = [
		"draw" => $draw,
		"recordsTotal" =>  count($data),
		"recordsFiltered" =>$totalRecords,
		"data" => $data1,
	];

	echo json_encode($result);
	exit();
}

public function complete_trip_list(){
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);

	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];

	$condition = "sma_trip.status = 'Completed' ";
	
	$totalRecords = $this->db->query("select COUNT(*) AS allcount FROM sma_trip WHERE $condition ORDER BY sma_trip.id DESC")->row()->allcount;

	$data = $this->db->query("select sma_trip.id,sma_trip.trip_number,sma_trip.status,sma_trip.createdOn,sma_users.username as driver,sma_vehicle.model_name as vehicle FROM `sma_trip` LEFT JOIN sma_users on sma_users.id=sma_trip.driver_id LEFT JOIN sma_vehicle on sma_vehicle.id=sma_trip.vehicle_id 
		WHERE $condition AND (trip_number LIKE '%$searchValue%' OR sma_users.username LIKE '%$searchValue%' 
		OR sma_vehicle.model_name LIKE '%$searchValue%')
		ORDER BY sma_trip.id DESC LIMIT $start, $length")->result();

	$data1 = [];
	foreach ($data as $r) {
		$date = new DateTime($r->createdOn);
		$formattedDate = $date->format('d-m-Y');
		$data1[] = [
			"id" => $r->id,
			"date" =>$formattedDate,
			"trip_number" =>$r->trip_number,
			"driver" => $r->driver,
			"vehicle" => $r->vehicle,
			"status" => $r->status
		];
	}

	$result = [
		"draw" => $draw,
		"recordsTotal" =>  count($data),
		"recordsFiltered" =>$totalRecords,
		"data" => $data1,
	];

	echo json_encode($result);
	exit();
}

public function trip_summary()
{
	extract($_POST);

	// $secure_key = $this->input->request_headers();
	// $token = $secure_key['authorization'];  
	// $str = ltrim($token, 'Bearer ');
	// $token = 'e'.$str;   
	// $this->load->helper('jwt_helper');
	// $user = jwt::decode($token, $this->config->item('jwt_key'));      
	// $prefix = $user->prefix;
	// $this->load->database($prefix);   
	// $driver_id = $user->id;

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);

	$trip_id = $this->input->post('tripId');
	$trip_delivered_order = $this->db->query("select SUM(sma_sales.grand_total) AS grand_total 
		FROM sma_sales
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sales.sale_status = 'Delivered' and sma_manifest_trip.trip_id = '$trip_id'
		")->result_array();

	$trip_undelivered_order = $this->db->query("select SUM(sma_sales.grand_total) AS grand_total 
		FROM sma_sales
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sales.sale_status = 'Undelivered' and sma_manifest_trip.trip_id = '$trip_id'
		")->result_array();
	$partial_undeliv = $this->db->query("select SUM(sma_sale_items.not_delivered_qty * sma_sale_items.unit_price) AS grand_total 
		FROM sma_sales
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sale_items.not_delivered_qty  IS NOT NULL  and sma_manifest_trip.trip_id = '$trip_id'		
		AND sma_sale_items.is_delete = '0'
		")->result_array();


	$trip_delivered_order_product = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.order_type AS qtyType,  CAST(sma_sale_items.quantity AS UNSIGNED) AS qty FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sales.sale_status = 'Delivered' AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();


	$trip_undelivered_order_product = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.unit_price,sma_sale_items.item_tax,sma_sale_items.subtotal, CAST(sma_sale_items.quantity AS UNSIGNED) AS qty,sma_sale_items.order_type AS qtyType  FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sales.sale_status = 'Undelivered' 
		and sma_sale_items.quantity!='0'  AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();

	$delivered_products_box = 0;
	$delivered_products_piece = 0;

	foreach ($trip_delivered_order_product as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$delivered_products_box += $product['qty'];
		} elseif ($product['qtyType'] == 'piece') 
		{
			$delivered_products_piece += $product['qty'];
		}
	}


	$undelivered_products_box = 0;
	$undelivered_products_piece = 0;

	foreach ($trip_undelivered_order_product as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$undelivered_products_box += $product['qty'];
		} elseif ($product['qtyType'] == 'piece') 
		{
			$undelivered_products_piece += $product['qty'];
		}
	}

	$trip_delivered_order_details = $this->db->query("select 
		sma_sales.id AS orderId,
		sma_companies.id AS customerId,
		sma_companies.company AS customerName,
		sma_companies.company,
		sma_sales.reference_no AS referenceNo,
		sma_sales.invoice_date AS invoice_date,
		sma_companies.accound_no AS customerAccountNo
		FROM 
		sma_sales
		LEFT JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) > 0
		LEFT JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id
		WHERE 
		sma_sales.sale_status = 'Delivered' 
		AND FIND_IN_SET(sma_manifest_trip.trip_id, '$trip_id') > 0")->result_array();

	foreach ($trip_delivered_order_details as &$detail) {
		
		$originalDate = $detail['invoice_date'];
		$newDate = date("d-m-Y", strtotime($originalDate));
		$detail['invoice_date'] = $newDate;
	}

	$trip_undelivered_order_details = $this->db->query("select 
		sma_sales.id AS orderId,
		sma_companies.id AS customerId,
		sma_companies.company AS customerName,
		sma_companies.company,
		sma_sales.reference_no AS referenceNo,
		sma_sales.invoice_date AS invoice_date,
		sma_companies.accound_no AS customerAccountNo 
		FROM 
		sma_sales 
		LEFT JOIN 
		sma_manifest 
		ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) > 0 
		LEFT JOIN 
		sma_manifest_trip 
		ON sma_manifest.id = sma_manifest_trip.manifest_id 
		LEFT JOIN 
		sma_companies 
		ON sma_companies.id = sma_sales.customer_id 
		WHERE 
		sma_sales.sale_status = 'Undelivered' 
		AND sma_manifest_trip.trip_id = '$trip_id'
		")->result_array();

	foreach ($trip_undelivered_order_details as &$detail) {
		
		$originalDate = $detail['invoice_date'];
		$newDate = date("d-m-Y", strtotime($originalDate));
		$detail['invoice_date'] = $newDate;
	}

	$total_product_list = $this->db->query("select sma_sale_items.product_id AS productId,sma_sale_items.sale_id, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.order_type AS qtyType,  CAST(sma_sale_items.quantity AS UNSIGNED) AS qty,CAST(order_qty AS UNSIGNED) AS order_qty,CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS not_delivered_qty FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();




	$total_products_box = 0;
	$total_products_piece = 0;

	foreach ($total_product_list as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$total_products_box += $product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);
		} 
		elseif ($product['qtyType'] == 'piece') 
		{
			$total_products_piece += $product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);
		}
	}



	$total_balance_product = $this->db->query("
		SELECT 
		sma_sale_items.product_id AS productId,
		sma_sale_items.sale_id, 
		sma_sale_items.product_name AS productName, 
		sma_sale_items.product_code AS productCode, 
		sma_sale_items.order_type AS qtyType,  
		SUM(CAST(sma_sale_items.quantity AS UNSIGNED)) AS qty,
		CAST(order_qty AS UNSIGNED) AS order_qty,
		CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS not_delivered_qty
		FROM 
		sma_sales 
		INNER JOIN 
		sma_sale_items ON sma_sales.id = sma_sale_items.sale_id 
		INNER JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id 
		WHERE 
		sma_manifest_trip.trip_id = '$trip_id' 
		AND sma_sale_items.is_delete = '0' 
		GROUP BY 
		sma_sale_items.product_id, 
		sma_sale_items.order_type
		")->result_array();

// Loop through each product
	foreach ($total_balance_product as &$product) {
    // Initialize total quantities
		$TotalQty = $product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);

    // Prepare variables for queries
		$productId = $product['productId'];
		$sale_id = $product['sale_id'];

    // Execute the inner query to get the delivered quantity
		$Delivered = $this->db->query("
			SELECT sma_sale_items.quantity as qty 
			FROM sma_sales 
			LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
			WHERE sma_sales.sale_status = 'Delivered' 
			AND sma_sale_items.sale_id = '$sale_id' 
			AND sma_sale_items.product_id = '$productId'
			")->row_array();

    // Calculate the delivered quantity
		$delivered_qty = isset($Delivered['qty']) ? $Delivered['qty'] : 0;
		$product['delivered_qty'] = $delivered_qty;

    // Update quantities and balance based on quantity type
		$product['qty'] = $TotalQty;
		$product['balance_qty'] = $TotalQty - $delivered_qty;
	}
	unset($product);





	//Return start

	$return_product_list = $this->db->query("select sma_return_items.product_id as productId,sma_return_items.product_name as productName,product_code as productCode,return_type as qtyType,sma_return_items.return_quantity as qty FROM `sma_returns` left join sma_return_items on sma_return_items.return_id=sma_returns.id where trip_id='$trip_id'")->result_array();

	$return_products_box = 0;
	$return_products_piece = 0;

	foreach ($return_product_list as $row) 
	{

		if ($row['qtyType'] == 'box') 
		{
			$return_products_box += $row['qty'];

		} elseif ($row['qtyType'] == 'piece') 
		{
			$return_products_piece += $row['qty'];
		}
	}

	$return_details = $this->db->query("select sma_companies.accound_no, sma_companies.company as company, sma_returns.is_accept, sma_returns.id, sma_returns.date, sma_returns.reference_no, sma_returns.grand_total, sma_returns.total_items, sma_users.first_name, sma_users.last_name 
		FROM sma_returns
		LEFT JOIN sma_companies ON sma_companies.id = sma_returns.customer_id 
		LEFT JOIN sma_users ON sma_returns.created_by = sma_users.id where trip_id='$trip_id'")->result_array();

	foreach ($return_details as &$detail) {
		
		$originalDate = $detail['date'];
		$newDate = date("d-m-Y", strtotime($originalDate));
		$detail['date'] = $newDate;
	}


	//Return End



//payment collection start

	$driver_payment_collection['cash_collection_details'] = $this->db->query("select sma_companies.company as customerName,sma_companies.accound_no AS customerAccountNo,amount as cashCollected,payment_mode from sma_driver_collected_amount left join sma_companies on sma_companies.id=sma_driver_collected_amount.customer_id where trip_id='$trip_id'")->result_array();

	$payment_collection = [
		'cash_collection_details' => [],
		'cheque_collection_details' => []
	];


	foreach ($driver_payment_collection['cash_collection_details'] as $payment) {

		if ($payment['payment_mode'] == 'cash') {

			$payment_collection['cash_collection_details'][] = [
				'customerName' => $payment['customerName'], 
				'customerAccountNo' => $payment['customerAccountNo'],
				'cashCollected' => $payment['cashCollected']
			];
		} elseif ($payment['payment_mode'] == 'cheque') {

			$payment_collection['cheque_collection_details'][] = [
				'customerName' => $payment['customerName'],
				'customerAccountNo' => $payment['customerAccountNo'],
				'chequeAmount' => $payment['cashCollected'] 
			];
		}
	}

	$data['deliverySummary'] = array(
		'delivered_orders' => count($trip_delivered_order_details),
		'delivered_amt' => $trip_delivered_order[0]['grand_total'] ? $trip_delivered_order[0]['grand_total'] : 0,		
		'delivered_order_details' => $trip_delivered_order_details,
		'undelivered_orders' => count($trip_undelivered_order_details),
		'undelivered_amt' =>  $trip_undelivered_order[0]['grand_total'] + $partial_undeliv[0]['grand_total'] ? $trip_undelivered_order[0]['grand_total'] + $partial_undeliv[0]['grand_total'] : 0,
		'undelivered_order_details'=>$trip_undelivered_order_details	

		
	);


	$trip_order_details = $this->db->query("select 
		sma_sales.id AS orderId,
		sma_companies.id AS customerId,
		sma_companies.company AS customerName,
		sma_companies.company,
		sma_sales.reference_no AS referenceNo,
		sma_sales.sale_status,
		sma_companies.accound_no AS customerAccountNo,
		sma_companies.postal_code AS postCode,
		sma_manifest.manifest_id AS manifestId,
		sma_manifest.id AS manifest_Id,
		sma_routes.route_number as route
		FROM 
		sma_sales
		LEFT JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) > 0
		LEFT JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id

		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id
		LEFT JOIN 
		sma_routes ON sma_routes.id = sma_companies.route
		WHERE 
		FIND_IN_SET(sma_manifest_trip.trip_id, '$trip_id') > 0")->result_array();
	$trip_manifest = $this->db->query("select 
		sma_manifest.manifest_id AS manifestId		
		FROM 
		sma_sales
		LEFT JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) > 0
		LEFT JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id
		LEFT JOIN 
		sma_routes ON sma_routes.id = sma_companies.route
		WHERE 
		FIND_IN_SET(sma_manifest_trip.trip_id, '$trip_id') > 0 GROUP BY manifestId")->result_array();



	$partial_undeliv = $this->db->query("select sma_sale_items.id as item_id,sma_sale_items.product_id, sma_sale_items.product_code, sma_sale_items.product_name, sma_sale_items.unit_price, sma_sale_items.quantity, sma_sale_items.order_type, sma_sale_items.subtotal, sma_sale_items.not_delivered_qty, sma_sale_items.return_reason, sma_sale_items.tax,sma_sale_items.IsStock_update,sma_sales.id AS orderId,sma_sales.reference_no AS referenceNo FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sale_items.not_delivered_qty  IS NOT NULL  AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result();


	$partial_undelivered  = array();
	$partial_not_delivered_box='0';
	$partial_not_delivered_piece='0';
	foreach ($partial_undeliv as $r) {
		$qty= $r->quantity + $r->not_delivered_qty;
		$tax_rate_cal=(($qty*$r->unit_price) * $tax) / 100;
		$subtotal = $qty*$r->unit_price;
		if ($r->order_type == 'box') 
		{
			$partial_not_delivered_box +=  $r->not_delivered_qty;

		} elseif ($r->order_type == 'piece') 
		{
			$partial_not_delivered_piece +=  $r->not_delivered_qty;
		}
		$partial_undelivered[] = array(
			'item_id'=> $r->item_id,
			'orderId'=> $r->orderId,
			'referenceNo'=> $r->referenceNo,
			"productId" => $r->product_id,
			"productCode" => $r->product_code,
			"productName" => $r->product_name,
			"unit_price" => number_format($r->unit_price, 2),
			"quantity" => $r->quantity + $r->not_delivered_qty,
			"order_type" => $r->order_type,
			"reason" => $r->return_reason,
			"subtotal" => number_format($subtotal+$tax_rate_cal, 2),
			"not_delivered_qty" => $r->not_delivered_qty,
			"IsStock_update" => $r->IsStock_update,
			"tax" => number_format($tax_rate_cal, 2),
		);
	}


		//trip_details
	$trip_details = $this->db->query("select sma_trip.id,sma_trip.trip_number,sma_trip.status,sma_trip.createdOn,sma_users.username as driver,sma_vehicle.model_name as vehicle FROM `sma_trip` LEFT JOIN sma_users on sma_users.id=sma_trip.driver_id LEFT JOIN sma_vehicle on sma_vehicle.id=sma_trip.vehicle_id 
		WHERE sma_trip.id='$trip_id'")->row_array();

	
	$date = new DateTime($trip_details['createdOn']);
	$formattedDate = $date->format('d-m-Y');

	$data['trip_number'] = $trip_details['trip_number'];
	$data['total_balance_product'] = $total_balance_product;		
	$data['driver'] = $trip_details['driver'];	
	$data['vehicle'] = $trip_details['vehicle'];	
	$data['date'] = $formattedDate;	
	$data['partial_not_delivered_box']=$partial_not_delivered_box;
	$data['partial_not_delivered_piece']=$partial_not_delivered_piece;
	$data['partial_undelivered']=$partial_undelivered;
	$data['total_manifest']=count($trip_manifest);

	$data['delivered_products_list'] =$trip_delivered_order_product;
	$data['undelivered_products_list'] =$trip_undelivered_order_product;
	$data['return_products_list'] =$return_product_list;
	$data['return_products_piece'] =$return_products_piece;
	$data['return_products_box'] =$return_products_box;
	$data['payment_collection'] =$payment_collection;
	$data['total_products_box'] =$total_products_box;
	$data['total_products_piece'] =$total_products_piece;
	$data['delivered_products_box'] =$delivered_products_box;
	$data['delivered_products_piece'] =$delivered_products_piece;
	$data['undelivered_products_box'] =$undelivered_products_box + ($partial_not_delivered_box ? $partial_not_delivered_box : 0);
	$data['undelivered_products_piece'] =$undelivered_products_piece + ($partial_not_delivered_piece ? $partial_not_delivered_piece : 0);
	$data['total_products_list'] = $total_product_list;
	$data['trip_order_details'] = $trip_order_details;
	$data['return_details'] = $return_details;
	

	if (!empty($data)) {
		$response_arr = array(
			'success' => true,
			'message' => 'Delivery Details Found',
			'data' => $data
			
		);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch order list',
		);
	}
	echo json_encode($response_arr);
}



public function reassign_order(){
	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);

	if(!empty($manifest_id) && !empty($id) && !empty($reason_reassign)){

		$manifest = $this->db->query("select print_id from sma_manifest where manifest_id='$manifest_id'")->row_array();
		$idString = $manifest['print_id'];
		$idArray = explode(',', $idString);
		$idArray = array_diff($idArray, array($id));
		$orderIds = implode(',', $idArray);

		$update_manifest = array('print_id' => $orderIds);  
		$this->db->where('manifest_id', $manifest_id);
		$this->db->update('sma_manifest', $update_manifest);  

		$update_sale = array('sale_status' => 'Invoiced', 'manifest_id' => '');  
		$this->db->where('id', $id);
		$this->db->update('sma_sales', $update_sale);  

		$date = date("Y-m-d");

		$insert = array('sale_id' => $id, 'date' => $date, 'manifest_id' => $manifest_id,'reason_reassign'=> $reason_reassign);
		$this->db->insert('sma_order_history', $insert);


		$manifest = $this->db->query("select id FROM sma_sales WHERE manifest_id='$manifest_id' AND (sale_status ='Undelivered' || sale_status ='Delivered' )")->result_array();
		$total_order = $this->db->query("select id FROM sma_sales WHERE manifest_id='$manifest_id'")->result_array();

		if (count($manifest) == count($total_order)) 
		{             
			$date = date('Y-m-d');
			$this->db->query("UPDATE `sma_manifest` SET `delivered_status` = 'Y' WHERE manifest_id ='$manifest_id'");
		}

		$response_arr = array(
			'success' => true,
			'message' => 'Order reassigned successfully',	
		);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Order not reassigned successfully',
		);
	}
	echo json_encode($response_arr);

}

public function complete_trip(){

	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);
	
	if (!empty($trip_id)) {
		
		$date = date('Y-m-d');
		$update_sale = array('status' => 'Completed','complete_date'=>$date,'complete_by'=>$user_id);  
		$this->db->where('id', $trip_id);
		$this->db->update('sma_trip', $update_sale);  

		$response_arr = array(
			'success' => true,
			'message' => 'Trip complete successfully',		
		);

	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Trip not complete',
		);
	}
	echo json_encode($response_arr);

}
public function order_undelivered(){
	
	extract($_POST);    
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);
	if (!empty($id)) 
	{       
		$update_sale = array("sale_status" => 'Undelivered', "undelivered_reson" => $undelivered_reason);	
		$this->db->where('id', $id);
		$this->db->update('sma_sales', $update_sale);

		$order_details = $this->db->query("select manifest_id FROM sma_sales WHERE id='$id'")->row_array();
		$manifest_id = $order_details['manifest_id'];
		$manifest = $this->db->query("select id FROM sma_sales WHERE manifest_id='$manifest_id' AND sale_status ='Undelivered'")->result_array();
		$total_order = $this->db->query("select id FROM sma_sales WHERE manifest_id='$manifest_id'")->result_array();

		if (count($manifest) == count($total_order)) 
		{				
			$date = date('Y-m-d');
			$this->db->query("UPDATE `sma_manifest` SET `intransit` = 'N' WHERE manifest_id ='$manifest_id'");
		}

		$inv_items = $this->sales_model->getAllInvoiceItems($id);

		foreach ($inv_items as $item) 
		{
			$productid=$item->product_id;
			$iteamid=$item->id;
			$orderqty=$item->quantity;

			$this->update_product_stock($productid,$iteamid,$orderqty);

		}
		


		$message = lang('Order Undelivered successfully');

		$response = array(
			'success' => true,
			'message' => $message
		);      

	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Order not Undelivered',
		);
	}
	echo json_encode($response);
}


public function update_product_stock($productid,$iteamid,$orderqty)
{
	$check_quantity = $this->sales_model->check_quantity($productid);
	$sale_items = $this->db->query("select id,quantity,order_qty,order_type from sma_sale_items where id='$iteamid'")->row_array();
	
	$quantity = $sale_items['quantity'];
	$order_type = $sale_items['order_type'];

	$quan = round($check_quantity->quantity);


	if ($order_type == "box")
	{
		$check_quantity = $this->sales_model->check_quantity($productid);
		$quan = round($check_quantity->quantity);
		$quantity = ($quan + $orderqty);
		$data = ['quantity' => $quantity];
		$cid = $productid;			

	} else 
	{
		
		$products1 = $this->db->query("select parent_id,pack,id from sma_products where id='$productid'")->row_array();

		$parent_id = $products1['parent_id'];

		if($parent_id==0)
		{
			$parent_id = $products1['id'];
		} 
		$quantity= $quan+$orderqty;
		$data = ['split_quantity' => $quantity];
		$cid = $parent_id;
		
	}
	$c = $this->sales_model->update_quantity($cid, $data);

}


public function update_trip_vehicle(){
	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);
	if(!empty($tripId) && !empty($vehicle_id)){
		$update_vehicle = array("vehicle_id" => $vehicle_id);	
		$this->db->where('id', $tripId);
		$this->db->update('sma_trip', $update_vehicle);

		$message = lang('Vehicle update successfully');

		$response = array(
			'success' => true,
			'message' => $message
		);      

	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Vehicle not update successfully',
		);
	}
	echo json_encode($response);
}

public function get_trip_summary_details() {
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = $str;
	$this->load->database($token);

	$trip_id = $this->input->post('tripId');
	$trip_delivered_order = $this->db->query("select (sma_sale_items.quantity * sma_sale_items.unit_price) AS grand_total, item_tax AS item_tax,sma_sale_items.order_type,sma_sale_items.quantity 
		FROM sma_sales
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id 
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sales.sale_status = 'Delivered' 
		AND sma_manifest_trip.trip_id = '$trip_id' 
		AND sma_sale_items.is_delete = '0'")->result_array();

	$total_delivered_box = 0;
	$total_delivered_piece = 0;
	$total_delivered_box_price = 0;$total_delivered_piece_price = 0;
	foreach ($trip_delivered_order as $product) {

		if ($product['order_type'] == 'box') {

			$grand_total = $product['grand_total'];
			$item_tax = $product['item_tax'];
			$quantity = $product['quantity'];											
			$total_delivered_box_price += $this->sma->formatDecimal($grand_total + $item_tax, 2);
			$total_delivered_box += $quantity;
		}elseif ($product['order_type'] == 'piece'){
			$grand_total = $product['grand_total'];
			$item_tax = $product['item_tax'];
			$quantity =$product['quantity'];											
			$total_delivered_piece_price += $this->sma->formatDecimal($grand_total + $item_tax, 2);
			$total_delivered_piece += $quantity;		

		}
	}


	$trip_details = $this->db->query("select sma_trip.id, sma_trip.trip_number, sma_trip.status, sma_trip.createdOn, sma_users.username AS driver, sma_vehicle.model_name AS vehicle 
		FROM sma_trip 
		LEFT JOIN sma_users ON sma_users.id = sma_trip.driver_id 
		LEFT JOIN sma_vehicle ON sma_vehicle.id = sma_trip.vehicle_id 
		WHERE sma_trip.id='$trip_id'")->row_array();

	$date = new DateTime($trip_details['createdOn']);
	$formattedDate = $date->format('d-m-Y');

	$data['trip_number'] = $trip_details['trip_number']; 
	$data['driver'] = $trip_details['driver']; 
	$data['vehicle'] = $trip_details['vehicle']; 
	$data['date'] = $formattedDate;

	$total_product_list = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.order_type AS qtyType, CAST(sma_sale_items.quantity AS UNSIGNED) AS qty, CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS not_delivered_qty,sma_sale_items.unit_price 
		FROM sma_sales 
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id 
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id 
		WHERE sma_manifest_trip.trip_id = '$trip_id' 
		AND sma_sale_items.is_delete = '0'")->result_array();

	$total_products_box = 0;
	$total_products_piece = 0;
	$total_box_price = 0;$total_piece_price = 0;
	foreach ($total_product_list as $product) {

		if ($product['qtyType'] == 'box') {
			$tbox = $product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);
			$unit_price = $product['unit_price'];
			$productid = $product['productId'];
			$product = $this->db->query("select tax_rate from sma_products where id='$productid'")->row_array();
			$tax_rate = $product['tax_rate'];
			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			$total=($unit_price * $tbox);
			$tax = $this->sma->formatDecimal((($unit_price * $tbox) * $tax_parcent['rate']) / 100, 2);
			$total_box_price += $this->sma->formatDecimal($total + $tax, 2);
			$total_products_box += $tbox;
		}elseif ($product['qtyType'] == 'piece'){
			$tpiece=$product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);
			$total_products_piece += $tpiece;
			$unit_price = $product['unit_price'];
			$productid = $product['productId'];
			$product = $this->db->query("select tax_rate from sma_products where id='$productid'")->row_array();
			$tax_rate = $product['tax_rate'];
			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			$total=($unit_price * $tpiece);
			$tax = $this->sma->formatDecimal((($unit_price * $tpiece) * $tax_parcent['rate']) / 100, 2);

			$total_piece_price += $this->sma->formatDecimal($total + $tax, 2);
		}
	}

	$grand_total = $trip_delivered_order[0]['grand_total'] ? $trip_delivered_order[0]['grand_total'] : 0;
	$item_tax = $trip_delivered_order[0]['item_tax'] ? $trip_delivered_order[0]['item_tax'] : 0;
	$delivered_amt = $grand_total + $item_tax;





				                	// Start trip_order
	$trip_order = $this->db->query("select sma_sales.id, sma_sales.customer_id, sma_sales.reference_no, sma_sales.customer AS customerName, sma_companies.accound_no AS customerAccountNo, sma_companies.credit_type_name AS credit_type_name, sma_sales.created_by, sma_sales.grand_total, sma_sales.grand_total AS previous_dues, sma_sales.grand_total AS driver_collect,sma_sales.grand_total AS total_dev, sma_sales.grand_total AS type 
		FROM sma_sales 
		LEFT JOIN sma_companies ON sma_sales.customer_id = sma_companies.id 
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id 
		WHERE sma_manifest_trip.trip_id = '$trip_id' 
		GROUP BY sma_sales.id")->result_array();		                	
	$total_cash =0; $total_cheque_amt=0;
	foreach ($trip_order as &$row) {
		$id = $row['id'];
		$total_credit = total_sale_person_collected($row['customer_id']) + total_driver_collected($row['customer_id']);
		$row['previous_dues'] = number_format(get_total($row['customer_id']) - $total_credit, 2);
		$row['total_dev'] =   number_format((get_total($row['customer_id']) - $total_credit)-$row['grand_total'],2);
		$driver_collected_data = $this->total_driver_collected($id, $trip_id);

		if ($row['credit_type_name'] == '') {
			$row['credit_type_name'] = 'Cash';
		}
		$total_cash += (float) $driver_collected_data['total_cash_amt'];
		$total_cheque_amt += (float) $driver_collected_data['total_cheque_amt'];

		$row['driver_collect'] = number_format($driver_collected_data['total_cash_collected'], 2);
		$row['type'] = $driver_collected_data['payment_modes'];
	}
				                	//end trip_order

				                	//Good Report Start
	$partial_undeliv_product = $this->db->query("select sma_sale_items.sale_id as orderId, sma_sale_items.product_code, sma_sale_items.product_name ,sma_sale_items.quantity as not_delivered_qty, sma_sale_items.order_type,sma_sales.reference_no,sma_sale_items.not_delivered_qty,sma_sale_items.unit_price,sma_sale_items.product_id AS productId, sma_sale_items.return_reason,sma_sales.sale_status FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sale_items.not_delivered_qty  IS NOT NULL  AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result();
	foreach($partial_undeliv_product as $trip){
		$trip->sale_status ='Partial';

	}

	$trip_undelivered_products = $this->db->query("select sma_sale_items.sale_id as orderId, sma_sale_items.product_name , sma_sale_items.product_code , CAST(sma_sale_items.quantity AS UNSIGNED) AS not_delivered_qty, sma_sale_items.order_type,sma_sale_items.unit_price,sma_sale_items.product_id AS productId,sma_sales.undelivered_reson as return_reason,sma_sales.sale_status,sma_sales.reference_no
		FROM sma_sales 
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id 
		INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id 
		WHERE sma_sales.sale_status = 'Undelivered' 
		AND sma_sale_items.quantity != '0' 
		AND sma_manifest_trip.trip_id = '$trip_id' 
		AND sma_sale_items.is_delete = '0'")->result();

	$return_product_list = $this->db->query("select sma_return_items.product_name,product_code,return_type as order_type,sma_return_items.return_quantity as not_delivered_qty,sma_return_items.returnReason as return_reason,sma_sales.reference_no,sma_return_items.returnReason as sale_status, sma_return_items.sale_id as orderId,sma_return_items.subtotal as unit_price,sma_return_items.product_id AS productId FROM `sma_returns` left join sma_return_items on sma_return_items.return_id=sma_returns.id left join sma_sales on sma_sales.id=sma_return_items.sale_id where trip_id='$trip_id'")->result();
	$return_products_box = 0;
	$return_products_piece = 0;
	$return_box_price = 0;
	$return_piece_price = 0;
	foreach($return_product_list as $r){
		$r->sale_status ='Return';
		if ($r->order_type == 'box') {
			$return_products_box += $r->not_delivered_qty;
			$return_box_price += $r->unit_price;
		} elseif ($r->order_type == 'piece') {
			$return_products_piece += $r->not_delivered_qty;
			$return_piece_price += $r->unit_price;
		}
	}

	$trip_undelivered_order_product = array_merge($trip_undelivered_products, $partial_undeliv_product);
	$undelivered_products_box = 0;
	$undelivered_products_piece = 0;
	$undelivered_box_price = 0;
	$undelivered_piece_price = 0;

	foreach ($trip_undelivered_order_product as $product) {
		if ($product->order_type == 'box') {

			$undelivered_products_box += $product->not_delivered_qty;
			$tbox = $product->not_delivered_qty;
			$unit_price = $product->unit_price;
			$productid = $product->productId;
			$product = $this->db->query("select tax_rate from sma_products where id='$productid'")->row_array();
			$tax_rate = $product['tax_rate'];
			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			$total=($unit_price * $tbox);
			$tax = $this->sma->formatDecimal((($unit_price * $tbox) * $tax_parcent['rate']) / 100, 2);
			$undelivered_box_price += $this->sma->formatDecimal($total + $tax, 2);

		} elseif ($product->order_type == 'piece') {
			$undelivered_products_piece += $product->not_delivered_qty;
			$tpiece=$product->not_delivered_qty;
				                			// $total_products_piece += $tpiece;
			$unit_price = $product->unit_price;
			$productid = $product->productId;
			$product = $this->db->query("select tax_rate from sma_products where id='$productid'")->row_array();
			$tax_rate = $product['tax_rate'];
			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			$total=($unit_price * $tpiece);
			$tax = $this->sma->formatDecimal((($unit_price * $tpiece) * $tax_parcent['rate']) / 100, 2);

			$undelivered_piece_price += $this->sma->formatDecimal($total + $tax, 2);

		}
	}

	$trip_undelivered = array_merge($partial_undeliv_product, $trip_undelivered_products,$return_product_list);

	$partial_undelivered  = array();
	foreach ($trip_undelivered as $r) {	
		$partial_undelivered[] = array(
			"productCode" => $r->product_code,
			"productName" => $r->product_name,
			"order_type" => $r->order_type,
			"reason" => $r->return_reason,
			"orderId" => $r->orderId,
			"not_delivered_qty" => $r->not_delivered_qty,
			'status' => $r->sale_status,	
			"reference_no"	=> $r->reference_no		
		);
	}
	
				                	//end Good Return

	$data['partial_undelivered']=$partial_undelivered;
	$data['trip_order'] = $trip_order;
	$data['total_cash'] = $total_cash;
	$data['total_cheque_amt'] = $total_cheque_amt;
	$data['delivered_amt'] = $delivered_amt;

	$data['total_products_box'] = $total_products_box;
	$data['total_products_piece'] = $total_products_piece;
	$data['total_box_price'] = $total_box_price;
	$data['total_piece_price'] = $total_piece_price;
	$data['total_price'] = $total_box_price + $total_piece_price;

	$data['total_delivered_box'] = $total_delivered_box;
	$data['total_delivered_piece'] = $total_delivered_piece;
	$data['total_delivered_box_price'] = $total_delivered_box_price;
	$data['total_delivered_piece_price'] = $total_delivered_piece_price;
	$data['total_delivered_price'] = $total_delivered_box + $total_delivered_piece;

	$data['undelivered_products_box'] = $undelivered_products_box;
	$data['undelivered_products_piece'] = $undelivered_products_piece;
	$data['undelivered_box_price'] = $undelivered_box_price;
	$data['undelivered_piece_price'] = $undelivered_piece_price;
	$data['total_undelivered_price'] = $undelivered_box_price + $undelivered_piece_price;

	$data['return_products_box'] = $return_products_box;
	$data['return_products_piece'] = $return_products_piece;
	$data['return_box_price'] = $return_box_price;
	$data['return_piece_price'] = $return_piece_price;
	$data['total_return_price'] = $return_box_price + $return_piece_price;

	if (!empty($data)) {
		$response_arr = array(
			'success' => true,
			'message' => 'Summary Details Found',
			'data' => $data
		);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch summary list'
		);
	}

	echo json_encode($response_arr);
}


public function total_driver_collected($id,$trip_id){
	$driver_payment_collection = $this->db->query("select sma_companies.name AS customerName, sma_companies.accound_no AS customerAccountNo, amount AS cashCollected, payment_mode FROM sma_driver_collected_amount LEFT JOIN sma_companies ON sma_companies.id = sma_driver_collected_amount.customer_id WHERE trip_id = '$trip_id' AND sales_id = '$id'")->result_array();

	$payment_modes = [];
	$total_cash_collected = 0;
	$total_cash_amt = 0.0;
	$total_cheque_amt = 0.0;	
	foreach ($driver_payment_collection as $driver_payment) {
		$payment_modes[] = $driver_payment['payment_mode'];
		$cashCollected = $driver_payment['cashCollected'];

		if ($cashCollected) {
			$total_cash_collected += $cashCollected;
		}
		if ($driver_payment['payment_mode'] == 'cash') {
			$total_cash_amt += $cashCollected;
		} elseif ($driver_payment['payment_mode'] == 'cheque') {
			$total_cheque_amt += $cashCollected;
		}
	}

	$payment_modes_string = implode('/', $payment_modes);


	return [
		'payment_modes' => $payment_modes_string,
		'total_cash_collected' => number_format($total_cash_collected, 2),
		'total_cash_amt' => number_format($total_cash_amt, 2),
		'total_cheque_amt' => number_format($total_cheque_amt, 2)
	];
}

public function missing_product_order(){
	extract($_POST);

	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];
	$condition = "(sma_sale_items.is_delete = '1' OR sma_sale_items.order_qty > sma_sale_items.quantity) and sma_sale_items.is_return = '0'";
	if ($start_date != '' && $end_date != '') {
		$form_date = date("Y-m-d", strtotime($start_date));
		$to_date = date("Y-m-d", strtotime($end_date));
		$condition .= " AND DATE(sma_sales.date) BETWEEN '$form_date' AND '$to_date'";
	} else {
		$form_date = date('Y-m-01');
		$to_date = date('Y-m-t');
		$condition .= " AND DATE(sma_sales.date) BETWEEN '$form_date' AND '$to_date'";
	}

	if ($product_id != '') {
		$condition .= " AND sma_sale_items.product_id = '$product_id'";
	}
							// echo $condition;exit();
	$totalRecords = $this->db->query(" select COUNT(*) AS allcount FROM sma_sales 
		LEFT JOIN sma_companies ON sma_sales.customer_id = sma_companies.id 
		LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
		LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id      
		WHERE $condition ")->row()->allcount;

	$dataQuery = "select sma_sales.id,sma_sales.sale_status,sma_sales.customer_id, sma_sales.reference_no, sma_sales.customer AS customerName, sma_companies.accound_no AS customerAccountNo, sma_companies.postal_code AS customerPostalCode, sma_sales.grand_total, sma_sales.grand_total AS previous_dues, SUM(sma_sale_items.order_qty - sma_sale_items.quantity) AS total 
	FROM sma_sales 
	LEFT JOIN sma_companies ON sma_sales.customer_id = sma_companies.id 
	LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
	LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id      
	WHERE $condition AND (sma_sale_items.product_code LIKE '%$searchValue%' 
	OR sma_sale_items.product_name LIKE '%$searchValue%') 
	GROUP BY sma_sale_items.sale_id LIMIT $start, $length"; 

							//echo $dataQuery;exit();
	$data = $this->db->query($dataQuery)->result();
							//print_r($data);exit();

	$i = $start + 1;
	$data1=array();
	foreach ($data as $r){   

		$prev_due = number_format(get_total($r->customer_id) - total_sale_person_collected($r->customer_id) - total_driver_collected($r->customer_id), 2);

		$data1[] = [
			"i"=>$i++,
			"id"=>$r->id,
			"reference_no"=>$r->reference_no,
			"customerName"=>$r->customerName .'('.$r->customerAccountNo.')',
			"customerPostalCode"=>$r->customerPostalCode,
			"grand_total"=>number_format($r->grand_total, 2),
			"previous_dues" => number_format($prev_due, 2),
			"total" =>number_format($r->total),
			"sale_status"=>$r->sale_status];
		}

		$result = [
			"draw" => $draw,
			"recordsTotal" =>  count($data),
			"recordsFiltered" =>$totalRecords,
			"data" => $data1,
		];

		echo json_encode($result);
		exit();
	}
	public function get_vehicles()
	{
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = $str;
	$this->load->database($token);
	$this->db->select('id as vehicleId,model_name as modelName,registration_number as registrationNo');
	$query = $this->db->get('sma_vehicle')->result();

	if(count($query) != 0)
	{
		$response_arr = array(
			'data' => $query,
			'success' => true,
			'message' => 'Vehicle details found'
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Vehicle details not found',
		);
		echo json_encode($response_arr);
	}    
	}
}