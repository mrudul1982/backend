<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


class WebApiModulePicking extends CI_Controller {
	
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

	public function picking_print_list()
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

		$order = $this->input->post('order');
		$search = $this->input->post('search');

		$columnIndex = @$order[0]['column'];
		$columnName = @$this->input->post('columns')[$columnIndex]['data'];
		$columnSortOrder = $order[0]['dir'];
		$searchValue = $search['value'];
		$selectClause = "sma_sales.id as id, sma_sales.date, sma_sales.reference_no, sma_users.first_name, sma_users.last_name, sma_sales.deliverydate, sma_sales.customer, sma_sales.customer_id, sma_sales.sale_status, sma_sales.payment_method, sma_sales.grand_total,sma_companies.accound_no, sma_sales.paid, (sma_sales.grand_total - sma_sales.paid) as balance, sma_sales.print_by, sma_sales.print_on, sma_routes.route_number";


		$joinClause = "LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id";


		$whereClause = "sma_sales.sale_status ='Accept' AND picker_id='0' AND sma_sales.is_delete='0'";

		if (!empty($columnName) && !empty($columnSortOrder) && $columnName != 'id') {
			$orderByClause = "ORDER BY $columnName $columnSortOrder";
		} else {
			$orderByClause = "ORDER BY sma_sales.id DESC";
		}
		

		if ($start_date != '' && $end_date != '') {
			$from_date = date("Y-m-d", strtotime($start_date));
			$to_date = date("Y-m-d", strtotime($end_date));


			$query1 = $this->db->query("select $selectClause FROM sma_sales $joinClause WHERE $whereClause AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') >= '$from_date' AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') <= '$to_date' $orderByClause")->result();


			$data = $this->db->query("select $selectClause FROM sma_sales $joinClause WHERE $whereClause AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') >= '$from_date' AND DATE_FORMAT(sma_sales.date,'%Y-%m-%d') <= '$to_date' AND (
				sma_sales.reference_no LIKE '%$searchValue%'
				OR sma_users.first_name LIKE '%$searchValue%'
				OR sma_sales.customer LIKE '%$searchValue%'
				OR sma_sales.deliverydate LIKE '%$searchValue%'
				OR sma_routes.route_number LIKE '%$searchValue%'
			)  $orderByClause LIMIT $start, $length")->result();
		} else {

			$query1 = $this->db->query("select $selectClause FROM sma_sales $joinClause WHERE $whereClause  $orderByClause")->result();
			$data = $this->db->query("select $selectClause FROM sma_sales $joinClause WHERE $whereClause AND (
				sma_sales.reference_no LIKE '%$searchValue%'
				OR sma_users.first_name LIKE '%$searchValue%'
				OR sma_sales.customer LIKE '%$searchValue%'
				OR sma_sales.deliverydate LIKE '%$searchValue%'
				OR sma_routes.route_number LIKE '%$searchValue%'
			) $orderByClause LIMIT $start, $length")->result();
		}

		$recordsFiltered = ($searchValue == '') ? $query1 : $data;

		$data1 = array();
		foreach ($data as $r) {
			
			$name = $r->first_name . ' ' . $r->last_name;
			$date = new DateTime($r->date);
			$formattedDate = $date->format('d-m-Y');
			if ($r->deliverydate != '0000-00-00' && $r->deliverydate != '1970-01-01') {
				try {
					$date1 = new DateTime($r->deliverydate);
					$deliverydate = $date1->format('d-m-Y');
				} catch (Exception $e) {
					$deliverydate ='';
				}
			} else {
				$deliverydate ='';
			}

			$data1[] = array(
				"id" => $r->id,
				"date" => $formattedDate,
				"reference_no" => $r->reference_no,
				"customer" => $r->customer .' ('.$r->accound_no.')',
				"route_number" => $r->route_number,
				"first_name" => $name,
				"deliverydate" => $deliverydate
			);
		}

		$result = array(
			"draw" => $draw,
			"recordsTotal" => count($query1),
			"recordsFiltered" => count($recordsFiltered),
			"data" => $data1
		);

