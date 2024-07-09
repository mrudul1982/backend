<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");


class WebApiModuleDelivery extends CI_Controller {
	
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

public function get_manifest_list()
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
	$rowperpage=intval($this->input->post("length"));
	$columnIndex_arr = $this->input->post('order');
	$columnName_arr = $this->input->post('columns');
	$order_arr = $this->input->post('order');
	$search_arr = $this->input->post('search');
	$columnIndex = $columnIndex_arr[0]['column'];
	$columnName = @$columnName_arr[$columnIndex]['data'];
	$columnSortOrder = $order_arr[0]['dir'];
	$searchValue = $search_arr['value'];

	$totalRecords = $this->db->query("select model_name,sma_manifest.driver_id,sma_manifest.vehicle_id,sma_manifest.date,sma_manifest.route_date,sma_manifest.id,
		sma_manifest.route_number,sma_manifest.manifest_id,sma_users.first_name,sma_users.last_name
		from sma_manifest left join sma_vehicle on sma_vehicle.id=sma_manifest.vehicle_id 
		left join sma_users on sma_users.id=sma_manifest.driver_id where is_deleted='N' and intransit='Y' and is_manifest='Y' and delivered_status in('N','NUll') ORDER BY sma_manifest.id DESC")->result();

	$totalRecordswithFilter = $this->db->query("select model_name,sma_manifest.driver_id,sma_manifest.vehicle_id,sma_manifest.date,sma_manifest.route_date,sma_manifest.id,
		sma_manifest.route_number,sma_manifest.manifest_id,sma_users.first_name,sma_users.last_name
		from sma_manifest left join sma_vehicle on sma_vehicle.id=sma_manifest.vehicle_id 
		left join sma_users on sma_users.id=sma_manifest.driver_id where is_deleted='N' and intransit='Y' and is_manifest='Y' and delivered_status in('N','NUll') and (

		sma_manifest.date LIKE '%" . $searchValue . "%'					
		) 
		ORDER BY sma_manifest.id DESC")->result();

	$records = $this->db->query("select model_name,sma_manifest.driver_id,sma_manifest.vehicle_id,sma_manifest.date,sma_manifest.route_date,sma_manifest.id,
		sma_manifest.route_number,sma_manifest.manifest_id,sma_users.first_name,sma_users.last_name,sma_trip.trip_number
		from sma_manifest left join sma_vehicle on sma_vehicle.id = sma_manifest.vehicle_id  
		left join sma_users on sma_users.id = sma_manifest.driver_id left join 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id left join 
		sma_trip ON sma_trip.id = sma_manifest_trip.trip_id where is_deleted='N' and intransit='Y' and is_manifest='Y' and delivered_status in('N','NUll')  and (
		sma_manifest.date LIKE '%" . $searchValue . "%'	
	) ORDER BY sma_manifest.id DESC LIMIT $start, $rowperpage")->result();



	$i = 1;
	foreach ($records as $r) 
	{
		$date =  date("d-m-Y", strtotime($r->date));
		$vehicle = get_vehicle($r->vehicle_id);
		$driver = get_driver($r->driver_id);
		$status = 'Intransit';
		$action = '<a class="btn btn-primary" href="' . base_url('delivery/manifest_list/mark_delivery/' . $r->manifest_id) . '" id="mark_deliver">  <i class="fa fa-check"></i> Mark Deliver</a>';



		$data1[] = array(
			"id"=> $i,    
			"date"=>$date,
			"manifest_id"=> $r->manifest_id,
			"vehicle"=> $vehicle,
			"trip_id"=> $r->trip_number,
			"driver"=> $driver,
			"status"=>  $status,
			"action"=>  $action
		);

		$i = $i + 1;
	}



	$response = array(
		"draw" => intval($draw),
		"recordsTotal" =>  count($totalRecords),
		"recordsFiltered" => count($totalRecordswithFilter),
		"data" => $data1
	);        
	echo json_encode($response);
}

public function deliveredlistshow() {


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
	$columnName = @$columnName_arr[$columnIndex]['data']; 
	
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 
	

	$totalRecords = $this->db->query("select * from sma_manifest where intransit='Y' and is_deleted='N' and delivered_status='Y' ORDER BY sma_manifest.id DESC")->result();  

	$totalRecordswithFilter = $this->db->query("select * from sma_manifest where intransit='Y' and is_deleted='N' and delivered_status='Y'  AND (
		sma_manifest.manifest_id LIKE '%" . $searchValue . "%' 
		OR sma_manifest.date LIKE '%" . $searchValue . "%') ORDER BY sma_manifest.id DESC")->result(); 

	$records = $this->db->query("select * from sma_manifest where intransit='Y' and is_deleted='N' and delivered_status='Y'  AND (
		sma_manifest.manifest_id LIKE '%" . $searchValue . "%' 
		OR sma_manifest.date LIKE '%" . $searchValue . "%') ORDER BY sma_manifest.id DESC LIMIT $start, $rowperpage")->result(); 
	$i = 0;
	foreach ($records as $r) 
	{
		$i += 1;
		$date = date("d-m-Y", strtotime($r->date));
		$manifest_id = '<a href="admin/delivery/manifest_list/view_manifest/' . $r->id . '">' . $r->manifest_id . '</a>';
		$vehicle = get_vehicle($r->vehicle_id);
		$driver = get_driver($r->driver_id);
		$status = '<span>Delivered</span>';
		$action = '<a class="btn btn-primary btn-sm" href="isConfirm/' . $r->manifest_id . '"><i class="fa fa-check"></i> Is Confirm</a>';


		$data1[] = array(
			"id"=> $i,    
			"date"=> $date,
			"manifest_id"=> $manifest_id,
			"vehicle"=> $vehicle,
			"driver"=> $driver,
			"status"=>  $status,
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

public function undeliveredlistshow() 
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
	$columnName = @$columnName_arr[$columnIndex]['data']; 
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 

	$totalRecords = $this->db->query("select sma_sales.id as id,
		sma_sales.date, sma_sales.reference_no,	sma_sales.receive_amount,
		sma_sales.manifest_id,sma_sales.invoice_date,sma_sales.deliverydate,
		sma_sales.undelivered_reson,sma_sales.customer,	sma_sales.customer_id,
		sma_sales.payment_method,sma_sales.sale_status,	sma_sales.grand_total,
		sma_sales.paid,	sma_sales.grand_total-paid as balance,sma_sales.payment_status,
		sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code,
		sma_sales.return_id, sma_routes.route_number,sma_sales.picklist_number 
		from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.sale_status ='Undelivered' ORDER BY sma_sales.id DESC")->result(); 

	$query33 = "select sma_sales.id AS id, sma_sales.date,sma_sales.reference_no,
	sma_sales.receive_amount,sma_sales.manifest_id,sma_sales.invoice_date,
	sma_sales.deliverydate, sma_sales.undelivered_reson,sma_sales.customer,
	sma_sales.customer_id,sma_sales.payment_method,sma_sales.sale_status,
	sma_sales.grand_total, sma_sales.paid,sma_sales.grand_total - paid AS balance,
	sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,
	sma_companies.postal_code,sma_sales.return_id,sma_routes.route_number,
	sma_sales.picklist_number  FROM sma_sales
	LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id
	LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id
	WHERE sma_sales.sale_status = 'Undelivered'
	AND (
	sma_sales.receive_amount LIKE ? 
	OR sma_sales.reference_no LIKE ?
	OR sma_companies.accound_no LIKE ?
	OR sma_sales.customer LIKE ?
	OR sma_sales.manifest_id LIKE ?
	)
	ORDER BY sma_sales.id DESC";

	$searchPattern = '%' . $searchValue . '%';

	$totalRecordswithFilter = $this->db->query($query33, array($searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern))->result();

	$this->db->limit($rowperpage, $start);
	$records = $this->db->query($query33, array($searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern))->result();


	$i = 0;
	foreach ($records as $r) 
	{
		$i +=1;
		$date = date('Y-m-d', strtotime($r->invoice_date));
		$invoice_id = 'INV'.$r->reference_no;
		$invoice_amount = @$r->grand_total - @$r->total_discount;
		$sales_rep = sale_person_collected(@$r->created_by, @$r->customer_id);
		$data1[] = array(
			"id"=> $i,    
			"date"=> $date,
			//	"accound_no"=> $r->accound_no,
			"customer"=> $r->customer . "(".$r->accound_no.")",
			"invoice_id"=> $invoice_id,
			"manifest_id"=>  $r->manifest_id,
			"invoice_amount"=>	$invoice_amount,
			"sales_rep"=> $sales_rep ? $sales_rep : '0.00',
			"undelivered_reson"=> $r->undelivered_reson,
			"sale_status"=> $r->sale_status
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
public function get_mark_delivery()
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

	$manifest_id = $id_new;


	$draw=intval($this->input->post("draw"));
	$start=intval($this->input->post("start"));
	$rowperpage=intval($this->input->post("length"));

	$columnIndex_arr = $this->input->get('order');
	$columnName_arr = $this->input->get('columns');
	$order_arr = $this->input->get('order');

	$search_arr = $this->input->get('search');
	$columnIndex = $columnIndex_arr[0]['column'];
	$columnName = $columnName_arr[$columnIndex]['data'];

	$columnSortOrder = $order_arr[0]['dir'];
	$searchValue = $search_arr['value'];


	$data['manifast'] = $this->db->query("select * from sma_manifest where manifest_id='$manifest_id'")->row_array();
	$da = $data['manifast']['print_id'];
	if ($da != '') 
	{
		$totalRecords = $this->db->query("select sma_sales.id,sma_sales.created_by,sma_sales.is_confirm,sma_sales.receive_amount,sma_sales.id as id,sma_sales.total_discount, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_sales.return_id, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da)and sma_sales.sale_status in('Intransit') ORDER BY sma_sales.id DESC")->result();


		$totalRecordswithFilter = $this->db->query("select sma_sales.id,sma_sales.created_by,sma_sales.is_confirm,sma_sales.receive_amount,sma_sales.id as id,sma_sales.total_discount, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_sales.return_id, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da)and sma_sales.sale_status in('Intransit') and (
			sma_sales.created_by LIKE '%" . $searchValue . "%'
		)  ORDER BY sma_sales.id DESC")->result();



		$records = $this->db->query("select sma_sales.id,sma_sales.created_by,sma_sales.is_confirm,sma_sales.receive_amount,sma_sales.id as id,sma_sales.total_discount, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_sales.return_id, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da)and sma_sales.sale_status in('Intransit') and (
			sma_sales.created_by LIKE '%" . $searchValue . "%'
		)  ORDER BY sma_sales.id DESC LIMIT $start, $rowperpage")->result();
		$id1 = $manifest_id;
	} else 
	{
		$totalRecords = '';
		$totalRecordswithFilter = '';
		$records = '';
		$id1 = $manifest_id;
	}

	$manifest = $this->db->query("select * from sma_manifest where manifest_id='$manifest_id'")->row_array();


	$sales_person=0;
	$driver_collect=0;

	foreach ($records as $r) 
	{

		$check = '<input type="checkbox" required="required" class="checkbox multi-select input-xs" name="val[]" value="'.$r->id.'" />';

		if ($r->invoice_date != '') 
		{
			$date =  date('Y-m-d', strtotime($r->invoice_date));
		}

		$invoice_num = $r->reference_no;

		$customer = $r->customer . '(' . $r->accound_no . ')';

		$invoice_amt =  number_format(($r->grand_total) - ($r->total_discount), 2);

		$sales_person_collected = number_format(sale_person_collected($r->created_by, $r->id), 2);

		$sales_person += number_format(sale_person_collected($r->created_by, $r->id), 2);

		$previous_dues = number_format(get_total($r->customer_id), 2) - number_format(total_sale_person_collected($r->customer_id), 2) - number_format(total_driver_collected($r->customer_id), 2);




		$action = '';

		if ($r->is_confirm == 'N') 
		{
			



			$action .= '<a class="btn btn-primary btn-sm" style="margin-bottom:5px" href="'.admin_url('delivery/edit_invoice/' . $r->id . '/' . $id1) . '"><i class="fa fa-check">Confirm Delivery</i></a>';

			$action .= '<a href="' . admin_url('delivery/update_delivered_status/' . $r->id) . '" class="btn btn-danger btn-sm" style="margin-bottom:5px;margin-left: 10px;" data-toggle="modal" data-target="#myModal"><i class="fa fa-remove"></i> Undelivered</a>';


			$action .='<a target="_blank" class="btn btn-primary btn-sm" href="'.admin_url('sales/pdf/' . $r->id).'"> <i class="fa fa-print"> Print</i></a>';  
		}

		else

		{
			$action .='<a class="btn btn-primary btn-sm" style="margin-bottom: 5px; margin-right: 5px;"> <i class="fa fa-check">Confirmed</i></a>';

			$action .='<a href="'.admin_url('delivery/update_delivered_status/' .$r->id).'" class="btn btn-danger btn-sm"  data-toggle="modal" data-target="#myModal"> <i class="fa fa-remove">  Undelivered</i></a>';

			$action .='<a target="_blank" class="btn btn-primary btn-sm" href="'.admin_url('sales/pdf/' . $r->id).'"> <i class="fa fa-print"> Print</i></a>';  
                  // </td>

		}


		$data1[] = array(
			"id"=>  $r->id,  
			"manifest_id"=> $manifest_id, 
			"check"=> $check,    
			"date"=> $date,
			"invoice_num"=> $invoice_num,
			"customer"=> $customer,
			"invoice_amt"=> $invoice_amt,
			"sales_person_collected"=>  $sales_person_collected,
			"previous_dues"=>  number_format($previous_dues, 2),
			"is_confirm"=>$r->is_confirm,
			"action"=>  $action								
		);

		$data2[] = array(
			"manifest"=> $manifest,
			'id1' =>  $id1,
			'sales_person' => $sales_person
		);
	}


	$response = array(
		"draw" => intval($draw),
		"recordsTotal" =>  count($totalRecords),
		"recordsFiltered" => count($totalRecordswithFilter),
		"data" => $data1,
		"data2" =>$data2
	);        
	echo json_encode($response);
}

public function get_isConfirm_data()
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
	
	$id = $_POST['manifest_id'];
	$draw=intval($this->input->post("draw"));
	$start=intval($this->input->post("start"));
	$length=intval($this->input->post("length"));
	$columnIndex_arr = $this->input->get('order');
	$columnName_arr = $this->input->get('columns');
	$order_arr = $this->input->get('order');
	$search_arr = $this->input->get('search');
	$columnIndex = $columnIndex_arr[0]['column']; 
	$columnName = $columnName_arr[$columnIndex]['data']; 
	$columnSortOrder = $order_arr[0]['dir']; 
	$searchValue = $search_arr['value']; 


	$data['manifast'] = $this->db->query("select * from sma_manifest where manifest_id='$id'")->row_array();
	$da = $data['manifast']['print_id'];
	if ($da != '') 
	{
		$query1 = $this->db->query("select sma_sales.created_by,sma_sales.is_confirm,sma_sales.receive_amount,sma_sales.id as id,sma_sales.total_discount, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total,sma_sales.paid,sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_sales.return_id, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da) and sma_sales.sale_status in('Delivered') ORDER BY sma_sales.id DESC")->result();


		$data = $this->db->query("select sma_sales.created_by,sma_sales.is_confirm,sma_sales.receive_amount,sma_sales.id as id,sma_sales.total_discount, sma_sales.date, sma_sales.reference_no, sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total,sma_sales.paid,sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_sales.return_id, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da) and sma_sales.sale_status in('Delivered') ORDER BY sma_sales.id DESC limit $start, $length")->result();

		$manifest_id = $id;

	}
	else
	{
		$data = '';
		$manifest_id = $id;
	}

	$get_manifest = $this->db->query("select * from sma_manifest where manifest_id='$id'")->row_array();


	$i = 1;
	$sales_person=0;
	$driver_collect=0;
	foreach ($data as $r) 
	{
		
		$check = '<input type="checkbox" required="required" class="checkbox multi-select input-xs" name="val[]" value="'.$r->id.'" />';

		if ($r->invoice_date != '') 
		{
			$date =  date('Y-m-d', strtotime($r->invoice_date));
		}

		$invoice_number = '<a target="_blank" href="admin/sales/pdf/' . $r->id . '">INV' . $r->reference_no . '</a>';
		$customer = $r->customer . '(' . $r->accound_no . ')';

		$invoice_amt = number_format($r->grand_total - $r->total_discount, 2);

		$sales_person_amt = number_format(sale_person_collected($r->created_by, $r->id), 2);

		$sales_person += number_format(sale_person_collected($r->created_by, $r->id), 2);

		$sales_status = number_format(get_total($r->customer_id), 2) - number_format(total_sale_person_collected($r->customer_id), 2) - number_format(total_driver_collected($r->customer_id), 2);

		if ($r->is_confirm == 'N') {
			$action = '<a class="btn btn-primary btn-sm" style="margin-bottom:5px" href="admin/delivery/edit_delivered_invoice/' . $r->id . '/' . $manifest_id . '">  <i class="fa fa-check"></i> Confirm Delivery</a>';
		} else {
			$action = '<a class="btn btn-primary btn-sm"> <i class="fa fa-check">Confirmed</i></a>';
		}


		$data1[] = array(
			"check"=> $check,    
			"date"=> $date,
			"invoice_number"=> $invoice_number,
			"customer"=> $customer,
			"invoice_amt"=> $invoice_amt,
			"sales_person_amt"=>  $sales_person_amt,
			"sales_status"=>  $sales_status,
			"action"=>  $action
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

public function get_vehicles_web()
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

	$this->db->select('id as vehicleId,model_name as modelName,registration_number as registrationNo');
	$data['vehicle'] = $this->db->get('sma_vehicle')->result();

	$data['manifast'] = $this->db->query("select vehicle_id from sma_manifest where manifest_id='$manifest_id'")->row_array();

	if(count($data) != 0)
	{
		$response_arr = array(
			'data' => $data,
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