<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");


class WebApiModuleProformaInvoice extends CI_Controller {
	
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

	public function picking_proforma()
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

		$start_date = $_POST['searchByFromdate']; 
		$end_date = $_POST['searchByTodate'];

		$draw=intval($this->input->post("draw"));
		$start=intval($this->input->post("start"));
		$length=intval($this->input->post("length"));

		$columnIndex_arr = $this->input->get('order');
		$columnName_arr = $this->input->post('columns');
		$order_arr = $this->input->post('order');
		$search_arr = $this->input->post('search');

		$columnIndex = $columnIndex_arr[0]['column']; 
		$columnName = $columnName_arr[$columnIndex]['data']; 
		$columnSortOrder = $order_arr[0]['dir']; 
		$searchValue = $search_arr['value'];


		if ($start_date != '' && $end_date != '') 
		{
			$from_date = date("Y-m-d", strtotime($start_date));
			$to_date =  date("Y-m-d", strtotime($end_date));

			$query1 = $this->db->query("select sma_sales.id as id from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.sale_status in ('Invoiced','Canceled')  and DATE_FORMAT(sma_sales.invoice_date,'%Y-%m-%d') >='$from_date' and DATE_FORMAT(sma_sales.invoice_date,'%Y-%m-%d')<= '$to_date'  and (
				sma_sales.customer LIKE '%" . $searchValue . "%' 
				OR sma_sales.reference_no LIKE '%" . $searchValue . "%'
				OR sma_sales.invoice_date LIKE '%" . $searchValue . "%'
				OR sma_companies.accound_no LIKE '%" . $searchValue . "%')  ORDER BY sma_sales.id DESC")->result();

			$data = $this->db->query("select sma_sales.manifest_print,sma_sales.delivery_status, sma_sales.id as id, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_sales.updated_at, sma_sales.return_id,sma_routes.route_number,sma_companies.accound_no from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.sale_status in ('Invoiced','Canceled')  and DATE_FORMAT(sma_sales.invoice_date,'%Y-%m-%d') >='$from_date' and DATE_FORMAT(sma_sales.invoice_date,'%Y-%m-%d')<= '$to_date'  and (
				sma_sales.customer LIKE '%" . $searchValue . "%' 
				OR sma_sales.reference_no LIKE '%" . $searchValue . "%'
				OR sma_sales.invoice_date LIKE '%" . $searchValue . "%'
				OR sma_companies.accound_no LIKE '%" . $searchValue . "%')  ORDER BY sma_sales.id DESC limit $start, $length")->result();
			
		}
		else
		{
			$query1 = $this->db->query("select sma_sales.id as id from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.sale_status in ('Invoiced','Canceled') and (
				sma_sales.customer LIKE '%" . $searchValue . "%' 
				OR sma_sales.reference_no LIKE '%" . $searchValue . "%'
				OR sma_sales.invoice_date LIKE '%" . $searchValue . "%'
				OR sma_companies.accound_no LIKE '%" . $searchValue . "%') ORDER BY sma_sales.id DESC")->result();

			$data = $this->db->query("select sma_sales.manifest_print,sma_sales.delivery_status, sma_sales.id as id, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_sales.updated_at, sma_sales.return_id,sma_routes.route_number,sma_companies.accound_no from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.sale_status in ('Invoiced','Canceled') and (
				sma_sales.customer LIKE '%" . $searchValue . "%' 
				OR sma_sales.reference_no LIKE '%" . $searchValue . "%'
				OR sma_sales.invoice_date LIKE '%" . $searchValue . "%'
				OR sma_companies.accound_no LIKE '%" . $searchValue . "%') ORDER BY sma_sales.id DESC limit $start, $length")->result();
		}

		$data1=array();
		foreach ($data as $r) 
		{
			
			$dateTime = new DateTime($r->invoice_date);
			$invoice_date = $dateTime->format('d-m-Y');
			$dateTime1 = new DateTime($r->deliverydate);
			$deliverydate = $dateTime1->format('d-m-Y');
			
			$customer =  $r->customer.' ('. $r->accound_no.')';
			

			$data1[] = array(
				"id"=> $r->id,    
				"invoice_date"=> $invoice_date,
				"reference_no"=> $r->reference_no,
				"customer"=> $customer,
				"route_number"=> $r->route_number,
				"deliverydate"=>  $deliverydate,
				"sale_status"=>  $r->sale_status,
				"action"=>  $r->id
			);
		}

		$result = [
			"draw" => intval($draw),
			"recordsTotal" => count($query1),
			"recordsFiltered" => count($query1),
			"data" => $data1
		];    

		echo json_encode($result);
		exit();
	}
}