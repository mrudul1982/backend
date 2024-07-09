<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
class WebApiModuleSale extends CI_Controller {

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
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
http_response_code(200); // OK status for preflight request
exit();
}
}


public function saleslist()
{
	extract($_POST);


	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	
	$this->load->database($token);

	$start_date = $this->input->post('searchByFromdate');
	$end_date = $this->input->post('searchByTodate');
	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];

	$condition = "sma_sales.sale_status = 'New' AND sma_sales.id NOT IN (SELECT sale_id FROM sma_daily_sales_sheet) AND sma_sales.is_delete = '0'";

	if ($start_date != '' && $end_date != '') 
	{
		$form_date = date("Y-m-d", strtotime($start_date));
		$to_date = date("Y-m-d", strtotime($end_date));
		$condition .= " AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') >= '$form_date' AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') <= '$to_date'";
	}

	$totalRecords = $this->db->query("select COUNT(*) AS allcount FROM sma_sales WHERE $condition ORDER BY sma_sales.id DESC")->row()->allcount;


	$data = $this->db->query("select sma_sales.id, DATE_FORMAT(sma_sales.date, '%d-%m-%Y') AS date, 
		sma_users.first_name, sma_users.last_name,sma_users.group_id, sma_sales.reference_no, sma_sales.created_by, 
		sma_sales.payment_method, sma_sales.customer, sma_sales.customer_id, sma_sales.sale_status, 
		sma_sales.grand_total, sma_sales.payment_status, sma_sales.cheque_status, sma_routes.route_number, 
		sma_companies.accound_no,sma_companies.postal_code FROM sma_sales 
		LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id 
		LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by 
		LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id 
		WHERE $condition AND (accound_no LIKE '%$searchValue%' OR route_number LIKE '%$searchValue%' 
		OR customer LIKE '%$searchValue%' OR reference_no LIKE '%$searchValue%' OR first_name LIKE '%$searchValue%')
		ORDER BY sma_sales.id DESC LIMIT $start, $length")->result();

	$data1 = [];
	foreach ($data as $r) 
	{
		$ids = '<input type="checkbox" class="checkbox multi-select input-xs" name="val[]" value="' . $r->id . '" />';
		$spc = number_format(sale_person_collected($r->created_by, $r->id), 2);
		$prev_due = number_format(get_total($r->customer_id) - total_sale_person_collected($r->customer_id) - total_driver_collected($r->customer_id), 2);

		if($r->group_id!=3)
		{
			$name=$r->first_name . ' ' . $r->last_name;
		}else
		{
			$name='Customer';
		}

		$data1[] = [
			"ids" => $r->id,
			"date" =>  date('d-m-y', strtotime($r->date)),
			"reference_no" =>$r->reference_no,
			"customer" => $r->customer . '(' . $r->accound_no . ')',
			"grand_total" => number_format($r->grand_total, 2),
			"spc" => @$spc,
			"prev_due" => $prev_due,
			"route_number" => $r->route_number,
			"postal_code"=>$r->postal_code,
			"name" => $name,
//"action" => $action,
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

public function get_new_fs()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
//$token='e'.$str;
	$token=$str;

//	$user =jwt::decode($token,$this->config->item('jwt_key'));	
//	$prefix=$user->prefix;
	$this->load->database($token);

	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];           

	$totalRecords = $this->db->query("select sma_daily_sales_sheet.id FROM `sma_daily_sales_sheet` LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by WHERE sma_sales.sale_status ='New' GROUP BY dss_no ORDER BY sma_daily_sales_sheet.id DESC")->result_array();

	$query = $this->db->query("select sma_daily_sales_sheet.id, sma_daily_sales_sheet.date, dss_no, COUNT(sma_daily_sales_sheet.sale_id) as total, SUM(sma_sales.grand_total) as grand_total, SUM(sma_sales.total_discount) as total_discount, sma_users.first_name, sma_users.last_name FROM `sma_daily_sales_sheet` LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by WHERE sma_sales.sale_status ='New' AND (sma_daily_sales_sheet.dss_no LIKE '%$searchValue%'  OR sma_users.first_name LIKE '%$searchValue%') GROUP BY dss_no ORDER BY sma_daily_sales_sheet.id DESC LIMIT $start, $length")->result();

	$data1 = [];
	$i = 1;
	foreach ($query as $r) {
		$dss_no = $r->dss_no;

		$sheet = $this->db->query("select sma_sales.id FROM sma_sales WHERE sma_sales.id IN (SELECT sale_id FROM sma_daily_sales_sheet WHERE sma_daily_sales_sheet.dss_no='$dss_no') ORDER BY sma_sales.id DESC")->result_array();

		$status = (count($sheet) == $r->total) ? 'Pending' : ((count($sheet) != count($accept_order)) ? 'Partial' : 'Approved');

// $action = [
//     'viewUrl' => admin_url("sales/edit_daily_sales_sheet/" . $r->dss_no),
//     'deleteUrl' => admin_url("sales/delete_front_sheet/" . $r->dss_no)
// ];

		$data1[] = [
			"id" => $i++,
			"date" => date("d-m-Y", strtotime($r->date)),
			"dss_no" => $r->dss_no,
			"sp" => $r->first_name . $r->last_name,
			"inv_total" => $r->total,
			"spc" => number_format($r->spc, 2),
			"status" => $status,
//"action" => $action
		];
	}

	$result = [
		"draw" => intval($draw),
		"recordsTotal" => count($totalRecords),
		"recordsFiltered" => count($totalRecords),
		"data" => $data1
	];

	echo json_encode($result);
	exit();
}


public function get_approve_fs()
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
	$draw=intval($this->input->post("draw"));
	$start=intval($this->input->post("start"));
	$length=intval($this->input->post("length"));
	$searchValue = $this->input->post('search')['value'];           

	$totalRecords = $this->db->query("select sma_daily_sales_sheet.id FROM `sma_daily_sales_sheet` LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by where sma_sales.sale_status !='New' GROUP by dss_no ORDER BY sma_daily_sales_sheet.id DESC")->result_array();


	$query = $this->db->query("select sma_daily_sales_sheet.id, sma_daily_sales_sheet.date, dss_no, 
		COUNT(sma_daily_sales_sheet.sale_id) as total, SUM(sma_sales.grand_total) as grand_total, 
		SUM(sma_sales.total_discount) as total_discount, sma_users.first_name, sma_users.last_name 
		FROM `sma_daily_sales_sheet` 
		LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id 
		LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by 
		WHERE sma_sales.sale_status != 'New' 
		AND (sma_daily_sales_sheet.dss_no LIKE '%$searchValue%' OR sma_users.first_name LIKE '%$searchValue%') 
		GROUP BY dss_no,sma_daily_sales_sheet.date ORDER BY sma_daily_sales_sheet.id DESC 
		LIMIT $start, $length")->result();


	$data1 = [];
	$i=1;
	foreach ($query as $r) 
	{
		$dss_no = $r->dss_no;

		$sheet = $this->db->query("select sma_sales.id FROM sma_sales WHERE sma_sales.id IN (select sale_id FROM sma_daily_sales_sheet WHERE sma_daily_sales_sheet.dss_no='$dss_no') ORDER BY sma_sales.id DESC")->result_array();

		$status = 'Approved';

//	$action = '<a class="btn btn-sm btn-primary" href="' . admin_url("sales/view_daily_sales_sheet/" . $r->dss_no.'/'. $r->date) . '" >
// <i class="fa fa-eye"></i> View </a>';


		$data1[] = array(
			"id" => $i++,
			"date" => date("d-m-Y", strtotime($r->date)),
			"dss_no" => $r->dss_no,
			"sp" => $r->first_name . $r->last_name,
			"inv_total" => $r->total,
			"spc" => number_format($r->spc, 2),
			"status" => $status,
//"action" => $action
		);
	}




	$result = [
		"draw" => intval($draw),
		"recordsTotal" =>count($totalRecords),
		"recordsFiltered" =>count($totalRecords),
		"data" => $data1
	];        
	echo json_encode($result);
	exit();	
}


public function sales_history() 
{
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = $str;
	$this->load->database($token);

	$start_date = $_POST['searchByFromdate'];
	$end_date = $_POST['searchByTodate'];

	$draw = intval($this->input->post("draw"));
	$start = intval($this->input->post("start"));
	$length = intval($this->input->post("length"));

	$search = $this->input->post('search');
	$searchValue = $search['value'];


	$columnFilters = array(
		0 => 'sma_sales.date',
		7 => 'sma_sales.route_number',
		8 => 'sma_sales.sale_status'
	);

	$columnIndex = intval($this->input->post('order')[0]['column']);
	$columnName = isset($columnFilters[$columnIndex]) ? $columnFilters[$columnIndex] : '';

	$dir = $this->input->post('order')[0]['dir'];

	$searchCondition = "";
	if (!empty($searchValue)) 
	{
		$searchCondition = " AND (sma_sales.reference_no LIKE '%$searchValue%' OR sma_sales.customer LIKE '%$searchValue%' OR sma_routes.route_number LIKE '%$searchValue%' OR sma_sales.sale_status LIKE '%$searchValue%')";
	}


	// if (!empty($columnName)) 
	// {
	// 	$searchCondition .= " AND $columnName LIKE '%$searchValue%'";
	// }

	if ($start_date != '' && $end_date != '')
	{
		$form_date = date("Y-m-d", strtotime($start_date));
		$to_date = date("Y-m-d", strtotime($end_date));

		$totalRecords = $this->db->query("SELECT sma_sales.id AS id FROM sma_sales
			LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id
			LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id
			WHERE sma_sales.is_delete = '0'
			$searchCondition
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') >= '$form_date'
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') <= '$to_date'
			ORDER BY $columnName $dir")->result_array();

		$data = $this->db->query("SELECT sma_sales.id AS id, sma_sales.date, sma_sales.reference_no, sma_sales.customer,
			sma_sales.customer_id, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_routes.route_number,sma_companies.accound_no
			FROM sma_sales
			LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id
			LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id
			WHERE  sma_sales.is_delete = '0'
			$searchCondition
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') >= '$form_date'
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') <= '$to_date'
			ORDER BY $columnName $dir
			LIMIT $start, $length")->result();
	} else {
		$form_date = date('Y-m-01');
		$to_date = date('Y-m-t');




		$totalRecords = $this->db->query("SELECT sma_sales.id AS id FROM sma_sales
			LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id
			LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id
			WHERE  sma_sales.is_delete = '0'
			$searchCondition 
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') >= '$form_date'
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') <= '$to_date' 
			ORDER BY $columnName $dir")->result_array();

		$data = $this->db->query("SELECT sma_sales.id AS id, sma_sales.date, sma_sales.reference_no, sma_sales.customer,
			sma_sales.customer_id, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_routes.route_number,sma_companies.accound_no
			FROM sma_sales
			LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id
			LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id
			WHERE
			sma_sales.is_delete = '0'
			$searchCondition  
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') >= '$form_date'
			AND DATE_FORMAT(sma_sales.date, '%Y-%m-%d') <= '$to_date'
			ORDER BY $columnName $dir
			LIMIT $start, $length")->result();
	}

	$total_grand_total = 0;
	$total_grand_total1 = 0;
	foreach ($totalRecords as $rdata) {
		$sale_id = $rdata['id'];

		$salesdata = $this->db->query("SELECT
			SUM(order_qty * unit_price) as grand_total,SUM(quantity * unit_price) as grand_total1,
			SUM(item_tax) as item_tax
			FROM
			`sma_sale_items`
			WHERE
			sale_id IN (SELECT id FROM sma_sales WHERE sma_sales.is_delete = '0' AND sma_sales.id = '$sale_id')")->result_array();

		$total_grand_total += $salesdata[0]['grand_total'];
		$total_grand_total1 += $salesdata[0]['grand_total1'];
	}

	$data1 = [];
	foreach ($data as $r) {
		$sale_id = $r->id;

		$salesdata = $this->db->query("SELECT
			SUM(order_qty * unit_price) as grand_total,SUM(quantity * unit_price) as grand_total1,
			SUM(item_tax) as item_tax
			FROM
			`sma_sale_items`
			WHERE
			sale_id IN (SELECT id FROM sma_sales WHERE sma_sales.is_delete = '0' AND sma_sales.id = '$sale_id')")->result_array();

		$grand_total = $salesdata[0]['grand_total'];
		$grand_total1 = $salesdata[0]['grand_total1'];

	//	$previous_due = get_total($r->customer_id);
	


	    $totalSale = total_sale_person_collected(@$results->customer_id);
		$totalDriver = total_driver_collected(@$results->customer_id);

		// Format the numbers to 2 decimal places
		$formattedTotalSale = number_format($totalSale, 2);
		$formattedTotalDriver = number_format($totalDriver, 2);

		// Calculate the total credit and format it
		$totalcredit = $totalSale + $totalDriver;
		$previous_due=number_format(get_total(@$results->customer_id)-@$totalcredit, 2, '.', '');


	    $total_sum = $previous_due + $grand_total;

		$total = $total_sum - $r->paid;

		$data1[] = array(
			"id" => $r->id,
			"date" => date('d-m-y', strtotime($r->date)),
			"reference_no" => $r->reference_no,
			"customer" => $r->customer . ' (' . $r->accound_no . ')',
			"grand_total" => number_format($grand_total, 2, '.', ''),
			"grand_total1" => number_format($grand_total1, 2, '.', ''),
			"previous_due" => $previous_due,
			"paid" => $r->paid,
			'total' => number_format($total, 2, '.', ''),
			"route_number" => $r->route_number,
			"sale_status" => $r->sale_status,
		);
	}


	$result = [
		"draw" => intval($draw),
		"recordsTotal" => count($totalRecords),
		"recordsFiltered" => count($totalRecords),
		"data" => $data1,
		'total_grand_total' => number_format($total_grand_total, 2),
		'total_grand_total1' => number_format($total_grand_total1, 2)
	];

	echo json_encode($result);
	exit();
}

public function add_orders_front_sheet()
{
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = $str;

	$this->load->database($token);

	if (!empty($_POST['val'])) 
	{
		foreach ($_POST['val'] as $id) 
		{

			$this->load->model('sales_model');
			$this->load->model('inv_model');
			$this->sales_model->states_update($id);
			$inv_items = $this->sales_model->getAllInvoiceItems($id);
			foreach ($inv_items as $item) 
			{
				$product_id = $item->product_id;
				$item_id = $item->id;
				$order_qty = $item->quantity;
				$unit_price = $item->unit_price;
				$order_type = $item->order_type;               
				$data['accept_qty'] = $order_qty;
				$data['accept_amt'] = $order_qty * $unit_price;
				$this->db->where('id', $item_id);
				$this->db->update('sma_sale_items', $data);              
				$this->inv_model->inv_update_product_stock('Accept',$product_id, $item_id, $order_qty,$order_type);
			}
		}

		$result = [
			"status" => 'true',
			"massage" => 'order add in front sheet',					
		];        
		echo json_encode($result);
	}
	else
	{
		$result = [
			"status" => 'false',
			"massage" => 'Please select order First',
		];        
		echo json_encode($result);
	}
}




}