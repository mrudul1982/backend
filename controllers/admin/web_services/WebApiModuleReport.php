<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModuleReport extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->digital_upload_path = 'files/';
		$this->upload_path = 'assets/uploads/';
		$this->thumbs_path = 'assets/uploads/thumbs/';
		$this->image_types = 'gif|jpg|jpeg|png|tif';
		$this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
		$this->allowed_file_size = '1024';
		$this->load->library('upload');
	}
	

	public function get_missing_order()
	{
		$secure_key=$this->input->request_headers();
		$token=$secure_key['authorization'];

		$this->load->helper('jwt_helper');
		$str = ltrim($token, 'Bearer ');
	//$token='e'.$str;
		$token=$str;

		//$user =jwt::decode($token,$this->config->item('jwt_key'));	
	//	$prefix=$user->prefix;
		$this->load->database($token);

		extract($_POST);

		$start_date = $this->input->post('searchByFromdate');
		$end_date = $this->input->post('searchByTodate');
		$draw = intval($this->input->post("draw"));
		$start = intval($this->input->post("start"));
		$length = intval($this->input->post("length"));
		$searchValue = $this->input->post('search')['value'];
		$product_id = $this->input->post('product_id');
		$sales_rep_id = $this->input->post('sales_rep_id');

		$condition = "(sma_sale_items.quantity = '0.00' OR sma_sale_items.is_delete = '1')";

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

		if ($sales_rep_id != '') {
			$condition .= " AND sma_sales.created_by = '$sales_rep_id'";
		}

		$totalRecordsQuery = "select COUNT(*) AS allcount 
		FROM sma_sales 
		WHERE id IN (
		SELECT sma_sale_items.sale_id 
		FROM sma_sale_items 
		LEFT JOIN sma_sales ON sma_sale_items.sale_id = sma_sales.id 
		WHERE $condition 
		GROUP BY sma_sale_items.sale_id
	)";

	$totalRecords = $this->db->query($totalRecordsQuery)->row()->allcount;

	$dataQuery = "select sma_sales.id, DATE_FORMAT(sma_sales.date, '%d-%m-%Y') AS date,reference_no, 
	customer,sale_status, SUM(order_qty) AS total,sma_companies.accound_no, SUM(unit_price * order_qty) AS totalprice  
	FROM sma_sales 
	LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
	LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id 
	WHERE$condition AND (sma_companies.accound_no LIKE '%$searchValue%' 
	OR sma_sales.customer LIKE '%$searchValue%' 
	OR sma_sales.reference_no LIKE '%$searchValue%') 
	GROUP BY sma_sale_items.sale_id 
	ORDER BY sma_sales.id DESC 
	LIMIT $start, $length";
	$data = $this->db->query($dataQuery)->result();

	$data1 = [];
	$i = $start + 1;
	$order_total = 0;
	$missing_total = 0;
	$deliverd_total = 0;

	foreach ($data as $r) {   
		$order_total += get_order_total($r->id);
		$missing_total += $r->totalprice;
		$deliverd_total += get_deliverd_total($r->id);
		$data1[] = [
			"i" => $i++,
			"date" => $r->date,
			"id" => $r->id,
			"reference_no" => $r->reference_no,
			"customer" => $r->customer . '(' . $r->accound_no . ')',
			"order_total" => number_format(get_order_total($r->id), 2),
			"missing_total" => number_format($r->totalprice, 2),
			"deliverd_total" => number_format(get_deliverd_total($r->id), 2),
			"total_items" => $r->total,
			"sale_status" => $r->sale_status,
		];
	}

	$result = [
		"draw" => $draw,
		"recordsTotal" => count($data),
		"recordsFiltered" => $totalRecords,
		"data" => $data1,
		"form_date" => date("d-m-Y", strtotime($form_date)),
		"to_date" => date("d-m-Y", strtotime($to_date)),
		"order_total" => number_format($order_total, 2),
		"missing_total" => number_format($missing_total, 2),
		"deliverd_total" => number_format($deliverd_total, 2),
	];

	echo json_encode($result);
	exit();
}


