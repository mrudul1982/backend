<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, authorization");

class WebApiModuleMaster extends CI_Controller {
	
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

public function getDriver()
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
	$this->load->model('route_model');
	$data['driver'] = $this->route_model->getAlldriver();
	$data['manifast'] = $this->db->query("select vehicle_id,driver_id from sma_manifest where manifest_id='$manifest_id'")->row_array();
	if(count($data['driver']) > 0)
	{
		$response_arr = array(
			'data' => $data,
			'success' => true,
			'message' => 'Driver details found'
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Driver details not found',
		);
		echo json_encode($response_arr);
	} 

}





}