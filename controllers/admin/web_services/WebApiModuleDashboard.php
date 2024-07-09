<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModuleDashboard extends CI_Controller {
	
	public function __construct() {
		parent::__construct();		

		$this->digital_upload_path = 'files/';
		$this->upload_path = 'assets/uploads/';
		$this->thumbs_path = 'assets/uploads/thumbs/';
		$this->image_types = 'gif|jpg|jpeg|png|tif';
		$this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
		$this->allowed_file_size = '1024';
		$this->load->library('upload');
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') 
		{
	    http_response_code(200); // OK status for preflight request
	    exit();
	}
}

public function get_dashboard_count()
{   
	extract($_POST);
	$this->session->set_userdata('prefix',$prefix);
	$prefix = $this->session->userdata('prefix');
	$this->load->database($prefix);
	$this->load->model('api_model');	
	$this->load->model('auth_model');
	if(@$reportrange=="")
	{
		$this->session->unset_userdata('start_date');
		$this->session->unset_userdata('end_date');
		$this->session->unset_userdata('push_invoice_start_date');
		$this->session->unset_userdata('push_invoice_end_date');
		$start_date = date('Y-m-d', strtotime('first day of this month')) . ' 00:00:00';
		$end_date   =  date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59';
		$startdate  = date('Y-m-d 00:00:00');
		$enddate    = date('Y-m-d 23:59:59');
	}else
	{
		$reportrange = $_POST['reportrange'];
		$arry = explode("-", $reportrange);
		$start_date = $arry[0];
		$end_date = $arry[1];
		$push_invoice_start_date = date("Y-m-d", strtotime($start_date));
		$push_invoice_end_date = date("Y-m-d", strtotime($end_date));

		$start_date = date("Y-m-d 00:00:00", strtotime($start_date));
		$end_date = date("Y-m-d 23:59:59", strtotime($end_date));
		$startdate = date("Y-m-d 00:00:00", strtotime($start_date));
		$enddate = date("Y-m-d 23:59:59", strtotime($end_date));

		$this->session->set_userdata('start_date', $start_date);
		$this->session->set_userdata('end_date', $end_date);
		$this->session->set_userdata('push_invoice_start_date', $push_invoice_start_date);
		$this->session->set_userdata('push_invoice_end_date', $push_invoice_end_date);
	}

		//Month bases
	$sales_order = $this->db->query("select sum(order_qty * unit_price) as grand_total ,SUM(item_tax) as item_tax FROM `sma_sale_items` WHERE sale_id in(select id from sma_sales where date BETWEEN '$start_date' AND '$end_date')")->row_array();
		//print_r($sales_order);exit();
	$delivered_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where date BETWEEN '$start_date' AND '$end_date' and sale_status ='Delivered'")->row_array();
	$sage_push_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where sale_status ='Delivered' and push_invoice='Y' and  date BETWEEN '$start_date' AND '$end_date'")->row_array();
	$pending_sage_push_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where sale_status ='Delivered' and push_invoice='N' and date BETWEEN '$start_date' AND '$end_date'")->row_array();
	
	$new_sales = $this->db->query("select count(id) as total_new_sale from sma_sales where  date >='$startdate' and date <'$enddate' and sale_status='New'")->row_array();
	$sale_accept = $this->db->query("select count(id) as total_accept_sale from sma_sales where  accept_date >='$startdate' and accept_date <'$enddate' ")->row_array();

	$picklistgenerated = $this->db->query("select count(sma_sales.id) as total_picker from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where print_on >='$startdate' and print_on <'$enddate' group by sma_sales.picklist_number")->result_array();

	$picker_count = $this->db->query("select count(id)as total_picker from sma_sales where print_on >='$startdate' and print_on <'$enddate'")->row_array();

	$picking_confirmed = $this->db->query("select sma_sales.id as total_picking from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where picked_date >='$startdate' and picked_date <'$enddate'group by sma_sales.picklist_number ")->result_array();

	$picking_confirmed_inv = $this->db->query("select id as total_picking_inv from sma_sales where picked_date >='$startdate' and picked_date <'$enddate'")->result_array();

	$total_manifest_order = $this->db->query("select print_id from sma_manifest where date >='$startdate' and date <'$enddate' and is_deleted='N' and intransit='Y' and is_manifest='Y'")->result_array();
	$manifest_generated = $this->db->query("select count(id) as total_manifest_generated from sma_manifest where  date >='$startdate' and date <'$enddate' and is_deleted='N' and intransit='Y' and is_manifest='Y'")->row_array();
	$manifest_order=0;
	foreach($total_manifest_order as $r)
	{
		$da=$r['print_id'];
		$order = $this->db->query("select id from sma_sales where sma_sales.id in ($da)")->result_array();
		$manifest_order +=count($order);
	}

	$delivery_confirmed = $this->db->query("select count(id) as total_delivery_confirmed from sma_manifest where delivered_status ='Y' and date >='$startdate' and date <'$enddate'")->row_array();

	$total_push = $this->db->query("select count(id) as total_inv from sma_sales where push_invoice_date >='$startdate' and push_invoice_date <'$enddate' and push_invoice='Y'")->row_array();

	$users = $this->db->query("select sma_users.id,first_name,last_name, email, company,sma_groups.name,sma_users.active,sum(sma_sales.grand_total) as grand_total ,SUM(sma_sales.total_tax) as tax_total from sma_users left join sma_groups on sma_users.group_id=sma_groups.id left join sma_sales on sma_sales.created_by=sma_users.id where sma_users.active='1' and sma_sales.date >='$startdate' and sma_sales.date <'$enddate' and company_id is null group by sma_users.id order by grand_total desc limit 1")->result_array(); 


	$top_sales= $this->db->query("select sma_companies.id, sma_companies.company as name,count(sma_sales.id) as total_orders, SUM(sma_sales.grand_total) as total_amount, SUM(sma_sales.total_tax) as tax_total FROM sma_sales LEFT JOIN sma_companies on sma_companies.id=sma_sales.customer_id WHERE sma_companies.group_name='customer' and sma_sales.date >='$startdate' and sma_sales.date <'$enddate' GROUP BY customer_id ORDER BY total_amount DESC LIMIT 1;")->result_array();
	$from_date = date("d-m-Y", strtotime($start_date));
	$to_date = date("d-m-Y", strtotime($end_date));

	$result=array(
		'from_date' => $from_date,
		'to_date' => $to_date,
		'sales_grand_total'=>($sales_order['grand_total'] == '') ? 0 : $sales_order['grand_total'],
		'sales_item_tax'=> ($sales_order['item_tax'] == '') ? 0 : $sales_order['item_tax'],

		'delivered_invoice_grand_total'=>$delivered_invoice['grand_total']-$delivered_invoice['tax_total'],
		'delivered_invoice_item_tax' => ($delivered_invoice['tax_total'] == '') ? 0 : $delivered_invoice['tax_total'],

		'sage_push_invoice_grand_total'=>$sage_push_invoice['grand_total']-$sage_push_invoice['tax_total'],
		'sage_push_invoice_item_tax'=>($sage_push_invoice['tax_total'] == '') ? 0 : $sage_push_invoice['tax_total'],

		'pending_sage_push_invoice_grand_total'=>$pending_sage_push_invoice['grand_total']-$pending_sage_push_invoice['tax_total'],
		'pending_sage_push_invoice_item_tax'=> ($pending_sage_push_invoice['tax_total'] == '') ? 0 : $pending_sage_push_invoice['tax_total'],

		'new_sales'=> $new_sales['total_new_sale'],
		'sale_accept'=> $sale_accept['total_accept_sale'],
		'picklistgenerated'=> count($picklistgenerated),
		'totalpicker'=> $picker_count['total_picker'],
		'picking_confirmed'=> count($picking_confirmed),
		'picking_confirmed_inv'=>count($picking_confirmed_inv),
		'manifestgenerated' => $manifest_generated['total_manifest_generated'],
		'manifest_order' =>$manifest_order,
		'delivery_confirmed' => $delivery_confirmed['total_delivery_confirmed'],
		'total_sage_push'=> $total_push['total_inv'],
		'top_sales'=> (@$users[0]['first_name'] == '') ? '' : @$users[0]['first_name'],
		'top_sales_amount'=> @$users[0]['grand_total']-@$users[0]['tax_total'],
		'top_customer_sales'=> (@$top_sales[0]['name'] == '') ? '' : @$top_sales[0]['name'],
		'top_customer_amount'=> @$top_sales[0]['total_amount']-@	$top_sales[0]['tax_total']
	);
		// $result = array("data" => $data);

	echo json_encode($result);

}

public function get_dashboard_count_api()
{   
	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];	

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');	
	$token=$str;
	$this->load->database($token);

	if(@$reportrange=="")
	{
		$this->session->unset_userdata('start_date');
		$this->session->unset_userdata('end_date');
		$this->session->unset_userdata('push_invoice_start_date');
		$this->session->unset_userdata('push_invoice_end_date');
		$start_date = date('Y-m-d', strtotime('first day of this month')) . ' 00:00:00';
		$end_date   =  date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59';
		$startdate  = date('Y-m-d 00:00:00');
		$enddate    = date('Y-m-d 23:59:59');
	}else
	{
		$reportrange = $_POST['reportrange'];
		$arry = explode("-", $reportrange);
		$start_date = $arry[0];
		$end_date = $arry[1];
		$push_invoice_start_date = date("Y-m-d", strtotime($start_date));
		$push_invoice_end_date = date("Y-m-d", strtotime($end_date));

		$start_date = date("Y-m-d 00:00:00", strtotime($start_date));
		$end_date = date("Y-m-d 23:59:59", strtotime($end_date));
		$startdate = date("Y-m-d 00:00:00", strtotime($start_date));
		$enddate = date("Y-m-d 23:59:59", strtotime($end_date));

		$this->session->set_userdata('start_date', $start_date);
		$this->session->set_userdata('end_date', $end_date);
		$this->session->set_userdata('push_invoice_start_date', $push_invoice_start_date);
		$this->session->set_userdata('push_invoice_end_date', $push_invoice_end_date);
	}

		//Month bases
	$sales_order = $this->db->query("select sum(order_qty * unit_price) as grand_total ,SUM(item_tax) as item_tax FROM `sma_sale_items` WHERE sale_id in(select id from sma_sales where date BETWEEN '$start_date' AND '$end_date')")->row_array();
		//print_r($sales_order);exit();
	$delivered_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where date BETWEEN '$start_date' AND '$end_date' and sale_status ='Delivered'")->row_array();
	$sage_push_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where sale_status ='Delivered' and push_invoice='Y' and  date BETWEEN '$start_date' AND '$end_date'")->row_array();
	$pending_sage_push_invoice  =  $this->db->query("select SUM(total_tax) as tax_total,SUM(grand_total) as grand_total FROM `sma_sales` where sale_status ='Delivered' and push_invoice='N' and date BETWEEN '$start_date' AND '$end_date'")->row_array();

	$new_sales = $this->db->query("select count(id) as total_new_sale from sma_sales where  date >='$startdate' and date <'$enddate' and sale_status='New'")->row_array();
	$sale_accept = $this->db->query("select count(id) as total_accept_sale from sma_sales where  accept_date >='$startdate' and accept_date <'$enddate' ")->row_array();

	$picklistgenerated = $this->db->query("select count(sma_sales.id) as total_picker from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where print_on >='$startdate' and print_on <'$enddate' group by sma_sales.picklist_number")->result_array();

	$picker_count = $this->db->query("select count(id)as total_picker from sma_sales where print_on >='$startdate' and print_on <'$enddate'")->row_array();

	$picking_confirmed = $this->db->query("select sma_sales.id as total_picking from sma_sales left join sma_users on sma_users.id=sma_sales.picker_id where picked_date >='$startdate' and picked_date <'$enddate'group by sma_sales.picklist_number ")->result_array();

	$picking_confirmed_inv = $this->db->query("select id as total_picking_inv from sma_sales where picked_date >='$startdate' and picked_date <'$enddate'")->result_array();


	$manifest_generated = $this->db->query("select count(id) as total_manifest_generated from sma_manifest where  date >='$startdate' and date <'$enddate' and is_deleted='N' and intransit='Y' and is_manifest='Y'")->row_array();
	$total_manifest_order = $this->db->query("select print_id from sma_manifest where date >='$startdate' and date <'$enddate' and is_deleted='N' and intransit='Y' and is_manifest='Y'")->result_array();
	$manifest_order=0;

	foreach ($total_manifest_order as $r) {
		$print_ids = explode(',', $r['print_id']); 
		foreach ($print_ids as $da) {
			if (!empty($da)) { 
				$order = $this->db->query("SELECT id FROM sma_sales WHERE id = $da")->result_array();
				$manifest_order += count($order);
			}
		}
	}

	$delivery_confirmed = $this->db->query("select count(id) as total_delivery_confirmed from sma_manifest where delivered_status ='Y' and date >='$startdate' and date <'$enddate'")->row_array();

	$total_push = $this->db->query("select count(id) as total_inv from sma_sales where push_invoice_date >='$startdate' and push_invoice_date <'$enddate' and push_invoice='Y'")->row_array();

	$users = $this->db->query("select sma_users.id,first_name,last_name, email, company,sma_groups.name,sma_users.active,sum(sma_sales.grand_total) as grand_total ,SUM(sma_sales.total_tax) as tax_total from sma_users left join sma_groups on sma_users.group_id=sma_groups.id left join sma_sales on sma_sales.created_by=sma_users.id where sma_users.active='1' and sma_sales.date >='$startdate' and sma_sales.date <'$enddate' and company_id is null group by sma_users.id order by grand_total desc limit 1")->result_array(); 


	$top_sales= $this->db->query("select sma_companies.id, sma_companies.company as name,count(sma_sales.id) as total_orders, SUM(sma_sales.grand_total) as total_amount, SUM(sma_sales.total_tax) as tax_total FROM sma_sales LEFT JOIN sma_companies on sma_companies.id=sma_sales.customer_id WHERE sma_companies.group_name='customer' and sma_sales.date >='$startdate' and sma_sales.date <'$enddate' GROUP BY customer_id ORDER BY total_amount DESC LIMIT 1;")->result_array();
	$from_date = date("d-m-Y", strtotime($start_date));
	$to_date = date("d-m-Y", strtotime($end_date));

	$data = array(
		array(
			"title" => "Sales Order",
			"value" => moneyFormat(($sales_order['grand_total'] == '') ? 0 : $sales_order['grand_total']),
			"details" => 'VAT '.moneyFormat(($sales_order['item_tax'] == '') ? 0 : $sales_order['item_tax'])
		),
		array(
			"title" => "Delivered Invoice",
			"value" => moneyFormat($delivered_invoice['grand_total'] - $delivered_invoice['tax_total']),
			"details" => 'VAT '.moneyFormat(($delivered_invoice['tax_total'] == '') ? 0 : $delivered_invoice['tax_total'])
		),
		array(
			"title" => "Sage Push Invoice",
			"value" => moneyFormat($sage_push_invoice['grand_total'] - $sage_push_invoice['tax_total']),
			"details" => 'VAT '.moneyFormat(($sage_push_invoice['tax_total'] == '') ? 0 : $sage_push_invoice['tax_total'])
		),
		array(
			"title" => "Pending Sage Push Invoice",
			"value" => moneyFormat($pending_sage_push_invoice['grand_total'] - $pending_sage_push_invoice['tax_total']),
			"details" => 'VAT '.moneyFormat(($pending_sage_push_invoice['tax_total'] == '') ? 0 : $pending_sage_push_invoice['tax_total'])
		),
		array(
			"title" => "New Sales",
			"value" => $new_sales['total_new_sale'],
			"details" => ""
		),
		array(
			"title" => "Order Accepted",
			"value" => $sale_accept['total_accept_sale'],
			"details" => ""
		),
		array(
			"title" => "Picklist Generated",
			"value" => count($picklistgenerated) . '(' . $picker_count['total_picker'] . ')',
			"details" => ""
		),
		array(
			"title" => "Picking Confirmed",
			"value" => count($picking_confirmed) . '(' . count($picking_confirmed_inv) . ')',
			"details" => ""
		),
		array(
			"title" => "Manifest Generated",
			"value" => $manifest_generated['total_manifest_generated'] . '(' . $manifest_order . ')',
			"details" => ""
		),
		array(
			"title" => "Delivery Confirmed",
			"value" => $delivery_confirmed['total_delivery_confirmed'],
			"details" => ""
		),
		array(
			"title" => "Total Sage Push",
			"value" => $total_push['total_inv'],
			"details" => ""
		),
		array(
			"title" => "Top Sales",
			"value" => ($users[0]['first_name'] == '') ? '' : $users[0]['first_name'] . ' (' . moneyFormat(($users[0]['grand_total'] - $users[0]['tax_total'])) . ')',
			"details" => ""
		),
		array(
			"title" => "Max Value Order",
			"value" => ($top_sales[0]['name'] == '') ? '' : $top_sales[0]['name'] . ' (' . moneyFormat(($top_sales[0]['total_amount'] - $top_sales[0]['tax_total'])) . ')',
			"details" => ""
		)
	);

	$response_arr = array(
		"success" => true,
		"message" => "Data Found",
		"data" =>$data

	);

	echo json_encode($response_arr);
}



}