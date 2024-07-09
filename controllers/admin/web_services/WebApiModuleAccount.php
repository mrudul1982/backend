<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModuleAccount extends CI_Controller {
	
	public function __construct() {
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




public function get_invoice() 
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

	$draw=intval($this->input->get("draw"));
	$start=intval($this->input->get("start"));
	$rowperpage=intval($this->input->get("length"));
	$columnIndex_arr = $this->input->get('order');
	$columnName_arr = $this->input->get('columns');
	$order_arr = $this->input->get('order');
	$search_arr = $this->input->get('search');
	$columnIndex = $columnIndex_arr[0]['column'];
	$columnName = $columnName_arr[$columnIndex]['data']; 
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 


	$totalRecords = $this->db->query("select invoice_date,  id,customer,grand_total,total_discount,reference_no from sma_sales where delivery_status='Y' and sale_status='Delivered' and is_confirm='Y' and push_invoice='N' order by id DESC")->result();   

	$query = "select    invoice_date,  id,customer,grand_total,total_discount,reference_no from sma_sales where delivery_status='Y' and sale_status='Delivered' and is_confirm='Y' and push_invoice='N' AND (
	sma_sales.customer LIKE ? 
	OR sma_sales.reference_no LIKE ?
) order by id DESC"; 


$searchPattern = '%' . $searchValue . '%';

$totalRecordswithFilter = $this->db->query($query, array($searchPattern, $searchPattern))->result();

$this->db->limit($rowperpage, $start);
$records = $this->db->query($query, array($searchPattern, $searchPattern))->result();


foreach ($records as $r) 
{
	$id = '<input type="checkbox" class="checkbox multi-select input-xs" name="val[]" value="' . $r->id . '" />';
	$invoice_number ='<a target="_blank" href="'. base_url('admin/sales/pdf/' . $r->id) . '">  INV'. $r->reference_no .'</a>';
	$invoice_amount = $r->grand_total - $r->total_discount;
	$action = '<a class="btn btn-primary" href="' . base_url('admin/delivery/push_invoice/' . $r->id) . '">
	<i class="fa fa-check"></i> Push IN Sage
	</a>';

	$data1[] = array(
		"id"=> $id,  
		"invoice_date"=> $r->invoice_date,
		"invoice_number"=> $invoice_number,
		"customer"=> $r->customer,
		"invoice_amount"=> $invoice_amount, 
		"action"=> $action 
	);
}

$response = array(
	"draw" => intval($draw),
	"recordsTotal" =>  count($totalRecords),
	"recordsFiltered" => count($totalRecordswithFilter),
	"data" => $data1
);        
echo json_encode($response);

}
public function all_push_invoice()
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

	$draw=intval($this->input->get("draw"));
	$start=intval($this->input->get("start"));
	$rowperpage=intval($this->input->get("length"));
	$columnIndex_arr = $this->input->get('order');
	$columnName_arr = $this->input->get('columns');
	$order_arr = $this->input->get('order');
	$search_arr = $this->input->get('search');
	$columnIndex = $columnIndex_arr[0]['column'];
	$columnName = $columnName_arr[$columnIndex]['data']; 
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 



	$totalRecords = $this->db->query("select invoice_date,id,customer,grand_total,reference_no from sma_sales where delivery_status='Y' and sale_status='Delivered' and push_invoice='Y' order by id desc")->result();

	$totalRecordswithFilter = $this->db->query("
		select s.invoice_date, s.id, s.customer, s.grand_total, s.reference_no
		FROM sma_sales s
		JOIN sma_companies c ON s.customer_id = c.id
		LEFT JOIN sma_driver_collected_amount d ON s.id = d.sales_id
		WHERE s.delivery_status = 'Y' AND s.sale_status = 'Delivered' AND s.push_invoice = 'Y' AND (
		c.name LIKE '%" . $searchValue . "%' 
		OR c.accound_no LIKE '%" . $searchValue . "%' 
		OR s.customer LIKE '%" . $searchValue . "%'
		OR s.reference_no LIKE '%" . $searchValue . "%'
	) ORDER BY s.id DESC")->result();

	$records =  $this->db->query("
		select s.invoice_date, s.id, s.customer, s.grand_total, s.reference_no
		FROM sma_sales s
		JOIN sma_companies c ON s.customer_id = c.id
		LEFT JOIN sma_driver_collected_amount d ON s.id = d.sales_id
		WHERE s.delivery_status = 'Y' AND s.sale_status = 'Delivered' AND s.push_invoice = 'Y' AND (
		c.name LIKE '%" . $searchValue . "%' 
		OR s.customer LIKE '%" . $searchValue . "%' 
		OR s.reference_no LIKE '%" . $searchValue . "%'
	) ORDER BY s.id DESC LIMIT $start, $rowperpage")->result();



	foreach ($records as $r) 
	{
		$checkbox = '<input type="checkbox" class="checkbox multi-select input-xs" name="val[]" value="' . $r->id . '" />';

		//$invoice_number = '<a target="_blank" href="admin/sales/pdf/' . $r->id . '">' . 'INV' . $r->reference_no . '</a>';
		$invoice_amount = $r->grand_total - $r->total_discount;

		$data1[] = array(
			"checkbox" => $checkbox,
			"invoice_date" => $r->invoice_date,
			"invoice_number" =>'INV' . $r->reference_no,
			"customer" => $r->customer,
			"invoice_amount" => $invoice_amount,
			"id" =>$r->id,
		);
	}

	$response = array(
		"draw" => intval($draw),
		"recordsTotal" =>  count($totalRecords),
		"recordsFiltered" => count($totalRecordswithFilter),
		"data" => $data1
	);        
	echo json_encode($response);

}

public function payment_received()
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
	
	$draw=intval($this->input->get("draw"));
	$start=intval($this->input->get("start"));
	$rowperpage=intval($this->input->get("length"));
	$columnIndex_arr = $this->input->get('order');
	$columnName_arr = $this->input->get('columns');
	$order_arr = $this->input->get('order');
	$search_arr = $this->input->get('search');
	$columnIndex = $columnIndex_arr[0]['column'];
	$columnName = $columnName_arr[$columnIndex]['data']; 
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 


	$totalRecords = $this->db->query("select is_assign,sma_driver_collected_amount.id,sma_driver_collected_amount.created_at,sma_companies.name,sma_companies.accound_no,sma_companies.route,sales_person_id,driver_id,amount,allocated_amount,customer_id,payment_mode from sma_driver_collected_amount LEFT JOIN sma_companies ON sma_companies.id = sma_driver_collected_amount.customer_id where is_assign='N' and front_sheet_id='0'")->result();


	$totalRecordswithFilter = $this->db->query("select is_assign,sma_driver_collected_amount.id,sma_driver_collected_amount.created_at,sma_companies.name,sma_companies.accound_no,sma_companies.route,sales_person_id,driver_id,amount,allocated_amount,customer_id,payment_mode from sma_driver_collected_amount LEFT JOIN sma_companies ON sma_companies.id = sma_driver_collected_amount.customer_id where is_assign='N' and front_sheet_id='0' and (sma_companies.name LIKE '%" . $searchValue . "%' 
		OR sma_companies.accound_no LIKE '%" . $searchValue . "%' 
		OR sma_driver_collected_amount.amount LIKE '%" . $searchValue . "%'  
		OR sma_driver_collected_amount.payment_mode LIKE '%" . $searchValue . "%')")->result();


	$records = $this->db->query("select is_assign,sma_driver_collected_amount.id,sma_driver_collected_amount.created_at,sma_companies.name,sma_companies.accound_no,sma_companies.route,sales_person_id,driver_id,amount,allocated_amount,customer_id,payment_mode from sma_driver_collected_amount LEFT JOIN sma_companies ON sma_companies.id = sma_driver_collected_amount.customer_id where is_assign='N' and front_sheet_id='0' and (
		sma_companies.name LIKE '%" . $searchValue . "%' 
		OR sma_companies.accound_no LIKE '%" . $searchValue . "%' 
		OR sma_driver_collected_amount.amount LIKE '%" . $searchValue . "%'
		OR sma_driver_collected_amount.payment_mode LIKE '%" . $searchValue . "%') LIMIT $start, $rowperpage")->result();

	foreach ($records as $r) 
	{
		$id = '<input type="checkbox" class="checkbox multi-select input-xs" name="val[]" value="' . $r->id . '" />';
		$date = date('d-m-Y', strtotime($r->created_at));
		$customer = $r->name .' ' . $r->accound_no ;
		$route = get_route($r->route);
		if ($r->sales_person_id != 0){
			$collected_by = get_createdby($r->sales_person_id);
		}
		else{
			$collected_by = get_driver($r->driver_id);
		}
		$data1[] = array(
			"id"=> $id,  
			"date"=> $date,
			"customer"=> $customer,
			"route"=> $route,
			"collected_by"=> $collected_by, 
			"mode"=> $r->payment_mode,
			"amount"=> $r->amount, 
		);
	}

	$response = array(
		"draw" => intval($draw),
		"recordsTotal" =>  count($totalRecords),
		"recordsFiltered" => count($totalRecordswithFilter),
		"data" => $data1
	);        
	echo json_encode($response);

}

}