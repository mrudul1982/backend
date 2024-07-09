<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'vendor/autoload.php'; // Include the JWT library
require_once APPPATH . '/libraries/JWT.php';
use \Firebase\JWT\JWT; // Import the JWT class

class Login extends CI_Controller {

	public function __construct() {
		parent::__construct();
      //  $this->load->model('api_model'); // Corrected method name: `model` instead of `admin_model`
       // ini_set('session.gc_maxlifetime', 30*60);
       // session_start();
		 $this->load->library('session');
	}


	public function index() 
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		if (empty($username) || empty($password)) 
		{
			$response_arr = [
				'success' => false,
				'message' => 'Invalid credentials',
			];
			echo json_encode($response_arr);
			return;
		}
		$prefix = substr($username, 0, 3);
		$database_loaded =$this->switchDatabaseConnection($prefix);
		if ($database_loaded) 
		{

			$username= substr($username, 4,30);	

			$this->load->model('auth_model');
			$this->load->model('api_model');
			$pass = $this->auth_model->login2($username, $password);

			$config_key = $prefix . '_url';
		    $url = $this->config->item($config_key);
			$result = $this->api_model->get_login($username, $password);
			if(!empty($result))
			{
				$result['avatar'] =$url.'/assets/uploads/thumbs/'. $result['avatar'];
			}
			if (!empty($result))
			{
				$user_id = @$result['id']; 
				$role = $result['group_id'];

        	// Retrieve accessible roles
				$accessible = $this->db->query("select sma_groups.id AS role_id, sma_groups.name FROM sma_role_assign LEFT JOIN sma_users ON sma_role_assign.user_id = sma_users.id LEFT JOIN sma_groups ON sma_groups.id = sma_role_assign.role_id WHERE sma_role_assign.user_id = '$user_id'")->result_array();

				if (empty($accessible)) 
				{
            // If no accessible roles found, retrieve the user's role
					$data_role = $this->db->query("select id AS role_id, name FROM sma_groups WHERE id = '$role'")->result_array();
					$role_name = $data_role[0]['name'];
					$accessible[] = ['role_id' => $role, 'name' => $role_name];
				}

				if (!empty($pass)) {
            // Generate JWT token
					$token['id'] = $result['id'];
					$token['mobile'] = $result['username'];
					$token['role'] = $role;
					$token['prefix'] = $prefix;

					$date = new DateTime();
					$token['iat'] = $date->getTimestamp();
					$token['exp'] = $date->getTimestamp() + 60 * 60 * 5; 

					$response_arr = [
						'success' => true,
						'message' => 'Logged In Success',
						'role' => $role,
						'profile_details' => $result,
						'accessible_role' => $accessible,
						'token' => JWT::encode($token, $this->config->item('jwt_key')),
					];

					$this->session->set_userdata('token', JWT::encode($token, $this->config->item('jwt_key')));
				} else {
            // Invalid credentials
					$response_arr = [
						'success' => false,
						'message' => 'Invalid Credentials',
					];
				}
			} else {
        	// Invalid credentials
				$response_arr = [
					'success' => false,
					'message' => 'Invalid Credentials',
				];
			}

			echo json_encode($response_arr);


		}else{
			$response = array(
				'success' => false,
				'message' => 'Failed to connect to the database',
			);
			echo json_encode($response);
		}
	}



	private function switchDatabaseConnection($prefix) 
	{

		switch ($prefix) 
		{
			case 'dds':
			case 'tsc':
			case 'kar':
			case 'ebu':
			case 'dem':
			break;
			default:
			return false;
		}

        // Load the appropriate database configuration based on the client's username
		try 
		{

			$this->session->set_userdata('db_prefix', $prefix);

			$this->load->database($prefix);
			return true;
		} catch (Exception $e) 
		{
			return false;
		}
	}

	
	public function logout()
	{
		$secure_key=$this->input->request_headers();

		$token=$secure_key['authorization'];
		$this->load->helper('jwt_helper');
		$str = ltrim($token, 'Bearer ');
		$token='e'.$str;
		$jwtString =$token;
      // $token =  JWT::destroy($token, $this->config->item('jwt_key'));
       //echo $token; exit();
       //echo $jwtString;exit();
       // $EXAMPLE_JWT_SECRET_KEY = '********';
        //$EXAMPLE_JWT_ENCODE_ALG = 'HS256';
         //if (property_exists(JWT::class, 'leeway')) {
      //  JWT::$leeway = max(JWT::$leeway, 60);
   		// }

	    //$token = JWT::decode($jwtString, $EXAMPLE_JWT_SECRET_KEY [$EXAMPLE_JWT_ENCODE_ALG ]);
	    //echo $token;exit();
		if($secure_key!=''){
			
			$response_arr = array(
				'success' => true,
				'message' => 'Logout Success',
			);
			echo json_encode($response_arr);
			
		}else{
			$response_arr = array(
				'success' => false,
				'message' => 'Token empty',
			);
			echo json_encode($response_arr);
		}
		
		
	}

}
?>
