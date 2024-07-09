<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModulePurchase extends CI_Controller {
	
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


	
    public function grnlist() 
    {
        $secure_key=$this->input->request_headers();
        $token=$secure_key['authorization'];

        $this->load->helper('jwt_helper');
        $str = ltrim($token, 'Bearer ');
       //$token='e'.$str;
        $token=$str;

        //$user =jwt::decode($token,$this->config->item('jwt_key'));    
       //  $prefix=$user->prefix;
        $this->load->database($token);

        $draw = intval($this->input->get("draw"));
        $start = intval($this->input->get("start"));
        $rowperpage = intval($this->input->get("length"));
        $searchValue = $this->input->get('search')['value'];

        $totalRecordsQuery = "SELECT COUNT(*) as count FROM sma_purchases LEFT JOIN sma_companies ON sma_companies.id = sma_purchases.supplier_id";
        $totalRecordsResult = $this->db->query($totalRecordsQuery)->row();
        $totalRecords = $totalRecordsResult->count;

        $query = "SELECT 
        sma_purchases.date,
        sma_purchases.reference_no,
        sma_purchases.total,
        sma_purchases.product_tax,
        sma_purchases.order_tax,
        sma_purchases.grand_total,
        sma_purchases.id,
        sma_purchases.paid,
        sma_purchases.total_tax,
        sma_purchases.status,
        sma_companies.company
        FROM sma_purchases 
        LEFT JOIN sma_companies ON sma_companies.id = sma_purchases.supplier_id 
        WHERE sma_companies.company LIKE ? OR sma_purchases.reference_no LIKE ?
        ORDER BY sma_purchases.id DESC";

        $searchPattern = '%' . $searchValue . '%';

        $totalRecordswithFilterQuery = "SELECT COUNT(*) as count FROM sma_purchases 
        LEFT JOIN sma_companies ON sma_companies.id = sma_purchases.supplier_id
        WHERE sma_companies.company LIKE ? OR sma_purchases.reference_no LIKE ?";
        $totalRecordswithFilterResult = $this->db->query($totalRecordswithFilterQuery, array($searchPattern, $searchPattern))->row();
        $totalRecordswithFilter = $totalRecordswithFilterResult->count;

        $query .= " LIMIT $start, $rowperpage";

        $records = $this->db->query($query, array($searchPattern, $searchPattern))->result();

        $data = array();
        foreach ($records as $row) 
        {
            $data[] = array(
                "id" => $row->id,  
                "date" => date("d-m-Y", strtotime($row->date)),
                "reference_no" => $row->reference_no,
                "supplier" => $row->company,
                "paid" => $row->paid, 
                "total_tax" => $row->total_tax, 
                "grand_total" => $row->grand_total, 
                "status" => $row->status
            );
        }

        $response = array(
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordswithFilter,
            "data" => $data
        );        

        echo json_encode($response);
    }


    public function purchaseHistory() 
    {
        $secure_key=$this->input->request_headers();
        $token=$secure_key['authorization'];

        $this->load->helper('jwt_helper');
        $str = ltrim($token, 'Bearer ');
    //$token='e'.$str;
        $token=$str;

        //$user =jwt::decode($token,$this->config->item('jwt_key'));    
    //  $prefix=$user->prefix;
        $this->load->database($token);

        $draw = intval($this->input->get("draw"));
        $start = intval($this->input->get("start"));
        $rowperpage = intval($this->input->get("length"));
        $searchValue = $this->input->get('search')['value'];

        $totalRecordsQuery = "select COUNT(*) as count FROM sma_grn LEFT JOIN sma_companies ON sma_companies.id = sma_grn.supplier_id";
        $totalRecordsResult = $this->db->query($totalRecordsQuery)->row();
        $totalRecords = $totalRecordsResult->count;

        $query = "select 
        sma_grn.date,
        sma_grn.reference_no,
        sma_grn.total,
        sma_grn.product_tax,
        sma_grn.order_tax,
        sma_grn.grand_total,
        sma_grn.id,
        sma_grn.total_tax,
        sma_grn.status,
        sma_companies.company
        FROM sma_grn 
        LEFT JOIN sma_companies ON sma_companies.id = sma_grn.supplier_id 
        WHERE sma_companies.company LIKE ? OR sma_grn.reference_no LIKE ?
        ORDER BY sma_grn.id DESC";

        $searchPattern = '%' . $searchValue . '%';

        $totalRecordswithFilterQuery = "select COUNT(*) as count FROM sma_grn 
        LEFT JOIN sma_companies ON sma_companies.id = sma_grn.supplier_id
        WHERE sma_companies.company LIKE ? OR sma_grn.reference_no LIKE ?";
        $totalRecordswithFilterResult = $this->db->query($totalRecordswithFilterQuery, array($searchPattern, $searchPattern))->row();
        $totalRecordswithFilter = $totalRecordswithFilterResult->count;

        $query .= " LIMIT $start, $rowperpage";

        $records = $this->db->query($query, array($searchPattern, $searchPattern))->result();

        $data = array();
        foreach ($records as $row) 
        {
            $data[] = array(
                "id" => $row->id,  
                "date" => date("d-m-Y", strtotime($row->date)),
                "reference_no" => $row->reference_no,
                "supplier" => $row->company,
                "total_tax" => $row->total_tax, 
                "grand_total" => $row->grand_total
            );
        }

        $response = array(
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordswithFilter,
            "data" => $data
        );        

        echo json_encode($response);
    }



    public function stockTakeList()
    {
        $secure_key=$this->input->request_headers();
        $token=$secure_key['authorization'];

        $this->load->helper('jwt_helper');
        $str = ltrim($token, 'Bearer ');
    //$token='e'.$str;
        $token=$str;

        //$user =jwt::decode($token,$this->config->item('jwt_key'));    
    //  $prefix=$user->prefix;
        $this->load->database($token);

        $draw = intval($this->input->get("draw"));
        $start = intval($this->input->get("start"));
        $rowperpage = intval($this->input->get("length"));
        $searchValue = $this->input->get('search')['value'];
        $categories = $this->input->get("categories");
        $brands = $this->input->get("brands");
        $supplier = $this->input->get("supplier");

        $data = $this->db->query("SELECT id,start_date FROM `sma_stock_take_uniqueid` WHERE end_date IS NULL ORDER BY id DESC")->row_array(); 
        $uniqueid = $data['id'];

        $query = "SELECT
        COUNT(*) as total_records
        FROM
        sma_stock_take
        LEFT JOIN
        sma_products ON sma_products.id = sma_stock_take.product_id
        WHERE
        uniqueId = ?";

        $params = array($uniqueid);
        if (!empty($categories)) {
            $query .= " AND sma_products.category_id = ?";
            $params[] = $categories;
        }
        if (!empty($brands)) {
            $query .= " AND sma_products.brand = ?";
            $params[] = $brands;
        }
        if (!empty($supplier)) {
            $query .= " AND sma_products.supplier1 = ?";
            $params[] = $supplier;
        }

        $query .= " AND (sma_stock_take.code LIKE ? OR sma_stock_take.name LIKE ?)";

        $searchPattern = '%' . $searchValue . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;

        $total_records = $this->db->query($query, $params)->row()->total_records;

        $query_data = "SELECT
        sma_stock_take.id,
        sma_stock_take.code,
        sma_stock_take.name,
        sma_stock_take.size,
        sma_stock_take.price,
        sma_stock_take.cost,
        sma_stock_take.quantity,
        sma_stock_take.physical_qty,
        sma_stock_take.split_quantity,
        sma_stock_take.split_physical_qty,
        sma_stock_take.split_quantity - sma_stock_take.split_physical_qty AS piece_diff,
        sma_stock_take.bay,
        sma_stock_take.rack,
        sma_stock_take.remark
        FROM
        sma_stock_take
        LEFT JOIN
        sma_products ON sma_products.id = sma_stock_take.product_id
        WHERE
        uniqueId = ?";

        $params_data = array($uniqueid);
        if (!empty($categories)) {
            $query_data .= " AND sma_products.category_id = ?";
            $params_data[] = $categories;
        }
        if (!empty($brands)) {
            $query_data .= " AND sma_products.brand = ?";
            $params_data[] = $brands;
        }
        if (!empty($supplier)) {
            $query_data .= " AND sma_products.supplier1 = ?";
            $params_data[] = $supplier;
        }

        $query_data .= " AND (sma_stock_take.code LIKE ? OR sma_stock_take.name LIKE ?)
        ORDER BY
        sma_stock_take.id DESC
        LIMIT ?, ?";

        $params_data[] = $searchPattern;
        $params_data[] = $searchPattern;
        $params_data[] = $start;
        $params_data[] = $rowperpage;

        $records = $this->db->query($query_data, $params_data)->result();

        $data = [];
        foreach ($records as $row) {
            $data[] = array(
                "id" => $row->id,
                "name" =>$row->code .'-'. $row->name . '('.$row->size.')',
                "price" => $row->price,
                "cost" => $row->cost,
                "quantity" => $row->quantity,
                "physical_qty" => $row->physical_qty,
                "diff" => number_format($row->quantity - $row->physical_qty, 2),
                "piece_qty" => number_format($row->split_quantity, 2),
                "physical_piece_qty" => number_format($row->split_physical_qty, 2),
                "piece_diff" => number_format($row->split_quantity - $row->split_physical_qty, 2),
                "bay" => $row->bay,
                "rack" => $row->rack,
                "remark" => $row->remark,
                "uniqueid" => $uniqueid,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $total_records,
            "recordsFiltered" => $total_records,
            "data" => $data
        );

        echo json_encode($response);
    }


    public function update_stock_take_product()
    {

        extract($_POST);
        $secure_key=$this->input->request_headers();
        $token=$secure_key['authorization'];

        $this->load->helper('jwt_helper');
        $str = ltrim($token, 'Bearer ');
        $token=$str;  
        $this->load->database($token);


        $date = date('Y-m-d');  
        $update_data = array("end_date" => $date);
        $this->db->where('id', $uniqueId);
        $this->db->update('sma_stock_take_uniqueid', $update_data);
        $response = array();
        $selectedIds2 = $this->db->query("SELECT physical_qty, split_physical_qty, product_id FROM sma_stock_take")->result_array();

        foreach ($selectedIds as $id) 
        {        
            $physical_qty = $id['physical_qty'];
            $split_physical_qty = $id['split_physical_qty'];
            $product_id = $id['product_id'];

            $data = array(
                "quantity" => $physical_qty,
                "split_quantity" => $split_physical_qty,
            );

            $this->db->where('id', $product_id);
            $this->db->update('sma_products', $data);       
            $response[] = array(
                'product_id' => $product_id,
                'quantity_updated' => $physical_qty,
                'split_quantity_updated' => $split_physical_qty,
            );
        }


        $this->session->set_flashdata('success', 'Stock update successfully');    
        $json_response = array(
            'success' => true,
            'message' => 'Stock update successfully',
            'updated_products' => $response
        );


        header('Content-Type: application/json');
        echo json_encode($json_response);
    }


    public function start_stock_take()
    {
        $secure_key=$this->input->request_headers();
        $token=$secure_key['authorization'];

        $this->load->helper('jwt_helper');
        $str = ltrim($token, 'Bearer ');
    //$token='e'.$str;
        $token=$str;

        //$user =jwt::decode($token,$this->config->item('jwt_key'));    
    //  $prefix=$user->prefix;
        $this->load->database($token);

        $response = array();

        $date = date('Y-m-d');
        $uniqueId = date('YmdHis');
        $start_data = array("start_date" => $date, 'uniqueStockId' => $uniqueId);
        $this->db->insert('sma_stock_take_uniqueid', $start_data);
        $uid = $this->db->insert_id();
        $uniqueStockId = $uniqueId . $uid;
        $update_data = array("uniqueStockId" => $uniqueStockId);
        $this->db->where('id', $uid);
        $this->db->update('sma_stock_take_uniqueid', $update_data);

        $products = $this->db->query("select id,code,name,image,quantity,category_id,subcategory_id,size,price,cost,bay,rack,inner_ean_number,outer_ean_number,inner_ean_number,split,split_quantity from sma_products where status='1'")->result_array();

        foreach ($products as $row) {

            $product_id = $row['id'];
            $code = $row['code'];
            $name = $row['name'];
            $image = $row['image'];
            $quantity = $row['quantity'];
            $category_id = $row['category_id'];
            $subcategory_id = $row['subcategory_id'];
            $size = $row['size'];
            $price = $row['price'];
            $cost = $row['cost'];
            $bay = $row['bay'];
            $rack = $row['rack'];
            $outer_ean_number = $row['outer_ean_number'];
            $inner_ean_number = $row['inner_ean_number'];
            $split = $row['split'];
            $split_quantity = $row['split_quantity'];
            $date = date('Y-m-d');

            $data = array(
                "product_id" => $product_id,
                "code" => $code,
                "name" => $name,
                "image" => $image,
                "quantity" => $quantity,
                "split_quantity" => $split_quantity,
                "category_id" => $category_id,
                "subcategory_id" => $subcategory_id,
                "size" => $size,
                "price" => $price,
                "cost" => $cost,
                "bay" => $bay,
                "rack" => $rack,
                "outer_ean_number" => $outer_ean_number,
                "inner_ean_number" => $inner_ean_number,
                "date" => $date,
                "split" => $split,
                'uniqueStockId' => $uniqueStockId,
                'uniqueId' => $uid,
            );

            $this->db->insert('sma_stock_take', $data);
        }

    $response['success'] = true; // Indicate success
    $response['message'] = 'Stock take started successfully'; // Success message

    // Send JSON response
    $this->output->set_content_type('application/json')->set_output(json_encode($response));
}

public function update_remark()
{
    $secure_key=$this->input->request_headers();
    $token=$secure_key['authorization'];

    $this->load->helper('jwt_helper');
    $str = ltrim($token, 'Bearer ');
    //$token='e'.$str;
    $token=$str;

        //$user =jwt::decode($token,$this->config->item('jwt_key'));    
    //  $prefix=$user->prefix;
    $this->load->database($token);

    $response = array(); // Initialize response array

    extract($_POST);

    $val = explode(",", $product_id);

    if (!empty($product_id)) {
        for ($i = 0; $i < count($val); $i++) {
            $id = $val[$i];

            $data = array(
                "remark" => $remark,
            );

            $this->db->where('id', $id);
            $result = $this->db->update('sma_stock_take', $data);

            if (!$result) {
                // If update fails for any product, set response as failure and break the loop
                $response['success'] = false;
                $response['message'] = 'Failed to update remark for one or more products.';
                echo json_encode($response);
                return;
            }
        }

        // If all updates are successful
        $response['success'] = true;
        $response['message'] = 'Stock update successful';
    } else {
        // If no product selected
        $response['success'] = false;
        $response['message'] = 'Please select at least one product.';
    }

    echo json_encode($response);
}


}