		echo json_encode($result);
	}


	public function get_picking_history() {


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

		$columnIndex = @$columnIndex_arr[0]['column']; 
		$columnName = @$columnName_arr[$columnIndex]['data']; 
		$columnSortOrder = $order_arr[0]['dir'];
		$searchValue = $search_arr['value'];

		$totalRecords = $this->db->query("
			select picklist_number,first_name,last_name,print_on,picking_status from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where sma_sales.picklist_number!='' and picking_status='done' group by sma_sales.picklist_number ORDER BY picklist_number DESC")->result();


		$records =  $this->db->query("
			select picklist_number,first_name,last_name,print_on,picking_status from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where sma_sales.picklist_number!='' and picking_status='done' and (picking_status LIKE '%" . $searchValue . "%' 
			OR picklist_number LIKE '%" . $searchValue . "%' OR first_name LIKE '%" . $searchValue . "%' OR last_name LIKE '%" . $searchValue . "%' 
		) group by sma_sales.picklist_number ORDER BY picklist_number DESC LIMIT $start, $rowperpage")->result();
		if ($searchValue=='') {
			$totalRecordswithFilter=$totalRecords;
		}else{
			$totalRecordswithFilter=$records;
		}
		foreach ($records as $r) 
		{
			$picklist_number = $r->picklist_number;
			$picklist_numbers_query = $this->db->query("select sma_routes.route_name FROM `sma_sales` LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id LEFT JOIN sma_routes ON sma_routes.id = sma_companies.route WHERE picklist_number = '$picklist_number' GROUP BY sma_routes.route_name");

			$picklist_numbers_result = $picklist_numbers_query->result_array();

			$routes = array_column($picklist_numbers_result, 'route_name');

			$tags = rtrim(implode(', ', $routes));


			$data1[] = array(
				"picklist_number"=> $r->picklist_number,    
				"route"=> $tags,
				"picker"=> $r->first_name.''.$r->last_name,
				"picklist_date"=>  date('d-m-Y', strtotime($r->print_on)),
				"picking_status"=> ucfirst($r->picking_status),
				"action"=>  $picklist_number
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


	public function getPicker()
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

		$data['picker'] =$this->db->query("select sma_users.id,sma_users.first_name,sma_users.last_name, sma_groups.id as role_id,sma_groups.name from sma_role_assign left join sma_users on sma_role_assign.user_id=sma_users.id left join sma_groups on sma_groups.id=sma_role_assign.role_id WHERE sma_groups.id='15' and sma_users.id!='' 
			UNION
			SELECT sma_users.id,sma_users.first_name,sma_users.last_name, sma_groups.id as role_id,sma_groups.name  FROM `sma_users` left join sma_groups on sma_groups.id= sma_users.group_id WHERE group_id='15' and sma_users.id!=''")->result();
		echo json_encode($data);
	}
	public function create_picking()
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

		$sales_id=$_POST['val'];$picker_id=$_POST['picker'];

		$picklist = $this->db->query("select picklist_number from sma_sales where picklist_number!='' group by picklist_number order by  picklist_number desc limit 1")->result_array();

		if (count($picklist) == 0) {
			$picklist_number = 'AAA0001';
		} else {
			$picklist_number = $picklist[0]['picklist_number'];
		}
		$picklist_number = ++$picklist_number;
		$data = [
			'print' => 'Y',
			'print_on' => date('Y-m-d H:i:s'),
			'print_by' => $this->session->userdata('username'),
			'picker_id' => $picker_id,
			'picklist_number' => $picklist_number,
		];
		if (count($sales_id) != 0) {
			$this->db->where_in('id', $sales_id);
			$this->db->update('sales', $data);		
			
			$url=base_url("admin/sales/view_picking_list/" . $picklist_number);
			$result = [
				"picklist_number"=>$url,
				"status" => 'true',
				"massage" => 'picking successfully created',					
			];        
			echo json_encode($result);
		} else {
			$result = [
				"picklist_number"=>'0',
				"status" => 'false',
				"massage" => 'please select order first',					
			];        
			echo json_encode($result);
			
		}
	}


	public function get_view_picklist(){
		extract($_POST);


	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	///$token='e'.$str;
	$token=$str;

		//$user =jwt::decode($token,$this->config->item('jwt_key'));	
	//	$prefix=$user->prefix;
	$this->load->database($token);

		$sales = $this->db->query("select id from sma_sales  where picklist_number='$picklist_number'")->result();
		foreach ($sales as $row) {
			$sales_id[]= $row->id;
		}	
		
		$data['picklist_number']=$picklist_number;
		$data['reference_no'] = $this->sales_model->picking_reference_no_all($sales_id);
		$data['accound_no'] = $this->sales_model->picking_accound_no_all($sales_id);
		$data['route_number'] = $this->sales_model->picking_route_number_all($sales_id);
		$items = $this->sales_model->picking_all($sales_id);
		foreach ($items as $item => $v){
			if ($v['order_type'] =='box' ||  $v['order_type'] == '') {
				$order_type ="Box";

			} else {
				$order_type="Piece";
			}

			$packing_details[] = [
				'name' => $v['product_name'] .'-'. $v['size'],
				'code' => $v['product_code'],
				'size' => $v['size'],
				'pack' => $v['pack'],
				'price' => $v['price'],
				'quantity' => $v['total'],
				'bay' => $v['bay'],
				'rack' => $v['rack'],
				'split' => $v['split'],
				'order_type' =>  $order_type,
				'split_price' => $v['split_price'],
			];

		}
		$data['packing_details'] = $packing_details;
		if (count($data['packing_details']) != 0) {		
			
			$result = [
				"data"=>$data,
				"status" => 'true',
				"massage" => 'picking details found',					
			];        
			echo json_encode($result);
		} else {
			$result = [
				"picklist_number"=>'0',
				"status" => 'false',
				"massage" => 'picking details not found',					
			];        
			echo json_encode($result);
			
		}
	}
	
	public function get_view_picking_history(){
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

		$draw = intval($this->input->post("draw"));
		$start = intval($this->input->post("start"));
		$length = intval($this->input->post("length"));

		$order = $this->input->post('order')[0];
		$searchValue = $this->input->post('search')['value'];
		$orderBy = $this->input->post('columns')[$order['column']]['data'];
		$orderDir = $order['dir'];

		if (!empty($searchValue)) {
			$items = $this->db->query("select sma_sale_items.id AS id, sale_id, picked_qty, product_id, product_name, product_code, order_type, SUM(sma_sale_items.order_qty) AS total, SUM(sma_sale_items.confirmed_qty) AS confirmed_total, sma_products.size, pack, price, bay, rack, split_price, split FROM sma_sale_items LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id WHERE sale_id IN (SELECT id FROM sma_sales WHERE picklist_number = '$picklist_number') AND  order_qty != 0 AND (product_code LIKE '%$searchValue%' OR product_name LIKE '%$searchValue%' OR order_type LIKE '%$searchValue%')  GROUP BY product_code, order_type ORDER BY product_code ASC LIMIT $start, $length")->result_array();   

		}else{

			$items = $this->db->query("select sma_sale_items.id AS id, sale_id, picked_qty, product_id, product_name, product_code, order_type, SUM(sma_sale_items.order_qty) AS total, SUM(sma_sale_items.confirmed_qty) AS confirmed_total, sma_products.size, pack, price, bay, rack, split_price, split FROM sma_sale_items LEFT JOIN sma_products ON sma_sale_items.product_id = sma_products.id WHERE sale_id IN (SELECT id FROM sma_sales WHERE picklist_number = '$picklist_number') AND  order_qty != 0  GROUP BY product_code, order_type ORDER BY product_code ASC LIMIT $start, $length")->result_array();

		}


		$data1 = [];$picked_sum= 0;$total_sum = 0;$confirmed_qty=0;
		foreach ($items as $item) {
			$sale_id = $item['sale_id'];
			$p_id = $item['product_id'];

			$sales_order = $this->db->select('created_by')->from('sma_sales')->where('id', $sale_id)->get()->row_array();

			$picklist = $this->db->select('picked_qty')->from('sma_picked_product_details')->where('picking_list_no', $picklist_number)->where('product_id', $p_id)->get()->row_array();

			$sale_person = get_sales_rep($sales_order['created_by']);

			$order_type = ($item['order_type'] == 'box') ? 'Box' : 'Piece';

			$data1[] = [
				'code' => $item['product_code'],
				'name' => $item['product_name'] . '-' . $item['size'],
				'order_type' => $order_type,
				'quantity' => $item['total'],
				'picked_qty' => isset($picklist['picked_qty']) ? $picklist['picked_qty'] : 0,
				'confirmed_total' => $item['confirmed_total'],
				'sale_person' => $sale_person,
				'product_id'=> $p_id

			];
			$total_sum += $item['total'];$picked_sum +=$picklist['picked_qty'];$confirmed_qty +=$item['confirmed_total'];
		}


		$result = [
			"draw" => $draw,
			"recordsTotal" => count($items), 
			"recordsFiltered" => count($items), 
			"data" => $data1,
			"total_sum" =>number_format($total_sum,2),
			"picked_sum" =>number_format($picked_sum,2),
			"confirmed_qty" =>number_format($confirmed_qty,2),
		];


		echo json_encode($result);
		exit();
	}


	public function picking_list()
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

		$draw = intval($this->input->post("draw"));
		$start = intval($this->input->post("start"));
		$rowperpage = intval($this->input->post("length"));

		$searchValue = $this->input->post('search')['value'];
		$print_on=date('Y-m-d', strtotime($searchValue));
		$order = $this->input->post('order')[0];
		$orderBy = @$this->input->post('columns')[$order['column']]['data'];
		$orderDir = $order['dir'];

		$this->db->select('sma_sales.picklist_number, first_name, last_name, print_on, picking_status, is_lock');
		$this->db->from('sma_sales');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.picker_id', 'left');
		$this->db->where('sma_sales.picklist_number !=', '');
		$this->db->where('sma_sales.picking_status !=', 'done');
		$this->db->group_by('sma_sales.picklist_number');

		$totalRecords = $this->db->count_all_results();

		$this->db->select('sma_sales.picklist_number, first_name, last_name, print_on, picking_status, is_lock');
		$this->db->from('sma_sales');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.picker_id', 'left');
		$this->db->where('sma_sales.picklist_number !=', '');
		$this->db->where('sma_sales.picking_status !=', 'done');

		if (!empty($searchValue)) {
			$searchV = "(picklist_number like '%" . $searchValue . "%' or first_name like '%" . $searchValue . "%' or last_name like '%" . $searchValue . "%' or print_on like '%" . $print_on . "%')";
			$searchV .= " or EXISTS (SELECT 1 FROM sma_daily_sales_sheet WHERE sma_daily_sales_sheet.sale_id = sma_sales.id AND dss_no like '%" . $searchValue . "%')";
			$this->db->where($searchV);
		}

		if (!empty($orderBy) && $orderBy !='i') {
			//echo 'hii';exit();
			$this->db->order_by($orderBy, $orderDir);
		}else{
			//echo 'byy';exit();
			$this->db->order_by('sma_sales.picklist_number', 'DESC');
		}
		$this->db->group_by('sma_sales.picklist_number');

		$this->db->limit($rowperpage, $start);
		$records = $this->db->get()->result();

		$data1 = [];
		$i=1;
		foreach ($records as $p) {

			$this->db->select('sma_sales.id, sma_companies.route, sma_routes.route_name');
			$this->db->from('sma_sales');
			$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left');
			$this->db->join('sma_routes', 'sma_routes.id = sma_companies.route', 'left');
			$this->db->where('picklist_number', $p->picklist_number);
			$this->db->group_by('sma_routes.route_name');
			$picklist_number = $this->db->get()->result_array();

			$p_number =$p->picklist_number; 
			$sale_id = $picklist_number[0]['id'];
			// $dss_no = $this->db->query("select dss_no FROM `sma_daily_sales_sheet` where sale_id='$sale_id'")->row_array();

			$total_sales_count = $this->db->query("select count(id) as total_sales_count FROM `sma_sales` where picklist_number='$p->picklist_number'")->row()->total_sales_count;


			$route = '';
			$lastIndex = count($picklist_number) - 1;

			foreach ($picklist_number as $index => $row) {
				$route .= $row['route_name'];
				if ($index < $lastIndex) {
					$route .= ',';
				}
			}

			$name = $p->first_name . ' ' . $p->last_name;
			$picking_status = ucfirst($p->picking_status);

			$dateTime = new DateTime($p->print_on);
			$print_on = $dateTime->format('d-m-Y');

			$data1[] = [
				
				"i" => $i++,
				"picklist_number" => $p_number,
				"total_sales_count" => $total_sales_count,
				// "dss" => $dss_no['dss_no'],
				"route_name" => $route,
				"name" => $name,
				"print_on" => $print_on,
				"picking_status" => $picking_status,
				"is_lock"=> $p->is_lock,
				"action" => $p_number,
			];
		}

		$result = [
			"draw" => intval($draw),
			"recordsTotal" =>  $totalRecords,
			"recordsFiltered" => $totalRecords,
			"data" => $data1
		];

		echo json_encode($result);
		exit();
	}


	public function remove_picklist() {

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

		if ($picklist_number != '') {		
			$picklist = $this->db->query("select id from sma_sales where picklist_number='$picklist_number'")->result_array();
			foreach ($picklist as $key) {
				$order_id=$key['id'];
				$update = array('picklist_number' => '', 'print' => '', 'print_on' =>'', 'print_by' => '', 'picker_id' =>'');
				$this->db->where('id', $order_id);
				$this->db->update('sma_sales', $update);
			}
			$result = [					
				"status" => 'true',
				"massage" => 'Picklist Successfully Delete',					
			];        
			echo json_encode($result);
		} else {
			$result = [
				"status" => 'false',
				"massage" => 'Picklist number empty',					
			];        
			echo json_encode($result);
		}
	}


	public function update_picker() {

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

		if ($picker_id != '' && $picklist_number != '') {		
			if($fs_no==''){
				$fs_no=0;
			}
			$update = array('picker_id' => $picker_id);
			$this->db->where('picklist_number', $picklist_number);
			$this->db->update('sma_sales', $update);
			$result = [					
				"status" => 'true',
				"massage" => 'Picker Assign Successfully',					
			];        
			echo json_encode($result);
		} else {
			$result = [

				"status" => 'false',
				"massage" => 'please select picker or picklist_number',					
			];        
			echo json_encode($result);

		}


	}
	public function edit_picking_order_list(){
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

		$draw=intval($this->input->post("draw"));
		$start=intval($this->input->post("start"));
		$length=intval($this->input->post("length"));


		$order = $this->input->post('order')[0];
		$searchValue = $this->input->post('search')['value'];
		$orderBy = $this->input->post('columns')[$order['column']]['data'];
		$orderDir = $order['dir'];
		$totalRecords = $this->db->query("select sma_sales.id as id, sma_sales.date,sma_sales.reference_no,sma_sales.total_discount,sma_sales.customer,sma_sales.customer_id, sma_sales.grand_total, sma_sales.grand_total-paid as balance,sma_companies.accound_no,sma_routes.route_number,sma_sales.created_by,sma_sales.picklist_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.picklist_number ='$picklist_number' ORDER BY sma_sales.id DESC")->result_array();


		$this->db->select('sma_sales.id as id, sma_sales.date, sma_sales.reference_no, sma_sales.total_discount, sma_sales.customer, sma_sales.customer_id, sma_sales.grand_total, sma_companies.accound_no, sma_routes.route_number, sma_sales.created_by, sma_sales.picklist_number');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'LEFT');
		$this->db->join('sma_routes', 'sma_companies.route = sma_routes.id', 'LEFT');
		$this->db->where('sma_sales.picklist_number', $picklist_number);
		if (!empty($searchValue)) {
			$searchV = "(sma_companies.accound_no LIKE '%" . $searchValue . "%' OR sma_sales.reference_no LIKE '%" . $searchValue . "%' OR sma_sales.customer LIKE '%" . $searchValue . "%')";

			$this->db->where($searchV);
		}
		if (!empty($orderBy) && $orderBy !='i') {
			$this->db->order_by($orderBy, $orderDir);
		}else{
			$this->db->order_by('sma_sales.id', 'DESC');
		}
			// $this->db->order_by('sma_sales.id', 'DESC');
		$this->db->limit($length, $start);
		$data = $this->db->get()->result();

		$data1=array();
		$i=1;
		foreach ($data as $r) 
		{

			$date = new DateTime($r->date);
			$formattedDate = $date->format('d-m-Y');

			$data1[] = array(
				"i" => $i++,
				"date"=> $formattedDate,				
				"customer" => $r->customer.'('.$r->accound_no.')',
				"reference_no" => $r->reference_no,
				"grand_total" =>  number_format($r->grand_total-$r->total_discount, 2),
				"sales_rep_collected" =>  number_format(sale_person_collected($r->created_by, $r->id), 2),
				"route_number" => $r->route_number,				
				"action" => $r->id
			);
		}

		$result = [
			"draw" => intval($draw),
			"recordsTotal" => count($totalRecords),
			"recordsFiltered" => count($data),
			"data" => $data1
		];    
		echo json_encode($result);

		exit();
	}

	public function add_order_list(){


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


		$order = $this->input->post('order')[0];
		$searchValue = $this->input->post('search')['value'];
		$orderBy = $this->input->post('columns')[$order['column']]['data'];
		$orderDir = $order['dir'];


		$this->db->select('sma_sales.id as id');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'LEFT');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.created_by', 'LEFT');
		$this->db->join('sma_routes', 'sma_companies.route = sma_routes.id', 'LEFT');
		$this->db->where('sma_sales.sale_status', 'Accept');
		$this->db->where('picker_id', '0');
		$this->db->where('sma_sales.is_delete', '0');
		$this->db->order_by('sma_sales.id', 'DESC');

		$totalRecords = $this->db->get()->result_array();


		$this->db->select('sma_sales.id as id, sma_sales.date, sma_sales.reference_no, sma_users.first_name, sma_users.last_name, sma_sales.deliverydate, sma_sales.customer, sma_sales.customer_id, sma_companies.accound_no, sma_sales.grand_total, sma_routes.route_number');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'LEFT');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.created_by', 'LEFT');
		$this->db->join('sma_routes', 'sma_companies.route = sma_routes.id', 'LEFT');
		$this->db->where('sma_sales.sale_status', 'Accept');
		$this->db->where('picker_id', '0');
		$this->db->where('sma_sales.is_delete', '0');
		if (!empty($searchValue)) {
			$searchV = "(sma_companies.accound_no LIKE '%" . $searchValue . "%' OR sma_sales.reference_no LIKE '%" . $searchValue . "%' OR sma_sales.customer LIKE '%" . $searchValue . "%')";

			$this->db->where($searchV);
		}
		if (!empty($orderBy) && $orderBy !='i') {
			$this->db->order_by($orderBy, $orderDir);
		}else{
			$this->db->order_by('sma_sales.id', 'DESC');
		}
		$this->db->limit($length, $start);
		$data = $this->db->get()->result();

		$data1=array();
		foreach ($data as $r) 
		{

			$name = $r->first_name.' '.$r->last_name;
			$date = new DateTime($r->date);
			$formattedDate = $date->format('d-m-Y');
			$date1 = new DateTime($r->deliverydate);
			$deliverydate = $date1->format('d-m-Y');
			$data1[] = array(
				"id"=> $r->id,    
				"date"=> $formattedDate,
				"reference_no"=> $r->reference_no,
				"customer"=> $r->customer.'('.$r->accound_no.')',
				"route_number"=> $r->route_number,
				"first_name"=>  $name,
				"deliverydate"=> $deliverydate
			);
		}

		$result = [
			"draw" => intval($draw),
			"recordsTotal" => count($totalRecords),
			"recordsFiltered" => count($data),
			"data" => $data1
		];    
		echo json_encode($result);

		exit();
	}

	public  function add_order_picking_list()
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

		if (!empty($val)){  

			foreach ($val as $id){      
				$update = array(
					'picklist_number' => $picklist_number, 
					'print' => 'Y', 
					'print_on' => date('Y-m-d H:i:s'), 
					'print_by' => $this->session->userdata('username'), 
					'picker_id' => $pickerid,
					'sale_status' => 'Accept' 
				);
				$this->db->where('id', $id);
				$this->db->update('sma_sales', $update);

				// if (!empty($fs_no)) {
				// 	$data = array(
				// 		'sale_id' => $id,
				// 		'dss_no' => $fs_no, 
				// 		'date' => date('Y-m-d'),
				// 		'created_by' => $this->session->userdata('user_id')
				// 	);
				// 	$this->db->insert('sma_daily_sales_sheet', $data);
				// }
			}
			$result = [					
				"status" => 'true',
				"massage" => 'Picking Update Successfully',					
			];        
			echo json_encode($result);
		} else {
			$result = [
				"status" => 'false',
				"massage" => 'Order Not Select',					
			];        
			echo json_encode($result);

		}
	}

	public function shortpick_list() 
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

		$columnIndex = @$columnIndex_arr[0]['column']; 
		$columnName = @$columnName_arr[$columnIndex]['data'];
		$columnSortOrder = $order_arr[0]['dir'];
		$searchValue = $search_arr['value'];


		$this->db->select('sma_sales.id, sma_sales.picking_status, sma_sales.picklist_number, route, print_on, sma_users.first_name, sma_users.last_name');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left');
		$this->db->join('sma_picked_product_details', 'sma_picked_product_details.picking_list_no = sma_sales.picklist_number', 'left');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.picker_id', 'left');
		$this->db->where('picklist_number !=', '');
		$this->db->where('sma_users.id !=', '');
		$this->db->where('picking_status', 'done');
		$this->db->where('is_confirmed', 'N');
		$this->db->group_by('picking_list_no');
		$records = $this->db->get()->result();
		$totalRecords = $records;

		$this->db->select('sma_sales.id, sma_sales.picking_status, sma_sales.picklist_number, route, print_on, sma_users.first_name, sma_users.last_name');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left');
		$this->db->join('sma_picked_product_details', 'sma_picked_product_details.picking_list_no = sma_sales.picklist_number', 'left');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.picker_id', 'left');
		$this->db->where('picklist_number !=', '');
		$this->db->where('sma_users.id !=', '');
		$this->db->where('picking_status', 'done');
		$this->db->where('is_confirmed', 'N');
		$this->db->group_by('picking_list_no');
		$this->db->order_by('sma_sales.picklist_number', 'DESC');
		if ($searchValue != '') {
			$searchV = "(sma_sales.picklist_number LIKE '%" . $searchValue . "%' OR sma_users.first_name LIKE '%" . $searchValue . "%' OR sma_users.last_name LIKE '%" . $searchValue . "%' ) ";
			$this->db->where($searchV);
		}
		$totalRecordswithFilter = $this->db->get()->result();

		$this->db->select('sma_sales.id, sma_sales.picking_status, sma_sales.picklist_number, route, print_on, sma_users.first_name, sma_users.last_name');
		$this->db->from('sma_sales');
		$this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left');
		$this->db->join('sma_picked_product_details', 'sma_picked_product_details.picking_list_no = sma_sales.picklist_number', 'left');
		$this->db->join('sma_users', 'sma_users.id = sma_sales.picker_id', 'left');
		$this->db->where('picklist_number !=', '');
		$this->db->where('sma_users.id !=', '');
		$this->db->where('picking_status', 'done');
		$this->db->where('is_confirmed', 'N');
		$this->db->group_by('picking_list_no');
		$this->db->order_by('sma_sales.picklist_number', 'DESC');
		if ($searchValue != '') {
			$searchV = "(sma_sales.picklist_number LIKE '%" . $searchValue . "%' OR sma_users.first_name LIKE '%" . $searchValue . "%' OR sma_users.last_name LIKE '%" . $searchValue . "%' ) ";
			$this->db->where($searchV);
		}
		$this->db->limit($rowperpage,$start);
		$records  = $this->db->get()->result();


		foreach ($records as $p) 
		{

			$picklist_number = $p->picklist_number;

			$picklist_number = $this->db->query("select sma_sales.id,sma_sales.picklist_number,sma_companies.route,sma_routes.route_name FROM `sma_sales` left join sma_companies on sma_companies.id=sma_sales.customer_id left join sma_routes on sma_routes.id=sma_companies.route where picklist_number='$picklist_number' group by sma_routes.route_name")->result_array();


			$route = '';
			$lastIndex = count($picklist_number) - 1;
			foreach ($picklist_number as $index => $row) {
				$route .= $row['route_name'];
				if ($index < $lastIndex) {
					$route .= ',';
				}
			}

			$name = $p->first_name.' '.$p->last_name;

			$dateTime = new DateTime($p->print_on);
			$print_on = $dateTime->format('d-m-Y');

			//$view = '<a class="btn btn-primary" href="' . base_url('short/listview/' . $p->picklist_number) . '">View</a>';

			$data1[] = array(
				"picklist_number"=> $p->picklist_number,    
				"route"=> $route,
				"name"=> $name,
				"print_on"=> $print_on,
				"view"=> $p->picklist_number
			);
		}
		//print_r($data1);exit();
		$response = array(
			"draw" => intval($draw),
			"recordsTotal" =>  count($totalRecords),
			"recordsFiltered" => count($totalRecordswithFilter),
			"data" => $data1
		);        
		echo json_encode($response);
	}




	public function short_list() {
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

		$picklist_number = $_POST['id'];


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


		$sales = $this->db->query("select id from sma_sales  where picklist_number='$picklist_number'")->result_array();
		$values = '';
		foreach ($sales as $row) {
			$values .= $row['id'] . ",";
		}
		$sales_id = rtrim($values, ',');

		$totalRecords = $this->db->query("select sma_sale_items.id as id,sma_sale_items.picked_qty,sma_sale_items.order_type,
			sma_sale_items.product_id,product_name,product_code,order_type,sum(sma_sale_items.quantity) as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id  where sale_id in($sales_id) and sma_sale_items.is_short_qty_delete='0' and  sma_sale_items.product_id in(select product_id from sma_picked_product_details where sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.picked_qty!=sma_picked_product_details.actual_qty)  group by product_id,order_type")->result();


		$totalRecordswithFilter = $this->db->query("select sma_sale_items.id as id,sma_sale_items.picked_qty,sma_sale_items.order_type,
			sma_sale_items.product_id,product_name,product_code,order_type,sum(sma_sale_items.quantity) as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id  where sale_id in($sales_id) and sma_sale_items.is_short_qty_delete='0' and  sma_sale_items.product_id in(select product_id from sma_picked_product_details where sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.picked_qty!=sma_picked_product_details.actual_qty) and (
			sma_sale_items.order_type LIKE '%" . $searchValue . "%' OR sma_sale_items.product_name LIKE '%" . $searchValue . "%' OR sma_sale_items.product_code LIKE '%" . $searchValue . "%')
			group by product_id,order_type")->result();

		$records = $this->db->query("select sma_sale_items.id as id,sma_sale_items.picked_qty,sma_sale_items.order_type,
			sma_sale_items.product_id,product_name,product_code,order_type,sum(sma_sale_items.quantity) as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id  where sale_id in($sales_id) and sma_sale_items.is_short_qty_delete='0' and  sma_sale_items.product_id in(select product_id from sma_picked_product_details where sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.picked_qty!=sma_picked_product_details.actual_qty) and (
			sma_sale_items.order_type LIKE '%" . $searchValue . "%' OR sma_sale_items.product_name LIKE '%" . $searchValue . "%' OR sma_sale_items.product_code LIKE '%" . $searchValue . "%')
			group by product_id,order_type LIMIT $start, $rowperpage")->result();


		$i=1;

		foreach ($records as $r) 
		{
			$p_id=$r->product_id;
			$picklist = $this->db->query("select picked_qty FROM `sma_picked_product_details` where picking_list_no='$picklist_number' and product_id='$p_id'")->row_array();


			$code = $r->product_code;
			$name = $r->product_name;
			$order_qty = round($r->total).' '. $r->order_type;
			$pick_qty = $picklist['picked_qty'];

			$data1[] = array(
				'i'=>$i,
				"product_id"=> $r->product_id,    
				"code"=>  $code,
				"name"=> $name,
				"order_qty"=> $order_qty,
				"pick_qty"=> $pick_qty,
				"action"=>  $action,
				"picklist_number"=>$picklist_number,
				"order_type" =>$r->order_type 
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

	public function remove_order_picklist(){
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

		if ($orderId !='') {

			$update = array(
				'picklist_number' => '',
				'print' => '',
				'print_on' => '',
				'print_by' => '',
				'picker_id' => ''
			);
			$this->db->where('id', $orderId);      
			$this->db->update('sma_sales', $update);

			// $this->db->where('dss_no', $fs);
			// $this->db->where('sale_id', $orderId);
			// $this->db->delete('sma_daily_sales_sheet');



			$result = [					
				"status" => 'true',
				"massage" => 'Remove Order Picking List Successfully',					
			];        
			echo json_encode($result);
		} else {
			$result = [
				"status" => 'false',
				"massage" => 'Order Not Remove',					
			];        
			echo json_encode($result);

		}
	}

	public function update_short_quantity() {
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


		if(count($order_id)!=0)
		{
			for ($i = 0; $i < count($order_id); $i++) {

				$confirm_qty1 =$confirm_qty[$i];
				$product_id1 = $product_id[$i];
				$sale_id = $order_id[$i];


				if($confirm_qty1==0)
				{
					$get_product = $this->db->query("select sale_id,order_type,product_id,quantity from sma_sale_items  where  sma_sale_items.id='$sale_id'")->result_array();

					$saleid=$get_product[0]['sale_id'];
					$p_id = $get_product[0]['product_id'];
					$o_type = $get_product[0]['order_type'];
					$o_qty = $get_product[0]['quantity'];
					if($o_type!='box'){

						$products1 = $this->db->query("select parent_id from sma_products where id='$p_id'")->row_array();
						$p_id = $products1['parent_id'];

						if($p_id==''|| $p_id==0){
							$p_id =  $row->product_id;    				    				 
						}
						$products1 = $this->db->query("select split_quantity,parent_id from sma_products where id='$p_id'")->row_array();

						$split_quantity = $products1['split_quantity'];
						$qty = $split_quantity + $o_qty; 
						$update_sales = array('split_quantity' => $qty); 
					}else{
						$products = $this->db->query("select quantity from sma_products where id='$p_id'")->row_array();
						$quantity = $products['quantity'];

						$qty = $quantity + $o_qty; 
						$update_sales = array('quantity' => $qty); 
					}

					$this->db->where('id',$p_id);
					$this->db->update('sma_products',$update_sales); 

					$get_product1 = $this->db->query("SELECT sum(item_tax)+sum(subtotal) as grand_total,sum(item_tax) as total_tax FROM `sma_sale_items` where sale_id='$saleid' and  is_delete='0'")->result_array();
					$grand_total=$get_product1[0]['grand_total'];
					$total_tax=$get_product1[0]['total_tax'];
					$update_sal2 = array('grand_total'=>$grand_total,'total_tax'=>$total_tax);

					$this->db->where('id', $saleid);
					$this->db->update('sma_sales', $update_sal2);


				}
				if($confirm_qty1!=0)
				{
					$get_product = $this->db->query("select sale_id from sma_sale_items  where  sma_sale_items.id='$sale_id'")->result_array();

					$saleid=$get_product[0]['sale_id'];

					$get_product1 = $this->db->query("SELECT sum(item_tax)+sum(subtotal) as grand_total,sum(item_tax) as total_tax FROM `sma_sale_items` where sale_id='$saleid' and is_delete='0'")->result_array();
					$grand_total=$get_product1[0]['grand_total'];

					$total_tax=$get_product1[0]['total_tax'];
					$update_sal2 = array('grand_total'=>$grand_total,'total_tax'=>$total_tax);

					$this->db->where('id', $saleid);
					$this->db->update('sma_sales', $update_sal2);



				}
				$this->sales_model->update_product_stock($product_id1,$sale_id,$confirm_qty1);
				$update = array(
					"quantity" => $confirm_qty1,
					"confirmed_qty" => $confirm_qty1,
					'is_short_qty_delete' => 1,
				);
				$this->db->where('product_id', $product_id1);
				$this->db->where('id', $sale_id);
				$this->db->update('sma_sale_items', $update);

				$sma_sales = $this->db->query("select sma_sale_items.id from sma_sale_items left join sma_sales on sma_sales.id=sma_sale_items.sale_id where sma_sale_items.is_delete='0' and sma_sales.picklist_number='$picklist_number' and is_short_qty_delete='0'")->result_array();

				if (count($sma_sales) == 0) 
				{

					$update2 = array(
						"is_confirmed" => 'Y',
						"sale_status"=>'Invoiced',
						"invoice_date"=>date('Y-m-d'),
					);

					$this->db->where('picklist_number', $picklist_number);
					$this->db->update('sma_sales', $update2);

				}

				$update1 = array(
					"is_confirm" => 'Y',
				);
				$this->db->where('product_id', $product_id1);
				$this->db->where('picking_list_no', $picklist_number);
				$this->db->update('sma_picked_product_details', $update1);

				$j++;

			}


              	// $this->session->set_flashdata('success', 'Quantity Updated Successfully');
              	// redirect('admin/picking/short_list/' . $picklist_number);

			$result = [
				"status" => 'true',
				"massage" => 'Quantity Updated Successfully',					
			];        
			echo json_encode($result);



		} else {
			$result = [
				"status" => 'false',
				"massage" => 'Order Not Remove',					
			];        
			echo json_encode($result);

		}
	}



	public function view_sales_details(){
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

		$sales = $this->db->query("select id from sma_sales  where picklist_number='$picklist_number'")->result_array();
		$values = '';
		foreach ($sales as $row) {
			$values .= $row['id'] . ",";
		}
		$sales_id = rtrim($values, ',');

		$totalRecords = $this->db->query("select sma_sale_items.id as id,picked_qty,product_id,product_name,product_code,order_type,sale_id,sma_sale_items.confirmed_qty,sma_sale_items.unit_quantity as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id where sale_id in($sales_id) and sma_sale_items.product_id='$product_id' and sma_sale_items.order_type='$order_type'")->result();


		$records = $this->db->query("select sma_sale_items.id as id,picked_qty,product_id,product_name,product_code,order_type,sale_id,sma_sale_items.confirmed_qty,sma_sale_items.unit_quantity as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id where sale_id in($sales_id) and sma_sale_items.product_id='$product_id' and sma_sale_items.order_type='$order_type' and (sma_sale_items.order_type LIKE '%" . $searchValue . "%' OR sma_sale_items.product_name LIKE '%" . $searchValue . "%' OR sma_sale_items.product_code LIKE '%" . $searchValue . "%')  LIMIT $start, $rowperpage")->result();

		

		$check_reason=$this->db->query("select sma_picked_product_details.picked_qty,sma_picked_product_details.no_pick_reason from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id left join sma_picked_product_details on sma_picked_product_details.product_id=sma_sale_items.product_id where sale_id in($sales_id) and sma_sale_items.product_id in(select product_id from sma_picked_product_details where sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.picked_qty!=sma_picked_product_details.actual_qty) and sma_sale_items.product_id='$product_id' and sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.order_type='$order_type' group by sma_sale_items.product_id")->result_array();
		$no_pick_reason = '';
		foreach ($check_reason as $q) {
			$no_pick_reason = $q['no_pick_reason'];
		}
		$i=1;

		foreach ($records as $r) 
		{
			$product_id=$r->product_id;
			$id = $r->sale_id;
			$sales = $this->db->query("select sma_sales.customer,sma_sales.reference_no,picklist_number from sma_sale_items left join sma_sales on sma_sales.id=sma_sale_items.sale_id where sma_sale_items.sale_id='$id' and sma_sale_items.product_id='$product_id'")->row_array();

			$data1[] = array(
				'i'=>$i,
				"reference_no"=> $sales['reference_no'],    
				"customer"=>  $sales['customer'],
				"product_code"=> $r->product_code,
				"product_name"=> $r->product_name,
				"quantity"=> round($r->total),
				"order_type"=>   $r->order_type,
				"no_pick_reason"=>$no_pick_reason			
			);
		}

		$response = array(
			"draw" => intval($draw),
			"recordsTotal" =>  count($totalRecords),
			"recordsFiltered" => count($totalRecords),
			"data" => $data1
		);   


		echo json_encode($response);
	}
}