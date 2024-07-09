<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModuleReturn extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->load->admin_model('api_model');		
		$this->load->admin_model('companies_model');
		$this->load->admin_model('sales_model');
		$this->load->admin_model('products_model');
		$this->load->admin_model('returns_model');
		$this->load->admin_model('auth_model');
		$this->load->shop_model('shop_model');
		$this->load->model('site');
		$this->load->admin_model('settings_model');
		$this->digital_upload_path = 'files/';
		$this->upload_path = 'assets/uploads/';
		$this->thumbs_path = 'assets/uploads/thumbs/';
		$this->image_types = 'gif|jpg|jpeg|png|tif';
		$this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
		$this->allowed_file_size = '1024';
		$this->load->library('upload');
	}



public function returnList()
{
    $draw = intval($this->input->get("draw"));
    $start = intval($this->input->get("start"));
    $rowperpage = intval($this->input->get("length"));
    $search_arr = $this->input->get('search');
    $searchValue = $search_arr['value'];

    $query = "select sma_companies.accound_no, sma_companies.company, sma_returns.is_accept, sma_returns.id, sma_returns.date, sma_returns.reference_no, sma_returns.grand_total, sma_returns.total_items FROM sma_returns LEFT JOIN sma_companies ON sma_companies.id = sma_returns.customer_id WHERE sma_companies.company LIKE ? OR sma_returns.reference_no LIKE ? ORDER BY sma_returns.id DESC";

    $searchPattern = '%' . $searchValue . '%';

    $totalRecordswithFilter = $this->db->query($query, array($searchPattern, $searchPattern))->result();

    $this->db->limit($rowperpage, $start);
    $records = $this->db->query($query, array($searchPattern, $searchPattern))->result();

    $data1 = [];
    foreach ($records as $row) 
    {
        if ($row->is_accept == 'Y') {
            $status = 'Accept';
        } elseif ($row->is_accept == 'R') {
            $status = 'Reject';
        } elseif ($row->is_accept == 'N') {
            $status = 'Pending';
        }

      $data1[] = array(
		    "id" => $row->id, 
		    "is_accept" => $row->is_accept,   
		    "date" => date("d-m-Y", strtotime($row->date)),
		    "reference_no" => '<a href="' . admin_url() . 'returns/view/' . $row->id . '">' . $row->reference_no . '</a>',
		    "company" => $row->company,
		    "grand_total" => $row->grand_total, 
		    "total_items" => $row->total_items, 				
		    "status" => $status, 
		);

    }

    $response = array(
        "draw" => intval($draw),
        "recordsTotal" =>  count($totalRecordswithFilter),
        "recordsFiltered" => count($totalRecordswithFilter),
        "data" => $data1
    );        
    echo json_encode($response);
}


}