public function get_missing_orders_export($start_date,$end_date,$product_id='',$sales_rep_id =''){


	$filename = 'Missing_Orders_Report_' . date('Ymd') . '.csv';
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=$filename");
	header("Content-Type: application/csv; ");
	$condition = "(sma_sale_items.quantity = '0.00' OR sma_sale_items.is_delete = '1')";

	$form_date = date("Y-m-d", strtotime($start_date));
	$to_date = date("Y-m-d", strtotime($end_date));
	$condition .= " AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') >= '$form_date' AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') <= '$to_date'";

	if ($product_id != '') {
		$condition .= " AND sma_sale_items.product_id = '$product_id'";
	}

	if ($sales_rep_id != '') {
		$condition .= " AND sma_sales.created_by = '$sales_rep_id'";
	}
	$usersData=$this->db->query("select sma_sales.id,DATE_FORMAT(sma_sales.date, '%d-%m-%Y') AS date,reference_no, 
		customer,sale_status,SUM(order_qty) AS total,sma_companies.accound_no,SUM(unit_price * order_qty) AS totalprice  
		FROM sma_sales LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
		LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id 
		WHERE $condition GROUP BY sma_sale_items.sale_id ORDER BY sma_sales.id DESC")->result();

                    $file = fopen('php://output', 'w'); // Corrected this line
                    $header = array("Sr","Order Date", "Order Id", "Customers (A/C No)", "Order Amount", "Missing Amount", "Deliverd Amount", "Total Product", "Status");
                    fputcsv($file, $header);
                    $i=1;
                    foreach ($usersData as $b) {  

                    	$line = array(                               
                    		$i++,
                    		$b->date,
                    		$b->reference_no,
                    		$b->customer . '(' . $b->accound_no . ')',
                    		number_format(get_order_total($b->id), 2),
                    		number_format($b->totalprice, 2),
                    		number_format(get_deliverd_total($b->id), 2),
                    		$b->total,
                    		$b->sale_status,                          

                    	);
                    	fputcsv($file, $line);
                    }
                    fclose($file);
                    exit;
                }


                public function missing_order_details(){
                	extract($_POST);		
                	$secure_key=$this->input->request_headers();
                	$token=$secure_key['authorization'];

                	$this->load->helper('jwt_helper');
                	$str = ltrim($token, 'Bearer ');
	//$token='e'.$str;
                	$token=$str;

		//$user =jwt::decode($token,$this->config->item('jwt_key'));	
	//	$prefix=$user->prefix;
                	$this->load->database($token);

                	$id = $this->input->post('id');

                	$draw = intval($this->input->post("draw"));
                	$start = intval($this->input->post("start"));
                	$length = intval($this->input->post("length"));
                	$searchValue = $this->input->post('search')['value'];
                	$condition = "(sma_sale_items.quantity = '0.00' OR sma_sale_items.is_delete = '1') and sma_sales.id='$id'";

                	$totalRecords = $this->db->query("select COUNT(*) AS allcount FROM sma_sales LEFT JOIN sma_sale_items on sma_sale_items.sale_id= sma_sales.id WHERE $condition")->row()->allcount;

                	$data = $this->db->query("select sma_sales.reference_no, product_code,product_name,unit_price,order_qty,sma_sale_items.order_type  FROM `sma_sales`
                		LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id
                		WHERE $condition AND (sma_sale_items.product_code LIKE '%$searchValue%' OR sma_sale_items.product_name LIKE '%$searchValue%' OR sma_sale_items.order_type LIKE '%$searchValue%')  ORDER BY sma_sales.id DESC LIMIT $start, $length")->result();
                	$data1 = [];
                	$i=1;
                	foreach ($data as $r) {	
                		$reference_no=$r->reference_no;
                		$data1[] = [
                			"i" => $i++,
                			"product_name" => $r->product_name . '(' . $r->product_code . ')',				
                			"totalprice" => number_format($r->unit_price * $r->order_qty, 2),
                			"unit_price" => $r->unit_price,
                			"order_qty" => $r->order_qty,
                			"order_type" => $r->order_type,
                		];
                	}
                	$result = [
                		"draw" => $draw,
                		"recordsTotal" =>  count($data),
                		"recordsFiltered" =>$totalRecords,
                		"data" => $data1,
                		"reference_no" =>$reference_no,	
                	];

                	echo json_encode($result);
                	exit();
                }


                public function get_missing_product()
                {
                	extract($_POST);

                	$secure_key=$this->input->request_headers();
                	$token=$secure_key['authorization'];

                	$this->load->helper('jwt_helper');
                	$str = ltrim($token, 'Bearer ');
	//$token='e'.$str;
                	$token=$str;

		//$user =jwt::decode($token,$this->config->item('jwt_key'));	
	//	$prefix=$user->prefix;
                	$this->load->database($token);

                	$start_date = $this->input->post('searchByFromdate');
                	$end_date = $this->input->post('searchByTodate');
                	$draw = intval($this->input->post("draw"));
                	$start = intval($this->input->post("start"));
                	$length = intval($this->input->post("length"));
                	$searchValue = $this->input->post('search')['value'];
                	$product_id = $this->input->post('product_id');
                	$sales_rep_id = $this->input->post('sales_rep_id');                	
                	$condition = "(sma_sale_items.quantity = '0.00' OR sma_sale_items.is_delete = '1')";
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

                	if ($sales_rep_id != '') {
                		$condition .= " AND sma_sales.created_by = '$sales_rep_id'";
                	}

                	$dataQuery = "select sma_sales.id,sale_status,product_code,product_name,size, SUM(order_qty) AS total, SUM(unit_price * order_qty) AS totalprice ,sma_sale_items.order_type 
                	FROM sma_sales 
                	LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
                	LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id      
                	WHERE $condition AND (sma_sale_items.product_code LIKE '%$searchValue%' 
                	OR sma_sale_items.product_name LIKE '%$searchValue%') 
                	GROUP BY sma_sale_items.product_id 
                	ORDER BY total DESC";
//print_r($dataQuery);exit();
                	$data = $this->db->query($dataQuery)->result();

                	$data1 = [];
                	$i = $start + 1;
                	$order_total = 0;
                	$missing_total = 0;
                	$deliverd_total = 0;

                	foreach ($data as $r) {   

                		$missing_total += $r->totalprice;

                		$data1[] = [
                			"i" => $i++,         
                			"id" => $r->id,           
                			"product_name" => $r->product_name .' '.$r->size.'(' . $r->product_code . ')',
                			"order_type" => $r->order_type,
                			"total_items" => $r->total,           
                			"missing_total" => number_format($r->totalprice, 2),          
                		];
                	}

                	$result = [
                		"draw" => $draw,
                		"recordsTotal" => count($data1),
                		"recordsFiltered" => count($data1),
                		"data" => $data1,
                		"form_date" => date("d-m-Y", strtotime($form_date)),
                		"to_date" => date("d-m-Y", strtotime($to_date)),
                		"order_total" => number_format($order_total, 2),
                		"missing_total" => number_format($missing_total, 2),
                		"deliverd_total" => number_format($deliverd_total, 2),
                	];

                	echo json_encode($result);
                	exit();
                }

                public function get_profit_loss_report()
                {
                	extract($_POST);

                	$secure_key=$this->input->request_headers();
                	$token=$secure_key['authorization'];

                	$this->load->helper('jwt_helper');
                	$str = ltrim($token, 'Bearer ');
	//$token='e'.$str;
                	$token=$str;

		//$user =jwt::decode($token,$this->config->item('jwt_key'));	
	//	$prefix=$user->prefix;
                	$this->load->database($token);

                	$start_date = $this->input->post('searchByFromdate');
                	$end_date = $this->input->post('searchByTodate');
                	$draw = intval($this->input->post("draw"));
                	$start = intval($this->input->post("start"));
                	$length = intval($this->input->post("length"));
                	$searchValue = $this->input->post('search')['value'];
                	$route_id = $this->input->post('route_id');
                	$sales_rep_id = $this->input->post('sales_rep_id');
                	
                	$condition = "(sma_sale_items.is_delete = '0')";
                	if ($start_date != '' && $end_date != '') {
                		$form_date = date("Y-m-d 00:00:00", strtotime($start_date));
                		$to_date = date("Y-m-d  23:59:59", strtotime($end_date));
                		$condition .= " AND DATE(sma_sales.date) BETWEEN '$form_date' AND '$to_date'";
                	} else {
                		$form_date = date('Y-m-01 00:00:00');
                		$to_date = date('Y-m-t 23:59:59');
                		$condition .= " AND DATE(sma_sales.date) BETWEEN '$form_date' AND '$to_date'";
                	}

                	if ($route_id != '') {
                		$condition .= " AND sma_companies.route = '$route_id'";
                	}

                	if ($sales_rep_id != '') {
                		$condition .= " AND sma_sales.created_by = '$sales_rep_id'";
                	}

                	$dataQuery = "select sma_sales.id,DATE_FORMAT(sma_sales.date, '%d-%m-%Y') AS date,sma_companies.accound_no,customer_id,customer,reference_no,sma_sales.sale_status,
                	SUM(CASE WHEN sma_sale_items.order_type = 'box' THEN unit_price * sma_sale_items.quantity ELSE 0 END) AS box_totalprice,
                	SUM(CASE WHEN sma_sale_items.order_type = 'box' THEN cost * sma_sale_items.quantity ELSE 0 END) AS box_costtotal,
                	SUM(CASE WHEN sma_sale_items.order_type = 'piece' THEN unit_price * sma_sale_items.quantity ELSE 0 END) AS piece_totalprice,
                	SUM(CASE WHEN sma_sale_items.order_type = 'piece' THEN  (split_price-(split_price * 25 / 100)) * sma_sale_items.quantity ELSE 0 END) AS piece_costtotal,
                	sma_sale_items.order_type 
                	FROM sma_sales LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
                	LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id 
                	LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id  
                	WHERE  $condition AND (sma_sales.customer LIKE '%$searchValue%' OR sma_companies.accound_no LIKE '%$searchValue%' OR sma_sales.sale_status LIKE '%$searchValue%' 
                	OR sma_sales.reference_no LIKE '%$searchValue%') 
                	GROUP BY  sma_sale_items.sale_id 
                	ORDER BY sma_sales.id DESC";
                	
                	$data = $this->db->query($dataQuery)->result();

                	$data1 = [];
                	$i = $start + 1;
                	$cost_total = 0;
                	$netamount = 0;
                	$profit_total = 0;

                	foreach ($data as $r) {   
                		$net = $r->box_totalprice + $r->piece_totalprice;
                		$cost = $r->box_costtotal + $r->piece_costtotal;						
                		$profit =$net -$cost;					
                		$netamount += $net;
                		$cost_total += $cost;
                		$profit_total += $profit;
                		$data1[] = [
                			"i" => $i++,  
                			"id" => $r->id,          
                			"reference_no" => $r->reference_no,           
                			"customer" => $r->customer .'(' . $r->accound_no . ')',
                			"date" => $r->date,
                			"netamount" => number_format($net, 2),                 
                			"cost" => number_format($cost , 2),  
                			"profit" => number_format($profit, 2),
                			"status" => $r->sale_status,            
                		];
                	}

                	$result = [
                		"draw" => $draw,
                		"recordsTotal" => count($data1),
                		"recordsFiltered" => count($data1),
                		"data" => $data1,
                		"form_date" => date("d-m-Y", strtotime($form_date)),
                		"to_date" => date("d-m-Y", strtotime($to_date)),
                		"netamount" => number_format($netamount, 2),
                		"cost_total" => number_format($cost_total, 2),
                		"profit_total" => number_format($profit_total, 2),
                	];


                	echo json_encode($result);
                	exit();
                }

                public function get_trip_summary_report(){
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


            }