<?php
class Web_Services extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
		header('Access-Control-Allow-Headers: Content-Type, authorization');

		
		// $prefix = $this->session->userdata('db_prefix');
		// $this->load->database($prefix);
		// $this->load->model('api_model');		
		// $this->load->model('companies_model');
		// $this->load->model('sales_model');
		// $this->load->model('products_model');
		// $this->load->model('returns_model');
		$this->load->model('auth_model');
		// $this->load->model('shop_model');
		$this->load->model('site');
		// $this->load->model('settings_model');
		// $this->digital_upload_path = 'files/';
		// $this->upload_path = 'assets/uploads/';
		// $this->thumbs_path = 'assets/uploads/thumbs/';
		$this->image_types = 'gif|jpg|jpeg|png|tif';
		$this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
		$this->allowed_file_size = '1024';
		$this->load->library('upload');
		
	}

	public function checkAppVersion($appVersion = null) 
	{
		$appVersion = $this->input->post('appVersion');
		$platform = $this->input->post('platform');
		if($platform=='android')
		{
			if ($appVersion == '4.14' || $appVersion == '4.15' || $appVersion == '4.16' || $appVersion == '4.17') 
			{
				$response_arr = array(
					'success' => false,
					'message' => 'No new version Available',
				);
				echo json_encode($response_arr);
			}
			else
			{
				$response_arr = array(
					'success' => true,
					'message' => 'Hello, we have got some new and exiting features! Please update your app.',
				);
				echo json_encode($response_arr);
			}
		}
		elseif ($platform == 'ios') 
		{
			if ($appVersion == '4.14' || $appVersion == '4.15' || $appVersion == '4.16' || $appVersion == '4.17') 
			{
				$response_arr = array(
					'success' => false,
					'message' => 'No new version Available',
				);
				echo json_encode($response_arr);
			}
			else
			{
				$response_arr = array(
					'success' => true,
					'message' => 'Hello, we have got some new and exiting features! Please update your app.',
				);
				echo json_encode($response_arr);
			}
		}
	}

	public function place_order() 
	{
		extract($_POST);


		$secure_key = $this->input->request_headers();
		$token = $secure_key['authorization'];
		$this->load->helper('jwt_helper');
		$str = ltrim($token, 'Bearer ');
		$token = 'e' . $str;
		$user = jwt::decode($token, $this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
		$this->load->model('sales_model');
		$this->load->model('site');
		if ($prefix == 'tsc') {
			$this->tsc_place_order();
		}else{

			if ($user->role == 'customer') 
			{
				$created_by = $sales_product['customer_id'];
			} else 
			{
				$created_by =  $user->id;
			}
			$sales_product = json_decode(file_get_contents('php://input'), true);

			$json_input = file_get_contents('php://input');

			$customer_id = $sales_product['customer_id'];
			$customer_address = $sales_product['customer_address'];
			$totalAmount = $sales_product['totalAmount'];
			$totalvat = $sales_product['totalVAT'];
			$paymentMode = $sales_product['paymentMode'];
			$collect_amount = @$sales_product['collect_amount'];

			$promotionId = $sales_product['promotionId'];
			$discountAmount = $sales_product['discountAmount'];
			$promotionSubType = $sales_product['promotionSubType'];
			$uniqueOrderId = $sales_product['uniqueOrderId'];
			$invoiceWiseDiscountPercentage = $sales_product['invoiceWiseDiscountPercentage'];
			$invoiceWiseDiscountAmount = $sales_product['invoiceWiseDiscountAmount'];
			$date = date('Y-m-d H:i:s');              

			$insert_data = array(
				'unique_order_id' => $uniqueOrderId,
				'customer_id' => $customer_id,
				'date' => $date,
				'json_data' => $json_input 
			);

			$this->db->insert('sma_order_logs', $insert_data);

			if (!empty($customer_id) && !empty($customer_address))
			{
				$check_exit_or_not = $this->db->query("select id from sma_sales where uniqueOrderId='$uniqueOrderId'")->result_array();

				if(count($check_exit_or_not)==0)
				{ 
					$reference = $this->input->post('reference_no') ? $this->input->post('reference_no') : $this->site->getReference('so');

					$date = date('Y-m-d H:i:s');

					$customer_id = $customer_id;
					$biller_id = 29;
					$total_items = $sales_product['product_count'];
					$sale_status = $this->input->post('sale_status');
					$payment_status = $sales_product['paymentMode'];
					$payment_term = $this->input->post('payment_term');
					$po_number = $this->input->post('po_number');

					$chequeNo = $sales_product['chequeNo'];
					$chequeDate = $sales_product['chequeDate'];
					$latitude = @$sales_product['latitude'];
					$longitude = @$sales_product['longitude'];

					if ($po_number == '') 
					{
						$po_number = 0;
					}
					$due_date = $payment_term ? date('Y-m-d', strtotime('+' . $payment_term . ' days', strtotime($date))) : null;

					$shipping = $this->input->post('shipping') ? $this->input->post('shipping') : 0;
					$customer_details = $this->site->getCompanyByID($customer_id);
			//print_r($customer_details);exit();
					$customer = !empty($customer_details->company) && $customer_details->company != '-' ? $customer_details->company : $customer_details->name;

					$note = $this->sma->clear_tags($this->input->post('note'));
					$staff_note = $this->sma->clear_tags($sales_product['staff_note']);
					$note_for_driver = $this->sma->clear_tags($sales_product['note_for_driver']);
					$quote_id = $this->input->post('quote_id') ? $this->input->post('quote_id') : null;
					$default_delivery=$customer_details->default_delivery;
					$deliverydate='';
					if($default_delivery == 1)
					{
						$deliverydate=Date('Y-m-d', strtotime('+3 days'));

					}elseif($default_delivery == 2)
					{
						$deliverydate=Date('Y-m-d', strtotime('+1 days'));

					}elseif($default_delivery == 3)
					{
						$deliverydate=Date('Y-m-d', strtotime('+7 days'));
					}
					$total = 0;
					$product_tax = 0;
					$product_discount = 0;
					$digital = false;
					$gst_data = [];
					$total_cgst = $total_sgst = $total_igst = 0;
					$subtotal=0;$total_tax=0;$novat=0;$stdgoods=0;
					foreach ($sales_product['sale_details'] as $key => $value) 
					{
						$pid = $value['product_id'];
						$order_type = $value['quantityType'];
						$product = $this->db->query("select * from sma_products where id='$pid'")->row_array();

						if($order_type=='piece')
						{

							$pieceproduct = $this->db->query("select id,code from sma_products where parent_id='$pid'")->result_array();
							if(count($pieceproduct)!=0)
							{
								$item_id = $pieceproduct[0]['id'];
								$item_code = $pieceproduct[0]['code'];
							}else
							{
								$item_id = $product['id'];
								$item_code = $product['code'];
							}


						}else
						{
							$item_id = $product['id'];
							$item_code = $product['code'];
						}

						$item_type = $product['type'];
						$item_name = $product['name'];
						$item_option = isset($_POST['product_option']) && $_POST['product_option'] != 'false' && $_POST['product_option'] != 'null' ? $_POST['product_option'] : null;
						$real_unit_price = $this->sma->formatDecimal($value['unit_price'],2);
						$unit_price = $this->sma->formatDecimal($value['unit_price'],2);
						$item_unit_quantity = $value['quantity'];
						$get_tax = $value['vat'];
						$get_subtotal = $value['subTotal'];
						$item_serial = $_POST['serial'] ?? '';
						$item_tax_rate = $product['tax_rate'] ?? null;
						$item_discount = $_POST['product_discount'] ?? null;
						$item_unit = $product['unit'];
						$item_quantity = $value['quantity'] ?? null;
						$is_promoted = $value['isPromoted'] ?? 0;
						$promo_id = $value['promo_id'] ?? 0;
						$productWiseDiscountPercentage = $value['productWiseDiscountPercentage'];
						$productWiseDiscountAmount = $value['productWiseDiscountAmount'];
						$tdiscount =0;
						if (isset($item_code) && isset($real_unit_price) && isset($unit_price) && isset($item_quantity)) 
						{
							$item_net_price = $unit_price;
				//	$subtotal = (($item_net_price * $item_unit_quantity));

							if ($productWiseDiscountPercentage!='0.0'){

								$tdiscount = $unit_price * ($productWiseDiscountPercentage / 100);
								$item_discount = $tdiscount * $item_quantity;
								$tsum =($unit_price * $item_quantity)-$item_discount;
                    //echo $item_tax_rate;exit();
								$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$item_tax_rate'")->row_array();

								$get_tax = ($tsum * $tax_parcent['rate']) / 100;
								if ($item_tax_rate=='0'){

									$novat+=$tsum;
								}
								if ($item_tax_rate=='4'){

									$stdgoods+=$tsum;
								}
							}else{

								$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$item_tax_rate'")->row_array();
								$get_tax = (($unit_price*$item_quantity) * $tax_parcent['rate']) / 100;
                         //$tax_rate = $tax_parcent['rate'];
								$item_discount='0';
								if ($item_tax_rate=='0'){

									$novat+=$unit_price*$item_quantity;
								}
								if ($item_tax_rate=='4'){

									$stdgoods+=$unit_price*$item_quantity;
								}
							}




							$unit = $this->site->getUnitByID($item_unit);
							$product = [
								'product_id' => $item_id,
								'product_code' => $item_code,
								'product_name' => $item_name,
								'product_type' => $item_type,
								'option_id' => $item_option,
								'net_unit_price' => $item_net_price-$tdiscount,
								'unit_price' => $item_net_price-$tdiscount,
								'quantity' => $item_quantity,
								'accept_qty' => $item_quantity,
								'accept_amt' =>$unit_price*$item_quantity,
								'product_unit_id' => $unit ? $unit->id : null,
								'product_unit_code' => $unit ? $unit->code : null,
								'unit_quantity' => $item_unit_quantity,
								'warehouse_id' => '0',
								'item_tax' => $get_tax,
								'tax_rate_id' => $item_tax_rate,
								'tax' => $get_tax,
								'discount' => $productWiseDiscountPercentage,
								'item_discount' => $item_discount,
								'subtotal' => $get_subtotal,
								'serial_no' => $item_serial,
								'real_unit_price' => $real_unit_price,
								'order_type' => $order_type,
								'order_qty' =>$item_quantity,
								'is_promoted' => $is_promoted,
								'promo_id' => $promo_id,

							];

							$products[] = ($product + $gst_data);

							$total=$totalAmount;
							if($item_discount == 0){
								$subtotal+=$item_net_price*$item_quantity;
							}else{
								$subtotal+=$item_net_price*$item_quantity-$item_discount;
							}

							$total_tax+=$get_tax;
						}
					}


					$grand_total=$subtotal+$total_tax;
					$over_all_amount='';
					if ($invoiceWiseDiscountPercentage!='0.0') {

						$tdiscount = $stdgoods * $invoiceWiseDiscountPercentage/100;
						$tdiscount1 = $novat * $invoiceWiseDiscountPercentage/100; 

						$total = $stdgoods - $tdiscount;
						$total_tax =($total * 20) / 100;
						$novat1 = $novat - $tdiscount1;
						$over_all_amount=$tdiscount+$tdiscount1;
						$grand_total =  $total + $total_tax + $novat1;
					}

					$data = [
						'date' => $date,
						'reference_no' => $reference,
						'customer_id' => $customer_id,
						'customer' => $customer,
						'biller_id' => 0,
						'biller' => 0,
						'warehouse_id' => 1,
						'note' => $note,
						'staff_note' => $staff_note,
						'total' => $total,
						'product_discount' => 0,

						'order_discount_id' => $promotionId,
						'order_discount' => $discountAmount,
						'total_discount' => $discountAmount,
						'subtype' => $promotionSubType,

						'over_all_discount'=>$invoiceWiseDiscountPercentage,
						'over_all_amount'=>$over_all_amount,
						'discount_type' => 'percentage',
						'product_tax' => $total_tax,
						'order_tax_id' => $this->input->post('order_tax'),
						'order_tax' => @$order_tax,
						'total_tax' => $total_tax,
						'shipping' => $this->sma->formatDecimal($shipping),
						'grand_total' => $this->sma->formatDecimal($grand_total,2),
						'total_items' => $total_items,
						'sale_status' => 'New',
						'payment_status' => 'Due',
						'payment_method' => $paymentMode,
						'payment_term' => $payment_term,
						'due_date' => $due_date,
						'paid' => 0,
						'print' => 'N',
						'created_by' => $created_by,
						'hash' => hash('sha256', microtime() . mt_rand()),
						'po_number' => $po_number,
						'note_for_driver' => $note_for_driver,
						'deliverydate' => $deliverydate,
						'cheque_number' => $chequeNo,
						'cheque_date' => $chequeDate,
						'longitude' => $longitude,
						'latitude' => $latitude,
						'uniqueOrderId'=> $uniqueOrderId

					];
			//print_r($data);exit();
					$payment = [];
					$this->sales_model->addSale($data, $products, $payment, [],$collect_amount);

					$response_arr = array(
						'success' => true,
						'message' => 'Order Places sucessfully',
					);
					echo json_encode($response_arr);
				}else
				{
					$response_arr = array(
						'success' => true,
						'message' => 'Order alerady Places. Please check in order List',
					);
					echo json_encode($response_arr);
				}

			} else 
			{
				$response_arr = array(
					'success' => false,
					'message' => 'Unable to place order',
				);
				echo json_encode($response_arr);
			}
		}
	}


	public function tsc_place_order()
	{
		extract($_POST);

		$secure_key = $this->input->request_headers();
		$token = $secure_key['authorization'];
		$this->load->helper('jwt_helper');
		
		$str = ltrim($token, 'Bearer ');
		$token='e'.$str;
		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
		$this->load->model('site');
		if ($user->role == 'customer') 
		{
			$created_by = $sales_product['customer_id'];
		} else 
		{
			$created_by =  $user->id;
		}
		$sales_product = json_decode(file_get_contents('php://input'), true);
		
		$customer_id = $sales_product['customer_id'];
		$customer_address = $sales_product['customer_address'];
		$totalAmount = $sales_product['totalAmount'];
		$totalvat = $sales_product['totalVAT'];
		$paymentMode = $sales_product['paymentMode'];
		$collect_amount = @$sales_product['collect_amount'];
		
		$promotionId = $sales_product['promotionId'];
		$discountAmount = $sales_product['discountAmount'];
		$promotionSubType = $sales_product['promotionSubType'];
		$uniqueOrderId = $sales_product['uniqueOrderId'];
		$invoiceWiseDiscountPercentage = $sales_product['invoiceWiseDiscountPercentage'];
		$invoiceWiseDiscountAmount = $sales_product['invoiceWiseDiscountAmount'];
		
		if (!empty($customer_id) && !empty($customer_address))
		{
			$check_exit_or_not = $this->db->query("select id from sma_sales where uniqueOrderId='$uniqueOrderId'")->result_array();
			
			if(count($check_exit_or_not)==0)
			{ 
				$reference = $this->input->post('reference_no') ? $this->input->post('reference_no') : $this->site->getReference('so');

				$date = date('Y-m-d H:i:s');
				
				$customer_id = $customer_id;
				$biller_id = 29;
				$total_items = $sales_product['product_count'];
				$sale_status = $this->input->post('sale_status');
				$payment_status = $sales_product['paymentMode'];
				$payment_term = $this->input->post('payment_term');
				$po_number = $this->input->post('po_number');
				
				$chequeNo = $sales_product['chequeNo'];
				$chequeDate = $sales_product['chequeDate'];
				
				if ($po_number == '') 
				{
					$po_number = 0;
				}
				$due_date = $payment_term ? date('Y-m-d', strtotime('+' . $payment_term . ' days', strtotime($date))) : null;
				
				$shipping = $this->input->post('shipping') ? $this->input->post('shipping') : 0;
				$customer_details = $this->site->getCompanyByID($customer_id);

				$customer = !empty($customer_details->company) && $customer_details->company != '-' ? $customer_details->company : $customer_details->name;

				$note = $this->sma->clear_tags($this->input->post('note'));
				$staff_note = $this->sma->clear_tags($sales_product['staff_note']);
				$quote_id = $this->input->post('quote_id') ? $this->input->post('quote_id') : null;
				$default_delivery=$customer_details->default_delivery;
				$deliverydate='';
				if($default_delivery == 1)
				{
					$deliverydate=Date('Y-m-d', strtotime('+3 days'));

				}elseif($default_delivery == 2)
				{
					$deliverydate=Date('Y-m-d', strtotime('+1 days'));

				}elseif($default_delivery == 3)
				{
					$deliverydate=Date('Y-m-d', strtotime('+7 days'));
				}
				$total = 0;
				$product_tax = 0;
				$product_discount = 0;
				$digital = false;
				$gst_data = [];
				$total_cgst = $total_sgst = $total_igst = 0;
				$subtotal=0;$total_tax=0;$novat=0;$stdgoods=0;
				foreach ($sales_product['sale_details'] as $key => $value) 
				{
					$pid = $value['product_id'];
					$order_type = $value['quantityType'];
					$product = $this->db->query("select * from sma_products where id='$pid'")->row_array();
					
					if($order_type=='piece')
					{
						
						$pieceproduct = $this->db->query("select id,code from sma_products where parent_id='$pid'")->result_array();
						if(count($pieceproduct)!=0)
						{
							$item_id = $pieceproduct[0]['id'];
							$item_code = $pieceproduct[0]['code'];
						}else
						{
							$item_id = $product['id'];
							$item_code = $product['code'];
						}
						
						
					}else
					{
						$item_id = $product['id'];
						$item_code = $product['code'];
					}
					
					$item_type = $product['type'];
					$item_name = $product['name'];
					$box_cost  = $product['cost'];
					$piece_cost = $product['piece_cost'];

					$item_option = isset($_POST['product_option']) && $_POST['product_option'] != 'false' && $_POST['product_option'] != 'null' ? $_POST['product_option'] : null;
					$real_unit_price = $this->sma->formatDecimal($value['unit_price'],2);
					$unit_price = $this->sma->formatDecimal($value['unit_price'],2);
					$item_unit_quantity = $value['quantity'];
					$get_tax = $value['vat'];
					$get_subtotal = $value['subTotal'];
					$item_serial = $_POST['serial'] ?? '';
					$item_tax_rate = $product['tax_rate'] ?? null;
					$item_discount = $_POST['product_discount'] ?? null;
					$item_unit = $product['unit'];
					$item_quantity = $value['quantity'] ?? null;
					$is_promoted = $value['isPromoted'] ?? 0;
					$promo_id = $value['promo_id'] ?? 0;
					$productWiseDiscountPercentage = $value['productWiseDiscountPercentage'];
					$productWiseDiscountAmount = $value['productWiseDiscountAmount'];
					
					if (isset($item_code) && isset($real_unit_price) && isset($unit_price) && isset($item_quantity)) 
					{
						$item_net_price = $unit_price;
			//	$subtotal = (($item_net_price * $item_unit_quantity));

						if ($productWiseDiscountPercentage!='0.0'){

							$tdiscount = $unit_price * $productWiseDiscountPercentage/100;
							$item_discount = $tdiscount * $item_quantity;
							$tsum =($unit_price * $item_quantity)-$item_discount;
                //echo $item_tax_rate;exit();
							$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$item_tax_rate'")->row_array();
							
							$get_tax = ($tsum * $tax_parcent['rate']) / 100;
							if ($item_tax_rate=='0'){
								
								$novat+=$tsum;
							}
							if ($item_tax_rate=='4'){
								
								$stdgoods+=$tsum;
							}
						}else{

							$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$item_tax_rate'")->row_array();
							$get_tax = (($unit_price*$item_quantity) * $tax_parcent['rate']) / 100;
                     //$tax_rate = $tax_parcent['rate'];
							$item_discount='0';
							if ($item_tax_rate=='0'){
								
								$novat+=$unit_price*$item_quantity;
							}
							if ($item_tax_rate=='4'){
								
								$stdgoods+=$unit_price*$item_quantity;
							}
						}




						$unit = $this->site->getUnitByID($item_unit);
						$product = [
							'product_id' => $item_id,
							'product_code' => $item_code,
							'product_name' => $item_name,
							'product_type' => $item_type,
							'option_id' => $item_option,
							'box_cost' => $box_cost,
							'piece_cost' => $piece_cost,

							'net_unit_price' => $item_net_price,
							'unit_price' => $item_net_price,
							'quantity' => $item_quantity,
							'product_unit_id' => $unit ? $unit->id : null,
							'product_unit_code' => $unit ? $unit->code : null,
							'unit_quantity' => $item_unit_quantity,
							'warehouse_id' => '0',
							'item_tax' => $get_tax,
							'tax_rate_id' => $item_tax_rate,
							'tax' => $get_tax,
							'discount' => $productWiseDiscountPercentage,
							'item_discount' => $item_discount,
							'subtotal' => $get_subtotal,
							'serial_no' => $item_serial,
							'real_unit_price' => $real_unit_price,
							'order_type' => $order_type,
							'order_qty' =>$item_quantity,
							'is_promoted' => $is_promoted,
							'promo_id' => $promo_id,

						];

						$products[] = ($product + $gst_data);
						
						$total=$totalAmount;
						if($item_discount == 0){
							$subtotal+=$item_net_price*$item_quantity;
						}else{
							$subtotal+=$item_net_price*$item_quantity-$item_discount;
						}
						
						$total_tax+=$get_tax;
					}
				}
				

				$grand_total=$subtotal+$total_tax;
				$over_all_amount='';
				if ($invoiceWiseDiscountPercentage!='0.0') {

					$tdiscount = $stdgoods * $invoiceWiseDiscountPercentage/100;
					$tdiscount1 = $novat * $invoiceWiseDiscountPercentage/100; 

					$total = $stdgoods - $tdiscount;
					$total_tax =($total * 20) / 100;
					$novat1 = $novat - $tdiscount1;
					$over_all_amount=$tdiscount+$tdiscount1;
					$grand_total =  $total + $total_tax + $novat1;
				}

				$data = [
					'date' => $date,
					'reference_no' => $reference,
					'customer_id' => $customer_id,
					'customer' => $customer,
					'biller_id' => 0,
					'biller' => 0,
					'warehouse_id' => 1,
					'note' => $note,
					'staff_note' => $staff_note,
					'total' => $total,
					'product_discount' => 0,
					
					'order_discount_id' => $promotionId,
					'order_discount' => $discountAmount,
					'total_discount' => $discountAmount,
					'subtype' => $promotionSubType,

					'over_all_discount'=>$invoiceWiseDiscountPercentage,
					'over_all_amount'=>$over_all_amount,
					'discount_type' => 'percentage',
					'product_tax' => $total_tax,
					'order_tax_id' => $this->input->post('order_tax'),
					'order_tax' => @$order_tax,
					'total_tax' => $total_tax,
					'shipping' => $this->sma->formatDecimal($shipping,2),
					'grand_total' => $this->sma->formatDecimal($grand_total,2),
					'total_items' => $total_items,
					'sale_status' => 'New',
					'payment_status' => 'Due',
					'payment_method' => $paymentMode,
					'payment_term' => $payment_term,
					'due_date' => $due_date,
					'paid' => 0,
					'print' => 'N',
					'created_by' => $created_by,
					'hash' => hash('sha256', microtime() . mt_rand()),
					'po_number' => $po_number,
					'staff_note' => $staff_note,
					'deliverydate' => $deliverydate,
					'cheque_number' => $chequeNo,
					'cheque_date' => $chequeDate,
					'uniqueOrderId'=> $uniqueOrderId
					
				];

				$payment = [];

				$sale_id=$this->sales_model->addSale($data, $products, $payment, [],$collect_amount);
				
				if ($sale_id!=''){
					
					
					$inv = $this->sales_model->getInvoiceByID($sale_id);

					$hash=$inv->hash;
					$config_key = $prefix . '_url';
					$url2 = $this->config->item($config_key);
					$url= $url2.'/shop/order_received/'.$sale_id.'/'.$hash;
					$headers = array('AuthToken:' ."4ekLHJqvq2LNK3xcQhGr2Pcz", 'Content-Type: application/json');
					$fields = json_encode($inv);

					$ch = curl_init();

// Set cURL options
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
					curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of outputting it
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification (use with caution)

// Execute the cURL request
$response = curl_exec($ch);

// Check for errors
// if($response === false) {
//     // cURL error occurred
//     $error_message = curl_error($ch);
//     echo "cURL error: $error_message";
// } else {
//     // cURL request was successful
//     $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code
//     echo "HTTP status code: $http_code\n";

//     // Output the response
//     echo "Response: $response";
// }

// Close cURL session
curl_close($ch);
$response_arr = array(
	'success' => true,
	'message' => 'Order Places sucessfully',
);
echo json_encode($response_arr);
}else{
	$response_arr = array(
		'success' => false,
		'message' => 'Unable to place order',
	);
	echo json_encode($response_arr);
}

}else{
	$response_arr = array(
		'success' => true,
		'message' => 'Order Places sucessfully',
	);
	echo json_encode($response_arr);
}

} else 
{
	$response_arr = array(
		'success' => false,
		'message' => 'Unable to place order',
	);
	echo json_encode($response_arr);
}
}



public function place_order2() 
{
	extract($_POST);


	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$this->load->model('sales_model');
	$this->load->model('site');
	if ($user->role == 'customer') 
	{
		$created_by = $sales_product['customer_id'];
	} else 
	{
		$created_by =  $user->id;
	}
	$sales_product = json_decode(file_get_contents('php://input'), true);

	$customer_id = $sales_product['customer_id'];
	$customer_address = $sales_product['customer_address'];
	$totalAmount = $sales_product['totalAmount'];
	$totalvat = $sales_product['totalVAT'];
	$paymentMode = $sales_product['paymentMode'];
	$collect_amount = @$sales_product['collect_amount'];

	$promotionId = $sales_product['promotionId'];
	$discountAmount = $sales_product['discountAmount'];
	$promotionSubType = $sales_product['promotionSubType'];
	$uniqueOrderId = $sales_product['uniqueOrderId'];




	if (!empty($customer_id) && !empty($customer_address))
	{
		$check_exit_or_not = $this->db->query("select id from sma_sales where uniqueOrderId='$uniqueOrderId'")->result_array();

		if(count($check_exit_or_not)==0)
		{ 
			$reference = $this->input->post('reference_no') ? $this->input->post('reference_no') : $this->site->getReference('so');

			$date = date('Y-m-d H:i:s');

			$customer_id = $customer_id;
			$biller_id = 29;
			$total_items = $sales_product['product_count'];
			$sale_status = $this->input->post('sale_status');
			$payment_status = $sales_product['paymentMode'];
			$payment_term = $this->input->post('payment_term');
			$po_number = $this->input->post('po_number');

			$chequeNo = $sales_product['chequeNo'];
			$chequeDate = $sales_product['chequeDate'];

			if ($po_number == '') 
			{
				$po_number = 0;
			}
			$due_date = $payment_term ? date('Y-m-d', strtotime('+' . $payment_term . ' days', strtotime($date))) : null;

			$shipping = $this->input->post('shipping') ? $this->input->post('shipping') : 0;
			$customer_details = $this->site->getCompanyByID($customer_id);

			$customer = !empty($customer_details->company) && $customer_details->company != '-' ? $customer_details->company : $customer_details->name;

			$note = $this->sma->clear_tags($this->input->post('note'));
			$staff_note = $this->sma->clear_tags($sales_product['staff_note']);
			$quote_id = $this->input->post('quote_id') ? $this->input->post('quote_id') : null;
			$default_delivery=$customer_details->default_delivery;
			if($default_delivery == 1)
			{
				$deliverydate=Date('Y-m-d', strtotime('+3 days'));

			}elseif($default_delivery == 2)
			{
				$deliverydate=Date('Y-m-d', strtotime('+1 days'));

			}elseif($default_delivery == 3)
			{
				$deliverydate=Date('Y-m-d', strtotime('+7 days'));
			}
			$total = 0;
			$product_tax = 0;
			$product_discount = 0;
			$digital = false;
			$gst_data = [];
			$total_cgst = $total_sgst = $total_igst = 0;
			foreach ($sales_product['sale_details'] as $key => $value) 
			{
				$pid = $value['product_id'];
				$order_type = $value['quantityType'];
				$product = $this->db->query("select * from sma_products where id='$pid'")->row_array();

				if($order_type=='piece')
				{

					$pieceproduct = $this->db->query("select id,code from sma_products where parent_id='$pid'")->result_array();
					if(count($pieceproduct)!=0)
					{
						$item_id = $pieceproduct[0]['id'];
						$item_code = $pieceproduct[0]['code'];
					}else
					{
						$item_id = $product['id'];
						$item_code = $product['code'];
					}


				}else
				{
					$item_id = $product['id'];
					$item_code = $product['code'];
				}




				$item_type = $product['type'];
				$item_name = $product['name'];
				$item_option = isset($_POST['product_option']) && $_POST['product_option'] != 'false' && $_POST['product_option'] != 'null' ? $_POST['product_option'] : null;
				$real_unit_price = number_format($value['unit_price'],2);
				$unit_price = number_format($value['unit_price'],2);
				$item_unit_quantity = $value['quantity'];
				$get_tax = $value['vat'];
				$get_subtotal = $value['subTotal'];
				$item_serial = $_POST['serial'] ?? '';
				$item_tax_rate = $product['tax_rate'] ?? null;
				$item_discount = $_POST['product_discount'] ?? null;
				$item_unit = $product['unit'];
				$item_quantity = $value['quantity'] ?? null;
				$is_promoted = $value['isPromoted'] ?? 0;
				$promo_id = $value['promo_id'] ?? 0;



				if (isset($item_code) && isset($real_unit_price) && isset($unit_price) && isset($item_quantity)) 
				{
					$item_net_price = $unit_price;

					$subtotal = (($item_net_price * $item_unit_quantity));
					$unit = $this->site->getUnitByID($item_unit);
					$product = [
						'product_id' => $item_id,
						'product_code' => $item_code,
						'product_name' => $item_name,
						'product_type' => $item_type,
						'option_id' => $item_option,
						'net_unit_price' => $item_net_price,
						'unit_price' => $item_net_price,
						'quantity' => $item_quantity,
						'product_unit_id' => $unit ? $unit->id : null,
						'product_unit_code' => $unit ? $unit->code : null,
						'unit_quantity' => $item_unit_quantity,
						'warehouse_id' => '0',
						'item_tax' => $get_tax,
						'tax_rate_id' => $item_tax_rate,
						'tax' => $get_tax,
						'discount' => $item_discount,
						'item_discount' => 0,
						'subtotal' => $get_subtotal,
						'serial_no' => $item_serial,
						'real_unit_price' => $real_unit_price,
						'order_type' => $order_type,
						'order_qty' =>$item_quantity,
						'is_promoted' => $is_promoted,
						'promo_id' => $promo_id,
					];

					$products[] = ($product + $gst_data);

					$total=$totalAmount;
				}
			}

			$data = [
				'date' => $date,
				'reference_no' => $reference,
				'customer_id' => $customer_id,
				'customer' => $customer,
				'biller_id' => 0,
				'biller' => 0,
				'warehouse_id' => 1,
				'note' => $note,
				'staff_note' => $staff_note,
				'total' => $total,
				'product_discount' => 0,

				'order_discount_id' => $promotionId,
				'order_discount' => $discountAmount,
				'total_discount' => $discountAmount,
				'subtype' => $promotionSubType,

				'product_tax' => $totalvat,
				'order_tax_id' => $this->input->post('order_tax'),
				'order_tax' => @$order_tax,
				'total_tax' => $totalvat,
				'shipping' => number_format($shipping,2),
				'grand_total' => number_format($totalAmount + $totalvat,2),
				'total_items' => $total_items,
				'sale_status' => 'New',
				'payment_status' => 'Due',
				'payment_method' => $paymentMode,
				'payment_term' => $payment_term,
				'due_date' => $due_date,
				'paid' => 0,
				'print' => 'N',
				'created_by' => $created_by,
				'hash' => hash('sha256', microtime() . mt_rand()),
				'po_number' => $po_number,
				'staff_note' => $staff_note,
				'deliverydate' => $deliverydate,
				'cheque_number' => $chequeNo,
				'cheque_date' => $chequeDate,
				'uniqueOrderId'=> $uniqueOrderId

			];




			$payment = [];	

			$this->sales_model->addSale($data, $products, $payment, [],$collect_amount);

			$response_arr = array(
				'success' => true,
				'message' => 'Order Places sucessfully',
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => true,
				'message' => 'Order alerady Places. Please check in order List',
			);
			echo json_encode($response_arr);
		}

	} else 
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to place order',
		);
		echo json_encode($response_arr);
	}
}
public function get_customer_list()
{
	extract($_POST);		

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));

	$prefix=$user->prefix;
	$this->load->database($prefix);
	$this->load->model('companies_model');
	$pattern = $this->input->post('pattern');

	$this->load->model('companies_model');
	$result=$this->companies_model->getCustomerSuggestions_api($pattern);
	if(!empty($result))
	{  
		$response_arr = array(
			'success' =>true,
			'message' => 'Customer details found',
			'customer_details'=>$result,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}


public function get_product_list_old()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$this->load->model('site');
// 	    $user_id=$user->id;
// 	    $role=$user->role;
// 	    $users_id =$this->db->get_where('users',['id' =>$user_id])->row();
	$customer_id =$this->input->post('customer_id');
	$pattern = $this->input->post('pattern');
	$categoryId = $this->input->post('categoryId');
	$this->load->model('site');

	$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
	$product_price_type=$c_data->product_price_type;
	$contra_price=$c_data->contra_price;

	if($categoryId!='')
	{

		$this->load->model('companies_model');
		$result=$this->companies_model->getprodctcategory_id($categoryId);

		if(!empty($result)){
			foreach ($result as $results) {
				//if($role=='3'){

				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
				}else
				{
					if($product_price_type=='1')
					{
						$price = $results->price_1;
					}elseif($product_price_type=='2')
					{
						$price = $results->price_2;
					}elseif($product_price_type=='3')
					{
						$price = $results->price_3;
					}elseif($product_price_type=='4')
					{
						$price = $results->price_4;
					}elseif($product_price_type=='5')
					{
						$price = $results->price_5;
					}else
					{
						$price = $results->price;
					}
				} 
				$cdate = date('Y-m-d');
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{

						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}

					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 


				}else{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}
				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');
				//}

				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;

				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=$category->name;
				$results->category_id=$categoryId;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();

				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);

		}else{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}else
	{
		$this->load->model('companies_model');
		$result=$this->companies_model->getprodctSuggestions_api($pattern);

		if(!empty($result))
		{  
			$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
			$product_price_type=$c_data->product_price_type;
			$contra_price=$c_data->contra_price;
			foreach ($result as $results) 
			{


				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
				}else
				{
					if($product_price_type=='1')
					{

						$price = $results->price_1;

					}elseif($product_price_type=='2')
					{

						$price = $results->price_2;

					}elseif($product_price_type=='3')
					{

						$price = $results->price_3;
					}elseif($product_price_type=='4')
					{

						$price = $results->price_4;
					}elseif($product_price_type=='5')
					{				
						$price = $results->price_5;

					}else
					{

						$price = $results->price;
					}
				}
				$cdate = date('Y-m-d');
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{
						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}
					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 

				}else
				{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}

				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');

				//	}


				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;


				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=@$category->name;
				$results->category_id=@$category->id;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();
				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;
				$promotion = $results->productDiscountApplicable;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}
}

public function get_product_list()
{
	
	//echo 'hii';exit();
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	
	$this->load->model('site');
	// 	    $user_id=$user->id;
	// 	    $role=$user->role;
	// 	    $users_id =$this->db->get_where('users',['id' =>$user_id])->row();
	$customer_id =$this->input->post('customer_id');
	$pattern = $this->input->post('pattern');
	$categoryId = $this->input->post('categoryId');
	$this->load->model('site');

	$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
	
	$product_price_type=$c_data->product_price_type;
	$contra_price=$c_data->contra_price;

	if($categoryId!='')
	{

		$this->load->model('companies_model');
		$result=$this->companies_model->getprodctcategory_id($categoryId);

		if(!empty($result)){
			foreach ($result as $results) {
			//if($role=='3'){

				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
					$split_price=  $results->split_price;
				}else
				{
					if($product_price_type=='1')
					{
						$price = $results->price_1;
						$split_price =  $results->piece_price1;
					}elseif($product_price_type=='2')
					{
						$price = $results->price_2;
						$split_price=  $results->piece_price2;
					}elseif($product_price_type=='3')
					{
						$price = $results->price_3;
						$split_price=  $results->piece_price3;
					}elseif($product_price_type=='4')
					{
						$price = $results->price_4;
						$split_price=  $results->piece_price4;
					}elseif($product_price_type=='5')
					{
						$price = $results->price_5;
						$split_price=  $results->piece_price5;
					}else
					{
						$price = $results->price;
						$split_price=  $results->split_price;
					}
				} 
				$cdate = date('Y-m-d');
				if ($results->new_arrival=='1'){		
					$new_arrival_date = $results->new_arrival_date;    

					if ($new_arrival_date < $cdate) {
						$results->new_arrival = '0';
					}

				}
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{

						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}

					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 


				}else{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}
				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');
				$results->split_price = number_format((float)$split_price, 2, '.', '');
			//}

				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;

				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=$category->name;
				$results->category_id=$categoryId;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();

				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);

		}else{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}else
	{
		$this->load->model('companies_model');
		
		$result=$this->companies_model->getprodctSuggestions_api($pattern);

		if(!empty($result))
		{  
			$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
			$product_price_type=$c_data->product_price_type;
			$contra_price=$c_data->contra_price;
			foreach ($result as $results) 
			{


				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
					$split_price=  $results->split_price;
				}else
				{
					if($product_price_type=='1')
					{
						$price = $results->price_1;
						$split_price =  $results->piece_price1;
					}elseif($product_price_type=='2')
					{
						$price = $results->price_2;
						$split_price=  $results->piece_price2;
					}elseif($product_price_type=='3')
					{
						$price = $results->price_3;
						$split_price=  $results->piece_price3;
					}elseif($product_price_type=='4')
					{
						$price = $results->price_4;
						$split_price=  $results->piece_price4;
					}elseif($product_price_type=='5')
					{
						$price = $results->price_5;
						$split_price=  $results->piece_price5;
					}else
					{
						$price = $results->price;
						$split_price=  $results->split_price;
					}
				} 
				$cdate = date('Y-m-d');
				if ($results->new_arrival=='1'){

					$new_arrival_date = $results->new_arrival_date;    

					if ($new_arrival_date < $cdate) {
						$results->new_arrival = '0';
					}

				}
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{
						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}
					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 

				}else
				{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}

				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');
				$results->split_price = number_format((float)$split_price, 2, '.', '');
			//	}


				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;


				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=@$category->name;
				$results->category_id=@$category->id;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();
				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;
				$promotion = $results->productDiscountApplicable;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}
}



public function get_product_return_list()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$this->load->model('site');
// 	    $user_id=$user->id;
// 	    $role=$user->role;
// 	    $users_id =$this->db->get_where('users',['id' =>$user_id])->row();
	$customer_id =$this->input->post('customer_id');
	$pattern = $this->input->post('pattern');
	$categoryId = $this->input->post('categoryId');
	$this->load->model('site');

	$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
	$product_price_type=$c_data->product_price_type;
	$contra_price=$c_data->contra_price;

	if($categoryId!='')
	{

		$this->load->model('companies_model');
		$result=$this->companies_model->getprodct_returncategory_id($categoryId);

		if(!empty($result)){
			foreach ($result as $results) {
				//if($role=='3'){

				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
				}else
				{
					if($product_price_type=='1')
					{
						$price = $results->price_1;
					}elseif($product_price_type=='2')
					{
						$price = $results->price_2;
					}elseif($product_price_type=='3')
					{
						$price = $results->price_3;
					}elseif($product_price_type=='4')
					{
						$price = $results->price_4;
					}elseif($product_price_type=='5')
					{
						$price = $results->price_5;
					}else
					{
						$price = $results->price;
					}
				} 
				$cdate = date('Y-m-d');
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{

						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}

					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 


				}else{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}
				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');
				//}

				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;

				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=$category->name;
				$results->category_id=$categoryId;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();

				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);

		}else{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}else
	{
		$this->load->model('companies_model');
		$result=$this->companies_model->getprodct_returnSuggestions_api($pattern);

		if(!empty($result))
		{  
			$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
			$product_price_type=$c_data->product_price_type;
			$contra_price=$c_data->contra_price;
			foreach ($result as $results) 
			{


				$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$results->id])->row();

				if($p_data!='')
				{
					$price = $p_data->price;
				}else
				{
					if($product_price_type=='1')
					{

						$price = $results->price_1;

					}elseif($product_price_type=='2')
					{

						$price = $results->price_2;

					}elseif($product_price_type=='3')
					{

						$price = $results->price_3;
					}elseif($product_price_type=='4')
					{

						$price = $results->price_4;
					}elseif($product_price_type=='5')
					{				
						$price = $results->price_5;

					}else
					{

						$price = $results->price;
					}
				}
				$cdate = date('Y-m-d');
				$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$results->id' and sma_promos.type='product_discount';")->row_array();
				if(!empty($checkpromotion))
				{
					$price = $results->price;
					$type = $checkpromotion['gettype'];
					$amount = $checkpromotion['getamount'];
					$percentage = $checkpromotion['percentage'];
					if($type == 'percentage')
					{
						$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
					}else
					{
						$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
					}
					if($p_data!='')
					{
						$price1 = $p_data->price;
						$tdiscount=(min($price1,$tdiscount));
					}
					$results->productDiscountApplicable ='1';
					$results->discountedBoxPrice=$tdiscount; 

				}else
				{
					$results->productDiscountApplicable ='0';
					$results->discountedBoxPrice='0.0';
				}

				$results->price = number_format((float)$price, 2, '.', '');
				$results->cost = number_format((float)$price, 2, '.', '');

				//	}


				$config_key = $prefix . '_url';
				$url = $this->config->item($config_key);
				$results->image = $url . '/assets/uploads/thumbs/' . $results->image;


				$category=$this->site->getCategoryByID($results->category_name);
				$results->category_name=@$category->name;
				$results->category_id=@$category->id;
				$replaced = str_replace('<h3>', ' ', $results->product_details);
				$replaced = str_replace('<p>', ' ', $replaced);
				$results->product_details=strip_tags($replaced);
				$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();
				$results->tax_rate=$vat['rate'];
				$results->inner_ean_number=$results->inner_ean_number;
				$results->outer_ean_number=$results->outer_ean_number;
				$promotion = $results->productDiscountApplicable;

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Product details found',
				'product_details'=>$result,
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'No records for matching pattern',
			);
			echo json_encode($response_arr);
		}
	}
}





public function get_product_list_testing()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$customer_id = $this->input->post('customer_id');
	$pattern = $this->input->post('pattern');
	$categoryId = $this->input->post('categoryId');

    // Fetch company data
	$this->db->select('product_price_type, contra_price');
	$c_data = $this->db->get_where('companies', ['id' => $customer_id])->row();

    // Fetch contra prices
	$this->db->select('product_id, price');
	$p_data = $this->db->get_where('contra_price', ['customer_id' => $customer_id])->result_array();



    // Prepare a map for easier access
	$contra_prices = [];
	foreach ($p_data as $row) {
		$contra_prices[$row['product_id']] = $row['price'];
	}


	$cdate = date('Y-m-d');
    // Fetch promotions
	$promotion_query = "select p.gettype, p.getamount, p.percentage, pd.product_id 
	FROM sma_promos AS p 
	LEFT JOIN sma_product_discount AS pd ON p.id = pd.promo_id 
	WHERE p.start_date <= '$cdate' and p.end_date >= '$cdate'
	AND p.type = 'product_discount'";
	$promotions = $this->db->query($promotion_query)->result_array();


	$promotion_map = [];
	foreach ($promotions as $promotion) 
	{
		$promotion_map[$promotion['product_id']] = $promotion;
	}


    // Initialize result array
	$result = [];

	if ($categoryId != '') 
	{
		$this->load->model('companies_model');
		$result = $this->companies_model->getprodctcategory_id($categoryId);
	} else 
	{
		$this->load->model('companies_model');
		$result = $this->companies_model->getprodctSuggestions_api($pattern);
	}

	if (!empty($result)) 
	{
		foreach ($result as $product) 
		{
            // Fetch initial price based on product price type
			$product_price_type = $c_data->product_price_type;
			$price = $this->getInitialPrice($product, $product_price_type);

            // Apply promotion if available
			if (isset($promotion_map[$product->id]))
			{
				$promotion = $promotion_map[$product->id];
				$pro_price = $this->applyPromotion($price, $promotion);
			}


            // Fetch contra price if available
			$contra_price = isset($contra_prices[$product->id]) ? $contra_prices[$product->id] : null;



			if ($contra_price !== null) 
			{
				$tdiscount = min($pro_price, $contra_price);

				$product->productDiscountApplicable ='1';
				$product->discountedBoxPrice=$tdiscount; 

			}else
			{
				$tdiscount=0;
				$product->productDiscountApplicable ='0';
				$product->discountedBoxPrice=$tdiscount; 
			}




            // Format price and cost
			$product->price = number_format((float)$price, 2, '.', '');
			$product->cost = number_format((float)$price, 2, '.', '');

            // Additional data formatting

			$config_key = $prefix . '_url';
			$url = $this->config->item($config_key);
			$product->image = $url . '/assets/uploads/thumbs/' . $product->image;


			//	$product->image = base_url('/assets/uploads/thumbs') . '/' . $product->image;
			$category = $this->site->getCategoryByID($product->category_name);
			$product->category_name = $category->name;
			$product->category_id = $categoryId;
			$product->product_details = strip_tags(str_replace(['<h3>', '<p>'], ' ', $product->product_details));
			$product->tax_rate = $product->rate;
			$product->inner_ean_number = $product->inner_ean_number;
			$product->outer_ean_number = $product->outer_ean_number;
		}

		$response_arr = [
			'success' => true,
			'message' => 'Product details found',
			'product_details' => $result,
		];
		echo json_encode($response_arr);
	} else 
	{
		$response_arr = [
			'success' => false,
			'message' => 'No records for matching pattern',
		];
		echo json_encode($response_arr);
	}
}

private function getInitialPrice($product, $product_price_type)
{
	switch ($product_price_type) {
		case '1':
		return $product->price_1;
		case '2':
		return $product->price_2;
		case '3':
		return $product->price_3;
		case '4':
		return $product->price_4;
		case '5':
		return $product->price_5;
		default:
		return $product->price;
	}
}

private function applyPromotion($price, $promotion)
{


	$type = $promotion['gettype'];
	$amount = $promotion['getamount'];
	$percentage = $promotion['percentage'];

	if ($type == 'percentage') {
		return number_format((float)($price - ($price / 100) * $percentage), 2, '.', '');
	} else {
		return number_format((float)($price - $amount), 2, '.', '');
	}
}


public function getcustomer_by_id()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$id = $this->input->post('customer_id');
	$this->load->model('companies_model');
	$result=$this->companies_model->getUsers($id);
	$company_id=$result[0]->company_id;
	$customer_data=$this->companies_model->getCompanyByID($company_id);
	if(!empty($customer_data))
	{  
		$response_arr = array(
			'success' => true,
			'message' => 'customer details found',
			'customer_details'=>$customer_data,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}

public function getclient_by_id()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$id = $this->input->post('customer_id');
	$this->load->model('companies_model');

	$customer_data=$this->companies_model->getCompanyByID($id);
	if(!empty($customer_data))
	{  
		$response_arr = array(
			'success' => true,
			'message' => 'customer details found',
			'customer_details'=>$customer_data,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}
public function getcustomer_self_orders(){

	$id = $this->input->post('customer_id');
	$this->load->model('companies_model');
	$result=$this->companies_model->getUsers($id);
	$company_id=$result[0]->company_id;
	$customer_order=$this->companies_model->getcustomer_self_orders($company_id);

	if(!empty($customer_order))
	{  
		$response_arr = array(
			'success' => true,
			'message' => 'customer self order found',
			'customer_order'=>$customer_order,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}
public function get_previous_dues_delete()
{

	$id = $this->input->post('customer_id');
	$this->load->model('companies_model');
	$result=get_total($id);
		//$result=$this->companies_model->get_customer_details($id);
	$result2=$this->companies_model->get_customer_details2($id);

	$total_sales_person_collected=total_sale_person_collected($id);
	$total_driver_collected=total_driver_collected($id);

	$total=($total_sales_person_collected+$total_driver_collected);
	$previous_invoices=0;
	if($result2!=''){
		$previous_invoices=count($result2);
	}
	$result1= $result-$total;
// 		if(!empty($result1))
// 		{  
	$data=array(
			//	'previous_deus' => $result->total,
		'previous_deus' => "$result1",
		'previous_invoices' => $previous_invoices,
	);
	$response_arr = array(
		'success' => true,
		'message' => 'Previous dues found',
		'details'=>  $data,
	);
	echo json_encode($response_arr);
// 		}else
// 		{
// 			$response_arr = array(
// 				'success' => false,
// 				'message' => 'No dues found',
// 			);
// 			echo json_encode($response_arr);
// 		}
}
public function get_previous_dues()
{

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$id = $this->input->post('customer_id');
	$this->load->model('companies_model');
	$result=get_total($id);

	$result2=$this->companies_model->get_customer_details2($id);

	$total_sales_person_collected=total_sale_person_collected($id);
	$total_driver_collected=total_driver_collected($id);

	$total=($total_sales_person_collected+$total_driver_collected);


	$get_dues_30=get_dues_total_30_day($id);
	$get_dues_60=get_dues_total_60_day($id);
	$get_dues_90=get_dues_total_90_day($id);


	$moreThan30=@$get_dues_30;
	$moreThan60=@$get_dues_60;
	$moreThan90=@$get_dues_90;

	$total_due=get_total($id);
	$result2=$this->companies_model->get_customer_details2($id);		
	$total_sales_person_collected=total_sale_person_collected($id);
	$total_driver_collected=total_driver_collected($id);		
	$total=($total_sales_person_collected+$total_driver_collected);

	$debtProfile=array(
		"moreThan30DaysDue"=>$moreThan30,
		"moreThan60DaysDue"=>$moreThan60,
		"moreThan90DaysDue"=>$moreThan90,
	);
	$current_outstanding= $total_due-$total;
	$total_due1=$moreThan30+$moreThan60+$moreThan90;

	$previous_invoices=@count($result2);
	$result1= $result-$total;

//	exit();

// 		if(!empty($result1))
// 		{  
	$data=array(
			//	'previous_deus' => $result->total,
		'previous_deus' => "$result1",
		'previous_invoices' => $previous_invoices,
		'total_outstanding'=>"$current_outstanding",
		'total_due'=>$result1,

	);
	$response_arr = array(
		'success' => true,
		'message' => 'Previous dues found',
		'details'=>  $data,
	);
	echo json_encode($response_arr);
// 		}else
// 		{
// 			$response_arr = array(
// 				'success' => false,
// 				'message' => 'No dues found',
// 			);
// 			echo json_encode($response_arr);
// 		}
}

public function added_sale_list_old()
{
	extract($_POST);
	$limit=50;
	$limit = intval($limit);
	$startIndex = intval($startIndex);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);	
		// if ($prefix == 'tsc') {
		// 	$this->ts_added_sale_list();
		// }else
		// {

	if($user->role !='3')
	{ 
		if(!empty($user->id))
		{		

			if ($prefix == 'tsc') {
				$query = "select 
				sma_sales.id AS id,
				sma_sales.date,
				sma_sales.reference_no,
				sma_sales.biller,
				sma_companies.company as company_name,
				sma_sales.customer,
				sma_sales.customer_id,
				sma_sales.sale_status,
				sma_sales.grand_total,
				sma_sales.grand_total AS payable_amount,
				sma_sales.total_discount,
				sma_sales.paid,
				sma_sales.grand_total - sma_sales.paid AS balance,
				sma_sales.payment_method,
				sma_sales.payment_method AS total,
				sma_sales.cheque_status AS previous_dues,
				sma_sales.payment_status,
				sma_sales.cheque_status,
				sma_sales.return_id,
				sma_routes.route_number,sma_companies.company as company_name 
				FROM 
				sma_sales 
				LEFT JOIN 
				sma_companies ON sma_companies.id = sma_sales.customer_id 
				LEFT JOIN 
				sma_routes ON sma_companies.route = sma_routes.id 
				WHERE 			
				(sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')  
				ORDER BY 
				sma_sales.id DESC  
				LIMIT 
				$limit OFFSET $startIndex";

				$result = $this->db->query($query)->result();

			}else
			{
				$query = "select 
				sma_sales.id AS id,
				sma_sales.date,
				sma_sales.reference_no,
				sma_sales.biller,
				sma_companies.company as company_name,
				sma_sales.customer,
				sma_sales.customer_id,
				sma_sales.sale_status,
				sma_sales.grand_total,
				sma_sales.grand_total AS payable_amount,
				sma_sales.total_discount,
				sma_sales.paid,
				sma_sales.grand_total - sma_sales.paid AS balance,
				sma_sales.payment_method,
				sma_sales.payment_method AS total,
				sma_sales.cheque_status AS previous_dues,
				sma_sales.payment_status,
				sma_sales.cheque_status,
				sma_sales.return_id,
				sma_routes.route_number,sma_companies.company as company_name 
				FROM 
				sma_sales 
				LEFT JOIN 
				sma_companies ON sma_companies.id = sma_sales.customer_id 
				LEFT JOIN 
				sma_routes ON sma_companies.route = sma_routes.id 
				WHERE 
				sma_sales.created_by = '$user->id' 
				AND (sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')  
				ORDER BY 
				sma_sales.id DESC  
				LIMIT 
				$limit OFFSET $startIndex";

				$result = $this->db->query($query)->result();
			}


			foreach ($result as $results) 
			{
				$sale_id=$results->id;
				$collected_amount= $this->db->query("select amount from sma_driver_collected_amount where sales_id='$sale_id'")->result_array();
				$totalcredit=number_format(total_sale_person_collected(@$results->customer_id), 2)+number_format(total_driver_collected(@$results->customer_id), 2);

				$results->previous_dues =number_format(get_total(@$results->customer_id)-@$totalcredit, 2);
				$total_sum =(get_total(@$results->customer_id) + @$results->grand_total);
				$results->total = $total_sum - $results->paid;
				$total= $results->grand_total - $results->total_discount;
				$results->payable_amount = "$total";
				$results->grand_total =  number_format(@$results->grand_total,2);
				$results->paid = @$collected_amount[0]['amount'];
				$results->balance =number_format(@$results->balance,2);

			}
			$data = new stdClass();
			$data->result = $result;
			if($result==''){
				$result=[];
			}
			$response_arr = array(
				'success' => true,
				'message' => 'Order Details found',
				'order_details'=>$result,
			);
			echo json_encode($response_arr);

		}else{
			$result= array();
			$response_arr = array(
				'success' => false,
				'message' => 'No order details found',
				'order_details'=>$result,
			);
			echo json_encode($response_arr);
		}
	}elseif($user->role =='3')
	{
		$this->load->model('companies_model');
		$result=$this->companies_model->getUsers($user->id);
		$company_id=$result[0]->company_id;

		$customer_order=$this->companies_model->getcustomer_self_orderst($company_id,$startIndex,$limit,$fromDate,$toDate);

		foreach ($customer_order as $results) 
		{
			$total_sum =(get_total($company_id) + $results->grand_total);

			$results->total = $total_sum - $results->paid;
			$total  =	$results->grand_total - $results->total_discount;
			$results->payable_amount = "$total";
		}
		$data = new stdClass();
		$data->result = $result;
		if($customer_order==''){
			$customer_order=[];
		}
		$response_arr = array(
			'success' => true,
			'message' => 'Order Details found',
			'order_details'=>$customer_order,
		);
		echo json_encode($response_arr);
	}else
	{
		$result= array();
		$response_arr = array(
			'success' => false,
			'message' => 'No order details found',
			'order_details'=>$result,
		);
		echo json_encode($response_arr); 
	}
	//}

}


public function added_sale_list()
{
	extract($_POST);
	$limit = 50;
	$limit = intval($limit);
	$startIndex = intval($startIndex);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix = $user->prefix;
	$this->load->database($prefix);

	
	$check_customer_or_not = $this->db->query("select id,company_id FROM sma_users WHERE id='$user->id' AND group_id='3'")->row_array();

	if ($isSearch == 'Y' && !empty($pattern)) {
		$pattern = $this->db->escape_like_str($pattern);

		if (!empty($check_customer_or_not)){
			$company_id= $check_customer_or_not['company_id'];
			$whereClause = "(sma_sales.customer_id = '$company_id' AND (sma_companies.company LIKE '%$pattern%' OR sma_sales.reference_no LIKE '%$pattern%'))";
		} else {
			if ($prefix == 'tsc') {
				$whereClause = "(sma_companies.company LIKE '%$pattern%' OR sma_sales.reference_no LIKE '%$pattern%')";
			} else {
				$whereClause = "(sma_sales.created_by = '$user->id' AND (sma_companies.company LIKE '%$pattern%' OR sma_sales.reference_no LIKE '%$pattern%'))";
			}
		}
	} else {
		if (!empty($check_customer_or_not)){
			$company_id= $check_customer_or_not['company_id'];
			$whereClause = "(sma_sales.customer_id = '$company_id' AND sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')";
		} else {
			if ($prefix == 'tsc') {
				$whereClause = "(sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')";
			} else {
				$whereClause = "(sma_sales.created_by = '$user->id' AND sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')";
			}
		}
	}


	$orderByClause = "sma_sales.id DESC"; 

	if ($prefix == 'tsc') {
		$query = "select 
		sma_sales.id AS id,
		sma_sales.date,
		sma_sales.reference_no,
		sma_sales.biller,
		sma_companies.company AS company_name,
		sma_sales.customer,
		sma_sales.customer_id,
		sma_sales.sale_status,
		sma_sales.grand_total,
		sma_sales.grand_total AS payable_amount,
		sma_sales.total_discount,
		sma_sales.paid,
		sma_sales.grand_total - sma_sales.paid AS balance,
		sma_sales.payment_method,
		sma_sales.payment_method AS total,
		sma_sales.cheque_status AS previous_dues,
		sma_sales.payment_status,
		sma_sales.cheque_status,
		sma_sales.return_id,
		sma_routes.route_number,
		sma_companies.company AS company_name 
		FROM 
		sma_sales 
		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id 
		LEFT JOIN 
		sma_routes ON sma_companies.route = sma_routes.id 
		WHERE 
		$whereClause
		ORDER BY 
		$orderByClause
		LIMIT 
		$limit OFFSET $startIndex";
	} else {
		$query = "select 
		sma_sales.id AS id,
		sma_sales.date,
		sma_sales.reference_no,
		sma_sales.biller,
		sma_companies.company AS company_name,
		sma_sales.customer,
		sma_sales.customer_id,
		sma_sales.sale_status,
		sma_sales.grand_total,
		sma_sales.grand_total AS payable_amount,
		sma_sales.total_discount,
		sma_sales.paid,
		sma_sales.grand_total - sma_sales.paid AS balance,
		sma_sales.payment_method,
		sma_sales.payment_method AS total,
		sma_sales.cheque_status AS previous_dues,
		sma_sales.payment_status,
		sma_sales.cheque_status,
		sma_sales.return_id,
		sma_routes.route_number,
		sma_companies.company AS company_name 
		FROM 
		sma_sales 
		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id 
		LEFT JOIN 
		sma_routes ON sma_companies.route = sma_routes.id 
		WHERE 
		$whereClause
		ORDER BY 
		$orderByClause
		LIMIT 
		$limit OFFSET $startIndex";
	}

	$result = $this->db->query($query)->result();

	foreach ($result as $results) 
	{
		$sale_id=$results->id;
		$collected_amount= $this->db->query("select amount from sma_driver_collected_amount where sales_id='$sale_id'")->result_array();

		$totalSale = total_sale_person_collected(@$results->customer_id);
		$totalDriver = total_driver_collected(@$results->customer_id);

		// Format the numbers to 2 decimal places
		$formattedTotalSale = number_format($totalSale, 2);
		$formattedTotalDriver = number_format($totalDriver, 2);

		// Calculate the total credit and format it
		$totalcredit = $totalSale + $totalDriver;
		$results->previous_dues =number_format(get_total(@$results->customer_id)-@$totalcredit, 2, '.', '');

		
		$total_sum =(get_total(@$results->customer_id) + @$results->grand_total);
		$results->total = $total_sum - $results->paid;
		$total= $results->grand_total - $results->total_discount;
		$results->payable_amount = "$total";
		$results->grand_total =  number_format(@$results->grand_total,2);
		$results->paid = @$collected_amount[0]['amount'];
		$results->balance =number_format(@$results->balance,2);

	}

	$data = new stdClass();
	$data->result = $result;
	if (empty($result)) {
		$result = [];
	}
	$response_arr = array(
		'success' => true,
		'message' => 'Order Details found',
		'order_details' => $result,
	);
	echo json_encode($response_arr);
}



public function added_sale_list_testing()
{
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));

	if (!empty($user->id)) {
		$result = $this->db->query("select 
			s.id, 
			s.date, 
			s.reference_no, 
			s.biller, 
			s.customer, 
			s.customer_id, 
			s.sale_status, 
			s.grand_total, 
			s.total_discount, 
			s.paid, 
			(s.grand_total - s.paid) AS balance, 
			s.payment_method, 
			s.payment_method AS total, 
			s.cheque_status AS previous_dues, 
			s.payment_status, 
			s.cheque_status, 
			s.return_id, 
			r.route_number
			FROM 
			sma_sales s
			LEFT JOIN 
			sma_companies c ON c.id = s.customer_id
			LEFT JOIN 
			sma_routes r ON c.route = r.id
			WHERE 
			s.created_by = '$user->id' 
			ORDER BY 
			s.id DESC")->result();

        // Fetch collected amount for all sale IDs
		$collected_amounts = $this->db->query("select sales_id, amount FROM sma_driver_collected_amount WHERE sales_id IN (SELECT id FROM sma_sales WHERE created_by = '$user->id')")->result_array();
		$collected_amount_map = [];
		foreach ($collected_amounts as $collected) {
			$collected_amount_map[$collected['sales_id']] = $collected['amount'];
		}

		foreach ($result as $sale) {
			$sale_id = $sale->id;
            // Use the collected amount from the map
			$sale->paid = isset($collected_amount_map[$sale_id]) ? $collected_amount_map[$sale_id] : 0;
			$total_credit = total_sale_person_collected($sale->customer_id) + total_driver_collected($sale->customer_id);
			$sale->previous_dues = number_format(get_total($sale->customer_id) - $total_credit, 2);
			$total_sum = get_total($sale->customer_id) + $sale->grand_total;
			$sale->total = $total_sum - $sale->paid;
			$total = $sale->grand_total - $sale->total_discount;
			$sale->payable_amount = number_format($total, 2);
			$sale->grand_total = number_format($sale->grand_total, 2);
			$sale->balance = number_format($sale->balance, 2);
		}

		$response_arr = array(
			'success' => true,
			'message' => 'Order Details found',
			'order_details' => $result,
		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'No order details found',
			'order_details' => [],
		);
		echo json_encode($response_arr);
	}
}


public function order_details_id()
{
	$id = $this->input->post('order_id');
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	if(!empty($user->id) &&!empty($id))
	{
		$this->load->model('sales_model');
		$this->load->model('site');
		$inv = $this->sales_model->getInvoiceByID($id);

		$customer = $this->site->getCompanyByID($inv->customer_id);
		$credit_facility=$customer->credit_facility;
		// Assuming $customer->credit_type is a JSON string, decode it
		$value = json_decode($customer->credit_type);

// Check if $credit_facility is '0'
		if ($credit_facility == '0') 
		{
			$customer->credit_type_name = 'Cash';
		} else 
		{
    // Filter out objects where 'name' is not empty
			$filteredValue = array_filter($value, function ($item) {
				return !empty($item->name);
			});

    // Re-index the array to start from index 0
			$filteredValue = array_values($filteredValue);

    // Check conditions based on filtered values
			if (isset($filteredValue[1]) && $filteredValue[1]->name == 'invoice_wise') {
				$customer->credit_type_name = 'Temporary Credit';
				$customer->credit_type = $filteredValue;
			} elseif ((isset($filteredValue[0]) && $filteredValue[0]->name == 'day_wise') ||
				(isset($filteredValue[2]) && $filteredValue[2]->name == 'amount_wise')) {
				$customer->credit_type_name = 'Credit';
				$customer->credit_type = $filteredValue;
			} else {
        // Default case if none of the conditions match
        // You might want to handle this case based on your requirements
        $customer->credit_type_name = 'Unknown'; // Set a default value or handle as needed
        $customer->credit_type = $filteredValue; // Assign filtered value
    }
}



$created_by = $this->site->getUser($inv->created_by);
$order_item=$this->sales_model->getAllInvoiceItems($id,$prefix);
		//	$order_item=$this->sales_model->getAllInvoiceItems_no_return($id);
$inv->staff_note =$this->sma->decode_html($inv->staff_note);

$promos =$this->db->query("select gettype,getamount,percentage from sma_promos where id='$inv->order_discount_id'")->row_array();
if (!empty($promos)){
	$type= $promos['gettype'];
	$amount= $promos['getamount'];
	$percentage= $promos['percentage'];
	if($type=='percentage'){
		$inv->discount_description = $percentage .'%';
	}elseif($type=='amount'){
		$inv->discount_description = $amount;
	}
}else{
	$inv->discount_description='';
}

$vat =$this->db->query("select SUM(unit_price*quantity) as vat from sma_sale_items where item_tax != '0' and sale_id='$id' and is_delete='0' and quantity!='0'")->row_array();
$no_vat = $this->db->query("select SUM(unit_price*quantity) as novat from sma_sale_items where item_tax = '0' and sale_id='$id' and is_delete='0' and quantity!='0' ")->row_array();

if($vat['vat']=='')
{
	$vat['vat']='0.00';
}
if($no_vat['novat']==''){
	$no_vat['novat']='0.00';
}
$inv->std_goods=$vat['vat'];
$inv->zero_goods=$no_vat['novat'];
		//	$grand_total=$inv->total_tax + $inv->grand_total;

$grand_total=$inv->grand_total;
$inv->grand_total="$grand_total";
$total_discount=$inv->total_discount;
$to=$grand_total-$total_discount;
$grand_total=$inv->grand_total;
$inv->grand_total="$grand_total";
$inv->payable_amount = "$to";

$data = new stdClass();
$data->inv = $inv;

if($order_item==''){
	$order_item=[];
}
$response_arr = array(
	'success' => true,
	'message' => 'Order Details found',
	'details'=>array('customerDetails'=>$customer,'orderDetails'=>$inv,'product_details'=>$order_item,'parcel_created'=>$created_by),
);
echo json_encode($response_arr);
}else{
	$response_arr = array(
		'success' => false,
		'message' => 'No order details found',
	);
	echo json_encode($response_arr);
}
}
public function previous_invoice_list(){
	$id = $this->input->post('customerId');
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$data = $this->db->query("select id,reference_no,date as order_date,invoice_date,invoice_date,grand_total from sma_sales where customer_id='$id' and payment_status= 'Due' ORDER BY id desc")->result_array();
	if(!empty($user->id) && !empty($id) && !empty($data)){
		$response_arr = array(
			'success' => true,
			'message' => 'Order Details found',
			'invoice_details' => $data,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Details not found',
		);
		echo json_encode($response_arr);
	}
}


public function product_details_by_id()
{

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	// $token = 'e'.$str;
	// $user =jwt::decode($token,$this->config->item('jwt_key'));
	// $prefix=$user->prefix;
	// $this->load->database($prefix);

	$id = $this->input->post('productId');
	$customer_id = $this->input->post('customer_id');
	$this->load->model('products_model');
	$this->load->model('site');
	$pr_details = $this->products_model->getProductByID($id);
	$unit = $this->site->getUnitByID($pr_details->unit);
	$brand = $this->site->getBrandByID($pr_details->brand);

	$category= $this->site->getCategoryByID($pr_details->category_id);
	$subcategory = $pr_details->subcategory_id ? $this->site->getCategoryByID($pr_details->subcategory_id) : null;


	if(!empty($customer_id) &&!empty($id))
	{
		$config_key = $prefix . '_url';
		$url = $this->config->item($config_key);
		$pr_details->image = $url . '/assets/uploads/' . $pr_details->image;

			//$pr_details->image = base_url('/assets/uploads') . '/' . $pr_details->image;
		$pr_details->category_id = @$category->name;
		$pr_details->subcategory_id = @$subcategory->name;
		$vat_rate = $this->db->query("select rate from sma_tax_rates where id='$pr_details->tax_rate'")->row_array();
		$pr_details->tax_rate=$vat_rate['rate'];
		$pr_details->unit=$unit->code;
		$pr_details->product_details=$pr_details->product_details;
		$pr_details->inner_ean_number=$pr_details->inner_ean_number;
		$pr_details->outer_ean_number=$pr_details->outer_ean_number;
		$pr_details->brand=@$brand->name;

				//if($role=='3'){
		$c_data =$this->db->get_where('sma_companies',['id' =>$customer_id])->row();
		$product_price_type=$c_data->product_price_type;
		$contra_price=$c_data->contra_price;
		$p_data =$this->db->get_where('sma_contra_price',['customer_id' =>$customer_id,'product_id' =>$id])->row();
		if($p_data!=''){
			$price = $p_data->price;
		}else{
			if($product_price_type=='1'){
				$price = $pr_details->price_1;
			}elseif($product_price_type=='2'){
				$price = $pr_details->price_2;
			}elseif($product_price_type=='3'){
				$price = $pr_details->price_3;
			}elseif($product_price_type=='4'){
				$price = $pr_details->price_4;
			}elseif($product_price_type=='5'){
				$price = $pr_details->price_5;
			}else{
				$price = $pr_details->price;
			}
		} 
		$cdate = date('Y-m-d');
		$checkpromotion=$this->db->query("select sma_promos.gettype,sma_promos.getamount,sma_promos.percentage FROM sma_promos  LEFT JOIN sma_product_discount on sma_product_discount.promo_id=sma_promos.id WHERE sma_promos.start_date <= '$cdate' and sma_promos.end_date >= '$cdate' and sma_product_discount.product_id='$pr_details->id' and sma_promos.type='product_discount';")->row_array();

		if(!empty($checkpromotion)){
			$price = $pr_details->price;
			$type = $checkpromotion['gettype'];
			$amount = $checkpromotion['getamount'];
			$percentage = $checkpromotion['percentage'];
			if($type == 'percentage'){
				$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
			}else{
				$tdiscount =number_format((float)($price-$amount), 2, '.', ''); 
			}
			if($p_data!=''){
				$price1 = $p_data->price;
				$tdiscount=(min($price1,$tdiscount));
			}
			$pr_details->productDiscountApplicable ='1';
			$pr_details->discountedBoxPrice=$tdiscount; 

		}else{
			$pr_details->productDiscountApplicable ='0';
			$pr_details->discountedBoxPrice='0.0';
		}
		$pr_details->price = number_format((float)$price, 2, '.', '');
		$pr_details->cost = number_format((float)$price, 2, '.', '');

			//	}
		$data = new stdClass();
		$data->pr_details = $pr_details;
		$response_arr = array(
			'success' => true,
			'message' => 'Product Details found',
			'product_details' => $pr_details,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Details not found',
		);
		echo json_encode($response_arr);
	}

}

public function getcategories()
{

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{
		
		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	// $token='e'.$str;
	// $user =jwt::decode($token,$this->config->item('jwt_key'));	
	// $prefix=$user->prefix;
	// $this->load->database($prefix);

	if(!empty($secure_key))
	{ 
		$data = $this->db->query("select * from sma_categories where status='1' ORDER BY id DESC")->result_array();

		$response_arr = array(
			'success' => true,
			'message' => 'Product categories found',
			'product_categories' => $data,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'categories not found',
		);
		echo json_encode($response_arr);
	}
}
public function get_picking_list()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$picker_id = $user->id;
       // echo $picker_id;exit();
	if(!empty($secure_key))
	{ 
		  //$result = $this->db->query("select DISTINCT sma_sales.id,sma_sales.picklist_number,sma_sale_items.sale_id as product_count, SUM(DISTINCT sma_sales.total_items) as total_items from sma_sales left join sma_sale_items ON sma_sales.id=sma_sale_items.sale_id where sma_sales.picker_id='$picker_id' and sma_sales.sale_status='Accept' GROUP BY sma_sales.picklist_number")->result();
		$result = $this->db->query("SELECT sma_sales.id,sma_sales.picklist_number,sma_sale_items.sale_id as product_count,SUM(sma_sale_items.quantity)as total_items,sma_routes.route_name,sma_sales.picking_status  FROM sma_sales,sma_sale_items,sma_companies,sma_routes WHERE sma_sales.picker_id='$picker_id'  and sma_sale_items.sale_id=sma_sales.id and sma_companies.id=sma_sales.customer_id and sma_companies.route=sma_routes.id and picking_status!='done' and sma_sale_items.is_delete!='1' GROUP BY sma_sales.picklist_number order by sma_sales.picklist_number desc")->result();

		foreach ($result as $results) {
			$picklist_number=$results->picklist_number;
			$total_items= round($results->total_items);

			$product= $this->db->query("SELECT sma_sale_items.product_id FROM sma_sale_items where sale_id in(select id from sma_sales where picklist_number='$picklist_number') GROUP by sma_sale_items.product_id,sma_sale_items.order_type")->result();
			$total=count($product);
			$results->total_items="$total_items";
			$results->product_count="$total";
		}

		$data = new stdClass();
		$data->result = $result;
		if($result==''){
			$result=[];
		}
		$response_arr = array(
			'success' => true,
			'message' => 'Order Details found',
			'picking_details' => $result,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Picking List not found',
		);
		echo json_encode($response_arr);
	}
}

public function get_picking_details()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$picker_id = $user->id;
	$picklist_number = $this->input->post('picking_list_id');

	if(!empty($secure_key))
	{ 
		$result = $this->db->query("select sma_sales.id,sma_sales.picklist_number,sma_sale_items.sale_id as product_count,sma_sales.customer_id,sma_sales.staff_note,sma_sales.total_items as item_count from sma_sales left join sma_sale_items ON sma_sales.id=sma_sale_items.sale_id where sma_sales.picklist_number='$picklist_number'  GROUP BY sma_sale_items.sale_id")->result();

		foreach ($result as $results) {
			$id[]=$results->id;
			$note=$this->sma->decode_html($results->staff_note);
			$st=strip_tags($note);
			$customer_accound_no=$this->db->query("select accound_no from sma_companies where id='$results->customer_id'")->row_array();
			$accound_no=$customer_accound_no['accound_no'];
			$staff_note[]= [
				'staff_note' =>  "$st",
				'customer_account_no' =>  "$accound_no",
			];
		}
		  //print_r($staff_note);exit();
		$this->load->model('sales_model');
		$items = $this->sales_model->picking_all($id);

		foreach ($items as $item) {

			$product_id= $item['product_id'];

			$product = $this->db->query("select quantity,expiry_date,inner_ean_number,outer_ean_number,sma_categories.id as category_id,sma_categories.name as product_category_name  from sma_products left join sma_categories on sma_categories.id=sma_products.category_id  where sma_products.id='$product_id'")->row_array();
			$expiry_date = $product['expiry_date'];
			$inner_ean_number = $product['inner_ean_number'];
			$outer_ean_number = $product['outer_ean_number'];
			$product_category_name = @$product['product_category_name'];
			$category_id = @$product['category_id'];
			if($expiry_date==''){
				$expiry_date='0000-00-00';
			}
			$qty=round($product['quantity']);
			$batches= [
				'batch_name' =>  'Batch 1',
				'available_qty' =>  "$qty",
				'expiry_date' =>  $expiry_date,

			];
			$batch=array($batches);
			$quantity=round($item['total']);
			$picked_qty=round($item['picked_qty']);
			$outer_ean= str_replace(' ', '', $outer_ean_number);
			$ean_number= chop($outer_ean);
			$order_type= str_replace(' ', '', $item['order_type']);


			$sma_picked_product_details= $this->db->query("select is_picked,no_pick_reason,sma_picked_product_details.picked_qty from sma_picked_product_details  where product_id='$product_id' and picking_list_no='$picklist_number'")->row_array();
			$is_picked=$sma_picked_product_details['is_picked'];
			$no_pick_reason=$sma_picked_product_details['no_pick_reason'];
			$picked_qty1=$sma_picked_product_details['picked_qty'];


			$details[] = [
				'product_id'=> $item['product_id'],
				'name' =>  $item['product_name'].'-'.$item['size'],
				'code' =>  $item['product_code'],
				'size' =>  $item['size'],
				'pack' =>  $item['pack'],
				'price' =>  $item['price'],
				'quantity' => "$quantity",
				'picked_qty'=>"$picked_qty1",
				'bay' => $item['bay'],
				'rack' => $item['rack'],
				'batches' =>  $batch,
				'product_category_name' => $product_category_name,
				'category_id' => $category_id,
				'onOrderQty'=>'0',
				'inner_ean_number'=>str_replace(' ', '', $inner_ean_number),
				'outer_ean_number'=>$ean_number,
				'quantity_type' => $order_type,
				'is_picked'=>$is_picked,
				'no_pick_reason'=>$no_pick_reason,
			];
		}

		$response_arr = array(
			'success' => true,
			'message' => 'Product Details found',
			'staff_note' => $staff_note,
			'product_details' => $details,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No details found',
		);
		echo json_encode($response_arr);
	}
}
public function confirm_picking_live()
{
	$product = json_decode(file_get_contents('php://input'), true);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$picker_id = $user->id;
	$picking_list_id=$product['picking_list_id'];
	$picker_status = $product['picking_status'];

	if(count($product['picked_product_details'])!=0)
	{


		$data=array(
			'picking_status'=>$picker_status,
			'picked_date'=>date('Y-m-d h:m:s'),
		);
		$this->db->where('picklist_number',$picking_list_id);
		$this->db->update('sma_sales',$data);

		for($i=0;$i<count($product['picked_product_details']);$i++)
		{
			$product_id=$product['picked_product_details'][$i]['product_id'];
			$picked_qty=$product['picked_product_details'][$i]['picked_qty'];
			$actual_qty = $product['picked_product_details'][$i]['actual_qty'];
			$is_picked = $product['picked_product_details'][$i]['is_picked'];
			$no_pick_reason = $product['picked_product_details'][$i]['no_pick_reason'];
			$order_type=$product['picked_product_details'][$i]['order_type'];



			$sma_picked_product1=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and product_id='$product_id' and order_type='$order_type'")->result_array();

			if(count($sma_picked_product1)==0)
			{

				$insert = array(
					"picked_qty" => $picked_qty,
					"product_id" => $product_id,
					"actual_qty" => $actual_qty,
					"picking_list_no" => $picking_list_id,
					"date" => date('Y-m-d'),
					'is_picked'=>$is_picked,
					'no_pick_reason'=>$no_pick_reason,
					'order_type'=>$order_type,
				);

				$this->db->insert('sma_picked_product_details', $insert);
			}else
			{
				$update = array(
					"picked_qty" => $picked_qty,
					"product_id" => $product_id,
					"actual_qty" => $actual_qty,
					"picking_list_no" => $picking_list_id,
					"date" => date('Y-m-d'),
					'is_picked'=>$is_picked,
					'no_pick_reason'=>$no_pick_reason,
					'order_type'=>$order_type,
				);
				$this->db->where('product_id',$product_id);
				$this->db->where('order_type',$order_type);
				$this->db->where('picking_list_no',$picking_list_id);
				$this->db->update('sma_picked_product_details', $update);

			}



			if($picked_qty==$actual_qty)
			{
				$sales=$this->db->query("select sma_sales.id,sma_sale_items.product_id,sma_sale_items.quantity,sma_sale_items.order_type from sma_sales left join sma_sale_items on sma_sale_items.sale_id=sma_sales.id  where product_id='$product_id' and  picklist_number='$picking_list_id' and sma_sale_items.order_type='$order_type'")->result_array();
				foreach($sales as $row)
				{
					$product_id=$row['product_id'];
					$quantity=$row['quantity'];
					$sale_id=$row['id'];

					$update=array('picked_qty'=>$quantity,'is_short_qty_delete' => 1);
					$this->db->where('product_id',$product_id);
					$this->db->where('sale_id',$sale_id);
					$this->db->where('order_type',$order_type);
					$this->db->update('sma_sale_items',$update);

				}
				$update = array(
					'is_confirm'=>'Y',
				);
				$this->db->where('product_id',$product_id);
				$this->db->where('order_type',$order_type);
				$this->db->where('picking_list_no',$picking_list_id);
				$this->db->update('sma_picked_product_details', $update);

			}

		}

		$sma_picked_product=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and is_confirm='N'")->result_array();
		if(count($sma_picked_product)==0)
		{
			$update = array(
				"sale_status" => 'Invoiced',
				"is_confirmed"=>'Y',
				"invoice_date"=>date('Y-m-d'),
			);

			$this->db->where('picklist_number', $picking_list_id);
			$this->db->update('sma_sales', $update);


		}


		if(!empty($secure_key))
		{


			$response_arr = array(
				'success' => true,
				'message' => 'Picking confirmed',
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'Unable to confirm picking',
			);
			echo json_encode($response_arr);
		}

	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'picking Not confirm Please try again',
		);
		echo json_encode($response_arr);
	}
} 

public function confirm_picking()
{
	$product = json_decode(file_get_contents('php://input'), true);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$picker_id = $user->id;
	$picking_list_id=$product['picking_list_id'];
	$picker_status = $product['picking_status'];
	$this->load->model('inv_model');

	if(count($product['picked_product_details'])!=0)
	{


		$data=array(
			'picking_status'=>$picker_status,
			'picked_date'=>date('Y-m-d h:m:s'),
		);
		$this->db->where('picklist_number',$picking_list_id);
		$this->db->update('sma_sales',$data);

		for($i=0;$i<count($product['picked_product_details']);$i++)
		{
			$product_id=$product['picked_product_details'][$i]['product_id'];
			$picked_qty=$product['picked_product_details'][$i]['picked_qty'];
			$actual_qty = $product['picked_product_details'][$i]['actual_qty'];
			$is_picked = $product['picked_product_details'][$i]['is_picked'];
			$no_pick_reason = $product['picked_product_details'][$i]['no_pick_reason'];
			$order_type=$product['picked_product_details'][$i]['order_type'];



			$sma_picked_product1=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and product_id='$product_id' and order_type='$order_type'")->result_array();

			if(count($sma_picked_product1)==0)
			{

				$insert = array(
					"picked_qty" => $picked_qty,
					"product_id" => $product_id,
					"actual_qty" => $actual_qty,
					"picking_list_no" => $picking_list_id,
					"date" => date('Y-m-d'),
					'is_picked'=>$is_picked,
					'no_pick_reason'=>$no_pick_reason,
					'order_type'=>$order_type,
				);

				$this->db->insert('sma_picked_product_details', $insert);
			}else
			{
				$update = array(
					"picked_qty" => $picked_qty,
					"product_id" => $product_id,
					"actual_qty" => $actual_qty,
					"picking_list_no" => $picking_list_id,
					"date" => date('Y-m-d'),
					'is_picked'=>$is_picked,
					'no_pick_reason'=>$no_pick_reason,
					'order_type'=>$order_type,
				);
				$this->db->where('product_id',$product_id);
				$this->db->where('order_type',$order_type);
				$this->db->where('picking_list_no',$picking_list_id);
				$this->db->update('sma_picked_product_details', $update);

			}



			if($picked_qty==$actual_qty)
			{
				$sales=$this->db->query("select sma_sales.id,sma_sale_items.product_id,sma_sale_items.quantity,sma_sale_items.unit_price,sma_sale_items.order_type from sma_sales left join sma_sale_items on sma_sale_items.sale_id=sma_sales.id  where product_id='$product_id' and sma_sale_items.is_delete='0' and  picklist_number='$picking_list_id' and sma_sale_items.order_type='$order_type'")->result_array();
				foreach($sales as $row)
				{
					$product_id=$row['product_id'];
					$quantity=$row['quantity'];
					$unit_price=$row['unit_price'];
					$sale_id=$row['id'];
					$picking_amt=$unit_price*$quantity;

					$update=array('picked_qty'=>$quantity,'picking_qty'=>$quantity,'picking_amt'=>$picking_amt,'is_short_qty_delete' => 1);
					$this->db->where('product_id',$product_id);
					$this->db->where('sale_id',$sale_id);
					$this->db->where('order_type',$order_type);
					$this->db->update('sma_sale_items',$update);

				}
				$update = array(
					'is_confirm'=>'Y',
				);
				$this->db->where('product_id',$product_id);
				$this->db->where('order_type',$order_type);
				$this->db->where('picking_list_no',$picking_list_id);
				$this->db->update('sma_picked_product_details', $update);
				$sma_products = $this->db->query("select quantity from sma_products where id='$p_id'")->row_array();
				$p_quantity = $sma_products['quantity'];


				// $accept_sale = array(
				// 	'product_id' => $product_id,
				// 	'reference_number'=>'Picking',
				// 	'sale_id' => $sale_id,
				// 	'date' => date('Y-m-d H:i:s'), 
				// 	'stock' => $p_quantity,
				// 	'qty' => $quantity,					      
				// 	'system_qty' => $p_quantity - $quantity,
				// 	'order_type'=>$order_type,
				// 	'action_by' =>$picker_id,

				// );

				// $this->db->insert('sma_inventory_history', $accept_sale);


			}

			if($picker_status=='done')
			{

				if($picked_qty==0)
				{

					$sale_items=$this->db->query("select sma_sale_items.id,sma_sale_items.sale_id,sum(sma_sale_items.quantity) as quantity ,sma_sale_items.order_type,sma_sale_items.product_id from sma_sales left join sma_sale_items on sma_sale_items.sale_id=sma_sales.id  where product_id='$product_id' and  picklist_number='$picking_list_id' and sma_sale_items.is_delete='0' and sma_sale_items.order_type='$order_type'")->result_array();

					foreach($sale_items as $row)
					{
						$p_id =$row['product_id']; 
						$o_type = $row['order_type']; 
						$o_qty = $row['quantity'];
						$sale_id = $row['sale_id'];
						$item_id = $row['id'];


						// $date = date("Y-m-d");
						// $inventory = $this->db->query("select id, system_qty,accept_qty,picking_qty FROM sma_inventory_history WHERE product_id='$p_id' AND DATE_FORMAT(date, '%Y-%m-%d')='$date' AND order_type='$o_type'")->result_array();

						// if (empty($inventory)) 
						// {

						// 	$products = $this->db->query("select quantity FROM sma_products WHERE id='$p_id'")->row_array();
						// 	$quantity = $products['quantity'];

						// 	$accept_sale = array(
						// 		'product_id' => $p_id,
						// 		'date' => date('Y-m-d H:i:s'), 
						// 		'stock' => $quantity,
						// 		'picking_qty' => $picked_qty,					      
						// 		'system_qty' => $quantity + $o_qty,
						// 		'order_type' => $o_type,
						// 		'action_by' => $this->session->userdata('user_id')
						// 	);

						// 	$this->db->insert('sma_inventory_history', $accept_sale);
						// } 
						// else 
						// {
				  //          $products = $this->db->query("select quantity FROM sma_products WHERE id='$p_id'")->row_array();
						// 	$quantity = $products['quantity'];

						// 	$system_qty = $inventory[0]['system_qty'] + $o_qty;
						// 	$picked_qty = $inventory[0]['picking_qty'] + $picked_qty;

						// 	$update_inventory = array('picking_qty' => $picked_qty, 'stock' => $system_qty);

						// 	$this->db->where('product_id', $product_id);				
						// 	$this->db->where('order_type', $order_type);
						// 	$this->db->where('DATE(date)', $date);
						// 	$this->db->update('sma_inventory_history', $update_inventory);

						// }


						// if($o_type!='box')
						// {

						// 	$products1 = $this->db->query("select parent_id from sma_products where id='$p_id'")->row_array();
						// 	$p_id = $products1['parent_id'];

						// 	if($p_id==''|| $p_id==0){
						// 		$p_id =  $row['product_id']; 				    				 
						// 	}
						// 	$products1 = $this->db->query("select split_quantity from sma_products where id='$p_id'")->row_array();

						// 	$split_quantity = $products1['split_quantity'];
						// 	$qty = $split_quantity + $o_qty; 
						// 	$update_sales = array('split_quantity' => $qty); 
						// }else
						// {
						// 	$products = $this->db->query("select quantity from sma_products where id='$p_id'")->row_array();
						// 	$quantity = $products['quantity'];

						// 	$qty = $quantity + $o_qty; 
						// 	$update_sales = array('quantity' => $qty); 
						// }
						
						// $this->db->where('id',$p_id);
						// $this->db->update('sma_products',$update_sales);

						
						$this->inv_model->inv_update_product_stock('Picking', $p_id,$item_id, $o_qty, $o_type);

					}


					$sales=$this->db->query("select sma_sales.id,sma_sale_items.id as itemId,sma_sale_items.product_id,sma_sale_items.quantity,sma_sale_items.order_type,sma_sale_items.unit_price from sma_sales left join sma_sale_items on sma_sale_items.sale_id=sma_sales.id  where product_id='$product_id' and  picklist_number='$picking_list_id' and sma_sale_items.order_type='$order_type' and sma_sale_items.is_delete='0'")->result_array();

					foreach($sales as $row)
					{
						$product_id=$row['product_id'];
						$quantity=$row['quantity'];
						$sale_id=$row['id'];
						$iteamid= $row['itemId'];
						$unit_price=$row['unit_price'];
						$picking_amt=$unit_price*$quantity;

						$update=array('picked_qty'=>$picked_qty,'quantity'=>$picked_qty,'picking_qty'=>$picked_qty,'picking_amt'=>$picking_amt,'is_short_qty_delete' => 1);

						$this->db->where('product_id',$product_id);
						$this->db->where('sale_id',$sale_id);
						$this->db->where('order_type',$order_type);
						$this->db->update('sma_sale_items',$update);	

						$get_product = $this->db->query("SELECT sum(item_tax)+sum(subtotal) as grand_total,sum(item_tax) as total_tax FROM `sma_sale_items` where sale_id='$sale_id' and is_delete = '0'")->result_array();

						$grand_total=$get_product[0]['grand_total'];
						$total_tax=$get_product[0]['total_tax'];
						$update_sal2 = array('grand_total'=>$grand_total,'total_tax'=>$total_tax);

						$this->db->where('id', $sale_id);
						$this->db->update('sma_sales', $update_sal2);


					}

					$update = array(
						'is_confirm'=>'Y',
					);
					$this->db->where('product_id',$product_id);
					$this->db->where('order_type',$order_type);
					$this->db->where('picking_list_no',$picking_list_id);
					$this->db->update('sma_picked_product_details', $update);


				}
			}



		}

		$sma_picked_product=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and is_confirm='N'")->result_array();
		if(count($sma_picked_product)==0)
		{
			$update = array(
				"sale_status" => 'Invoiced',
				"is_confirmed"=>'Y',
				"invoice_date"=>date('Y-m-d'),
			);

			$this->db->where('picklist_number', $picking_list_id);
			$this->db->update('sma_sales', $update);


		}


		if(!empty($secure_key))
		{

			$response_arr = array(
				'success' => true,
				'message' => 'Picking confirmed',
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'Unable to confirm picking',
			);
			echo json_encode($response_arr);
		}

	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'picking Not confirm Please try again',
		);
		echo json_encode($response_arr);
	}
} 




public function confirm_picking1()
{
	$product = json_decode(file_get_contents('php://input'), true);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$picker_id = $user->id;
	$picking_list_id=$product['picking_list_id'];
	$picker_status = $product['picking_status'];
	$data=array(
		'picking_status'=>$picker_status,
	);
	$this->db->where('picklist_number',$picking_list_id);
	$this->db->update('sma_sales',$data);




	for($i=0;$i<count($product['picked_product_details']);$i++)
	{
		$product_id=$product['picked_product_details'][$i]['product_id'];
		$picked_qty=$product['picked_product_details'][$i]['picked_qty'];
		$actual_qty = $product['picked_product_details'][$i]['actual_qty'];
		$is_picked = $product['picked_product_details'][$i]['is_picked'];
		$no_pick_reason = $product['picked_product_details'][$i]['no_pick_reason'];



		$sma_picked_product1=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and product_id='$product_id'")->result_array();

		if(count($sma_picked_product1)==0)
		{

			$insert = array(
				"picked_qty" => $picked_qty,
				"product_id" => $product_id,
				"actual_qty" => $actual_qty,
				"picking_list_no" => $picking_list_id,
				"date" => date('Y-m-d'),
				'is_picked'=>$is_picked,
				'no_pick_reason'=>$no_pick_reason,
			);

			$this->db->insert('sma_picked_product_details', $insert);
		}else
		{
			$update = array(
				"picked_qty" => $picked_qty,
				"product_id" => $product_id,
				"actual_qty" => $actual_qty,
				"picking_list_no" => $picking_list_id,
				"date" => date('Y-m-d'),
				'is_picked'=>$is_picked,
				'no_pick_reason'=>$no_pick_reason,
			);
			$this->db->where('product_id',$product_id);
			$this->db->where('picking_list_no',$picking_list_id);
			$this->db->update('sma_picked_product_details', $update);

		}



		$sales=$this->db->query("select sma_sales.id,sma_sale_items.product_id,sma_sale_items.quantity from sma_sales left join sma_sale_items on sma_sale_items.sale_id=sma_sales.id  where product_id='$product_id' and  picklist_number='$picking_list_id'")->result_array();



	}

	$sma_picked_product=$this->db->query("select id from sma_picked_product_details where picking_list_no='$picking_list_id' and is_confirm='N'");
	if(count($sma_picked_product)==0)
	{
		$update = array(
			"sale_status" => 'Invoiced',
		);

		$this->db->where('picklist_number', $picklist_number);
		$this->db->update('sma_sales', $update);
	}
	if(!empty($secure_key))
	{
		$result = $this->db->query("select sma_sales.id,sma_sales.picklist_number,sma_sale_items.sale_id as product_count,sma_sales.total_items as item_count from sma_sales left join sma_sale_items ON sma_sales.id=sma_sale_items.sale_id where sma_sales.picker_id='$picker_id' and sma_sales.sale_status='Accept' GROUP BY sma_sale_items.sale_id;")->result();
		foreach ($result as $results) 
		{
			$id[]=$results->id;
		}
		$data=array(
			'print'=>'Y',
			'print_by'=>'picker',
			'print_on'=>date('Y-m-d H:i:s'),
		);
		$this->db->where_in('id',$id);
		$this->db->update('sma_sales',$data);

		$response_arr = array(
			'success' => true,
			'message' => 'Picking confirmed',
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to confirm picking',
		);
		echo json_encode($response_arr);
	}
} 
public function get_banner_list()
{
	$secure_key=$this->input->request_headers();
	
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}

	$results=$this->db->query("select slider from sma_shop_settings")->result_array();
	$results=json_decode($results[0]['slider']);

	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);



	if(!empty($results))
	{  

		foreach ($results as $result)
		{
			if($result->image!=""){
				$insert[]=array(
					"image" => $url . '/assets/uploads/' .$result->image,

				);
			}
		}
            // $data = new stdClass();
            // $data->$results = $results;


		$response_arr = array(
			'success' =>true,
			'message' => 'Banner details found',
			'slider'=>$insert,
		);

		echo json_encode($response_arr);

	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}
public function update_ean_number() 
{
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	if ($inner_ean != "" and $outer_ean != "") {
		$update = array('inner_ean_number' => $inner_ean, 'outer_ean_number' => $outer_ean);
		$this->db->where('id', $product_id);
		$this->db->update('sma_products', $update);
		$response_arr = array(
			'success' => true,
			'message' => 'Ean Number Update',

		);
		echo json_encode($response_arr);
	} elseif ($inner_ean != "" and $outer_ean == "") {
		$update = array('inner_ean_number' => $inner_ean);
		$this->db->where('id', $product_id);
		$this->db->update('sma_products', $update);
		$response_arr = array(
			'success' => true,
			'message' => 'Ean Number Update',

		);
		echo json_encode($response_arr);
	} elseif ($inner_ean == "" and $outer_ean != "") {
		$update = array('outer_ean_number' => $outer_ean);
		$this->db->where('id', $product_id);
		$this->db->update('sma_products', $update);
		$response_arr = array(
			'success' => true,
			'message' => 'Ean Number Update',

		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Ean Number Required',
		);
		echo json_encode($response_arr);

	}

}

public function search_product_by_patter()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$pattern = $this->input->post('pattern');
	$this->load->model('companies_model');
	$result=$this->companies_model->getprodctSuggestions($pattern);
	if(!empty($result))
	{  
		foreach ($result as $results)
		{

			$config_key = $prefix . '_url';
			$url = $this->config->item($config_key);	

			$results->image = $url . '/assets/uploads/thumbs/'. $results->image;
			$category=$this->site->getCategoryByID($results->category_name);
			$results->category_name=$category->name;
			$replaced = str_replace('<h3>', ' ', $results->product_details);
			$replaced = str_replace('<p>', ' ', $replaced);
			$results->product_details=strip_tags($replaced);
			$vat=$this->db->query("select rate from sma_tax_rates where id='$results->tax_rate'")->row_array();
			$results->tax_rate=$vat['rate'];
			$results->inner_ean_number=$results->inner_ean_number;
			$results->outer_ean_number=$results->outer_ean_number;
		}
		$data = new stdClass();
		$data->result = $result;
		$response_arr = array(
			'success' => true,
			'message' => 'Product details found',
			'product_details'=>$result,
		);
		echo json_encode($response_arr);
	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}

public function manifest_list()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$driver_id = $user->id;
	$result = $this->db->query("select id,date,manifest_id as manifest_number,route_number as route_name,vehicle_id  from sma_manifest where is_deleted='N' and intransit='Y' and is_manifest='Y' and driver_id='$driver_id'  and delivered_status in('N','NUll') ORDER BY sma_manifest.id DESC")->result();
	if(count($result)!='0')
	{
		foreach ($result as $results)
		{
			$manifest_route = $this->db->query("select route_number from sma_routes where id in($results->route_name)")->result();
			$route_name=array();
			foreach ($manifest_route as $r){
				$route_name[]=$r->route_number;
			}
			$route_name=implode(",",$route_name);
			$results->route_name = $route_name;

		}
		$data = new stdClass();
		$data->result = $result;
		$response_arr = array(
			'success' => true,
			'message' => 'Manifest Details found',
			'manifest_list'=>$result,
		);
		echo json_encode($response_arr);

	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch manifest List',
		);
		echo json_encode($response_arr);
	}
}

public function order_list_by_manifest_id()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$driver_id = $user->id;
	$id= $this->input->post('manifest_id');
	if($id!='0'){

		$data['manifast'] = $this->db->query("select * from sma_manifest where id='$id'")->row_array();
		$manifast_id=$data['manifast']['manifest_id'];
		$da = $data['manifast']['print_id'];
		$result = $this->db->query("select sma_sales.id as id, sma_sales.date, sma_sales.reference_no,sma_sales.invoice_date,sma_sales.deliverydate,sma_sales.customer,sma_sales.customer_id,sma_sales.driver_deliver,sma_sales.driver_deliver as total_due,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance, sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_companies.address, sma_routes.route_number from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  LEFT JOIN sma_routes ON sma_companies.route = sma_routes.id where sma_sales.id in ($da)  ORDER BY sma_sales.id DESC")->result();

		if(count($result)!='0'){
			foreach ($result as $results) 
			{

				$results->total_due = number_format(get_total($results->customer_id), 2) - number_format(total_sale_person_collected($results->customer_id), 2)-number_format(total_driver_collected($results->customer_id), 2);

			}
			$data = new stdClass();
			$data->result = $result;
			$response_arr = array(
				'success' => true,
				'message' => 'Delivery Details Found',
				'delivery_details'=>$result,
			);
			echo json_encode($response_arr);
		}else
		{
			$response_arr = array(
				'success' => false,
				'message' => 'Unable to fetch order list',
			);
			echo json_encode($response_arr);
		}
	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch order list',
		);
		echo json_encode($response_arr);
	}
}
public function mark_not_delivered_reason1(){

	$product_list = json_decode(file_get_contents('php://input'), true);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$driver_id = $user->id;
	$order_id=$product_list['order_id'];
	if($order_id!=''){
		$order_data = $this->db->query("select * from sma_sales where id='$order_id'")->row_array();

		$data = array(
			'sales_id'=>$order_data['id'],
			'reference_no'=>$order_data['reference_no'],
			'customer_id'=>$order_data['customer_id'],
			'customer'=>$order_data['customer'],
			'biller_id'=>$order_data['biller_id'],
			'biller'=>$order_data['biller'],
			'warehouse_id'=>$order_data['warehouse_id'],
			'note'=>$order_data['id'],
			'staff_note'=>$order_data['staff_note'],
			'product_discount'=>$order_data['product_discount'],
			'order_discount_id'=>$order_data['order_discount_id'],
			'total_discount'=>$order_data['total_discount'],
			'order_discount'=>$order_data['order_discount'],
			'product_tax'=>$order_data['product_tax'],
			'order_tax_id'=>$order_data['order_tax_id'],
			'order_tax'=>$order_data['order_tax'],
			'total_tax'=>$order_data['total_tax'],
			'grand_total'=>$order_data['grand_total'],
			'total_items'=>$order_data['total_items'],
			'paid'=>'',
			'surcharge'=>'0.00',
			'attachment'=>'',
			'return_quantity'=>$order_data['total_items'],
		);
		$order_data=$this->db->query("select id from sma_returns where sales_id='$order_id'")->row_array();

		if($order_data['id'] == ''){
			$this->db->insert('sma_returns', $data);
		}

		for($i=0;$i<count($product_list['product_list']);$i++)
		{
			$product_id=$product_list['product_list'][$i]['productId'];
			$order_data = $this->db->query("select * from sma_sale_items where sale_id='$order_id' and product_id='$product_id'")->row_array();
			$product = [
				'return_id'         => $order_data['sale_id'],
				'product_id'        => $order_data['product_id'],
				'product_code'      => $order_data['product_code'],
				'product_name'      => $order_data['product_name'],
				'product_type'      => $order_data['product_type'],
				'option_id'         => $order_data['option_id'],
				'net_unit_price'    => $order_data['net_unit_price'],
				'unit_price'        => $this->sma->formatDecimal($order_data['unit_price']),
				'quantity'          => $order_data['quantity'],
                        //'return_quantity'   => $order_data['return_quantity'],
				'product_unit_id'   => $order_data['product_unit_id'],
				'product_unit_code' => $order_data['product_unit_code'],
				'unit_quantity'     => $order_data['unit_quantity'],
				'warehouse_id'      => $order_data['warehouse_id'],
				'item_tax'          => $order_data['item_tax'],
				'tax_rate_id'       => $order_data['tax_rate_id'],
				'tax'               => $order_data['tax'],
				'discount'          => $order_data['discount'],
				'item_discount'     => $order_data['item_discount'],
				'subtotal'          => $this->sma->formatDecimal($order_data['subtotal']),
				'serial_no'         => $order_data['serial_no'],
				'real_unit_price'   => $order_data['real_unit_price'],
				'reason'            =>$product_list['product_list'][$i]['reason'],
				'reason_desc'       =>$product_list['product_list'][$i]['reason_desc'],
			];

			$product_id = $order_data['product_id'];
			$sale_id = $order_data['sale_id'];
			$returns_data=$this->db->query("select id from sma_return_items where return_id='$sale_id' and product_id ='$product_id'")->row_array();

			if($returns_data['id'] == ''){
				$this->db->insert('sma_return_items', $product);
			}

		}
		$response_arr = array(
			'success' => true,
			'message' => 'Marked undeliverd',
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to Mark undeliverd',
		);
		echo json_encode($response_arr);
	}
}

public function mark_not_delivered_reason()
{
	$product_list = json_decode(file_get_contents('php://input'), true);
                //print_r($product_list);exit();
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));        
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$driver_id = $user->id;
	$order_id=$product_list['order_id'];
	if($order_id!=''){                

		$item_tax=0;$subtotal=0;
		for($i=0;$i<count($product_list['product_list']);$i++)
		{
			$item_id=$product_list['product_list'][$i]['productId'];

			$update = [
				'is_return'         => '1',
				'return_reason'     => $product_list['product_list'][$i]['reason'],
				'return_reason_desc' => $product_list['product_list'][$i]['reason_desc'],
				'not_delivered_qty' => $product_list['product_list'][$i]['not_delivered_qty'],
			];                        


			$sale_items = $this->db->query("select product_id,order_type,quantity, item_tax,subtotal from sma_sale_items where id='$item_id'")->row_array();

			$p_id = $sale_items['product_id'];

			$o_type = $sale_items['order_type'];
			$o_qty = $sale_items['quantity'];

                                // if($o_type!='box'){

                                //         $products1 = $this->db->query("select split_quantity,parent_id from sma_products where id='$p_id'")->row_array();
                                //         $p_id = $products1['parent_id'];

                                //         if($p_id==''|| $p_id==0){
                                //                 $p_id =  $row->product_id;                                                                         
                                //         }
                                //         $products1 = $this->db->query("select split_quantity,parent_id from sma_products where id='$p_id'")->row_array();

                                //         $split_quantity = $products1['split_quantity'];
                                //         $qty = $split_quantity + $o_qty; 
                                //         $update_sales = array('split_quantity' => $qty); 
                                // }else{
                                //         $products = $this->db->query("select quantity from sma_products where id='$p_id'")->row_array();
                                //         $quantity = $products['quantity'];

                                //         $qty = $quantity + $o_qty; 
                                //         $update_sales = array('quantity' => $qty); 
                                // }

                                // $this->db->where('id',$p_id);
                                // $this->db->update('sma_products',$update_sales);

			$this->db->where('sale_id', $order_id);
			$this->db->where('id', $item_id);
			$this->db->update('sma_sale_items', $update);

			$item_tax += $sale_items['item_tax'];
			$subtotal += $sale_items['subtotal'];        
		}

		$order_details = $this->db->query("select total_tax,total,grand_total,manifest_id from sma_sales where id='$order_id'")->row_array();
		$total_tax = $order_details['total_tax']-$item_tax;
		$total = $order_details['total']-$subtotal;
		$grand_total = $order_details['grand_total']-($subtotal+$item_tax);                         
		$manifest_id = $order_details['manifest_id'];
		$update_sale=array(
			"total_tax" => $total_tax,
			"total" => $total,
			"grand_total" => $grand_total,
		);

		$this->db->where('id',$order_id);
		$this->db->update('sma_sales',$update_sale);

		$order_data = $this->db->query("select id from sma_sale_items where sale_id='$order_id' and is_return='0' and quantity!='0' and is_delete='0'")->result_array();

		if (count($order_data)==0){

			$query = $this->db->query("update `sma_sales` SET `sale_status` = 'Undelivered',undelivered_reson='all product undelivered' where id ='$order_id'");
		}

		$manifest = $this->db->query("select id from sma_sales where manifest_id='$manifest_id' and sale_status ='Undelivered'")->result_array();
		$total_order = $this->db->query("select id from sma_sales where manifest_id='$manifest_id'")->result_array();

		if (count($manifest)==count($total_order)){                                
			$date = date('Y-m-d');
			$query = $this->db->query("update `sma_manifest` SET `intransit` = 'N' where manifest_id ='$manifest_id' ");

		}
		$response_arr = array(
			'success' => true,
			'message' => 'Marked undeliverd',
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to Mark undeliverd',
		);
		echo json_encode($response_arr);
	}
}

public function mark_parcel_delivery()
{
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));        
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$driver_id = $user->id;
	$order_id = $_POST['order_id'];
	$recipient_name = $_POST['recipient_name'];
	$amount_received = $_POST['amount_received'];
	$payment_mode = $_POST['payment_mode'];
	$trip_id = $_POST['tripId'];
	$product_list = json_decode($_POST['delivered_product_list'], true);

	$chequeDate = $_POST['chequeDate'];
	$chequeNo = $_POST['chequeNo'];

	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);


	if($order_id!='')
	{
		if ($_FILES['proof_of_delivery']['size'] > 0) 
		{

                                ///driver_signature
			$config['upload_path'] =  $url .'/assets/uploads/proof_of_delivery';
			$config['allowed_types'] = '*';
			$config['overwrite'] = false;
                               // $config['max_filename'] = 25;
			$config['encrypt_name'] = true;
			$this->upload->initialize($config);
			if (!$this->upload->do_upload('proof_of_delivery')) 
			{
				$error = $this->upload->display_errors();                                                                
			}
			$photo = $this->upload->file_name;
			$proof_of_delivery = $photo;
		}else{
			$proof_of_delivery='';
		}
		if ($_FILES['recipient_signature']['size'] > 0) 
		{
			$config['upload_path'] = $url .'/assets/uploads/recipient_signature';
			$config['allowed_types'] = '*';
			$config['overwrite'] = false;
                                //$config['max_filename'] = 25;
			$config['encrypt_name'] = true;
			$this->upload->initialize($config);
			if (!$this->upload->do_upload('recipient_signature')) 
			{
				$error = $this->upload->display_errors();        
			}
			$photo = $this->upload->file_name;
			$recipient_signature = $photo;
		}else
		{
			$recipient_signature='';
		}
		if ($_FILES['driver_signature']['size'] > 0) 
		{
			$config['upload_path'] = $url .'/assets/uploads';
			$config['allowed_types'] = '*';
			$config['overwrite'] = false;
                               // $config['max_filename'] = 25;
			$config['encrypt_name'] = true;
			$this->upload->initialize($config);
			if (!$this->upload->do_upload('driver_signature')) 
			{
				$error = $this->upload->display_errors();        
			}
			$photo = $this->upload->file_name;
			$driver_signature = $photo;
		}else
		{
			$driver_signature='';
		}


		for($i=0;$i<count($product_list);$i++)
		{
			$iteamid=$product_list[$i]['product_id'];
			$checkStatus=$product_list[$i]['checkStatus'];
			$delivered_qty=$product_list[$i]['delivered_qty'];
			$sale_items = $this->db->query("select id,unit_price,product_id,accept_qty from sma_sale_items where sale_id='$order_id' and id='$iteamid' ")->row_array();

			$price=$sale_items['unit_price'];
			$productid=$sale_items['product_id'];
			$accept_qty=$sale_items['accept_qty'];
			$product = $this->db->query("select tax_rate,split_price,price,quantity,split_quantity from sma_products where id='$productid'")->row_array();
			$tax_rate = $product['tax_rate'];
			$quantity = $product['quantity'];
			$split_quantity = $product['split_quantity'];

			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			$tax = $this->sma->formatDecimal((($price * $delivered_qty) * $tax_parcent['rate']) / 100, 2);
			$update=array(
				'quantity'=>$delivered_qty,
				'deliver_qty'=>$delivered_qty,              
				'item_tax'=>$tax,
				'subtotal'=>$price*$delivered_qty,
				'unit_price'=>$price,
				'net_unit_price'=>$price,
				'checkStatus' => $checkStatus,
			);      

			$this->db->where('id',$iteamid);
			$this->db->update('sma_sale_items',$update);

			$inventory_setting = $this->db->query("select id from inventory_setting")->result_array();

			if(count($inventory_setting)!=0)
			{
				if($inventory_setting[0]['picking']==0)
				{
					$product_quantity=$accept_qty-$delivered_qty;
					$update_product_data=array(
						'quantity'=>$product_quantity,
						
					);                
					$this->db->where('id',$productid);
					$this->db->update('sma_products',$update_product_data);
				}
			}



			$subtotal+=$price*$delivered_qty;
			$total_tax+=$tax;
		}

		$get_product = $this->db->query("SELECT sum(item_tax)+sum(subtotal) as grand_total,sum(item_tax) as total_tax FROM `sma_sale_items` where sale_id='$order_id' and is_delete = '0'")->result_array();

		$grand_total=$get_product[0]['grand_total'];
		$total_tax=$get_product[0]['total_tax'];
		$subtotal=$grand_total-$total_tax;
		$update_sale=array("total_tax"=>$total_tax,"total"=>$subtotal,"grand_total"=>$grand_total);
		$this->db->where('id',$order_id);
		$this->db->update('sma_sales',$update_sale);

		$order_data = $this->db->query("select id,reference_no,customer,invoice_date,reference_no,total_tax,grand_total,customer_id,manifest_id from sma_sales where id='$order_id'")->row_array();
		$customer_id = $order_data['customer_id'];
		$customer_adress = $this->db->query("select address from sma_companies where id='$customer_id'")->row_array();
		$data_deliveries= array(
			'sale_id' =>$order_data['id'],
			'sale_reference_no' => $order_data['reference_no'],
			'customer' => $order_data['customer'],
			'address' => $customer_adress['address'],
			'note' =>'' ,
			'proof_of_delivery' => $proof_of_delivery,
			'recipient_signature' => $recipient_signature,
			'driver_signature' => $driver_signature,
			'received_by' => $recipient_name,
			'delivered_by' => $driver_id,
		);
		$deliveries_data = $this->db->query("select id from sma_deliveries where sale_id='$order_id'")->row_array();
		if($deliveries_data['id'] == '')
		{                        
			$this->db->insert('sma_deliveries', $data_deliveries);
			$ledger = [
				'customer_id' => $customer_id,
				'sales_id' =>$order_id,
				'driver_id' => $driver_id,
				'amount' => $amount_received,
				'payment_mode'=>$payment_mode,
				'customer_signature' => $customer_signature,
				'driver_signature ' => $driver_signature ,
				'cheque_no' => $chequeNo,
				'cheque_date' => $chequeDate,
				'trip_id'=>$trip_id,
			];

			$ledger = $this->db->insert('sma_driver_collected_amount', $ledger);
		}
		if (count($product_list)!=0)
		{
			$date = date('Y-m-d');
			$deliver = $this->db->query("update sma_sales SET sale_status ='Delivered',delivery_status ='Y' ,driver_deliver='Y',deliverydate='$date' where id ='$order_id'");
		}
		$sales_id_sa = $order_data['id'];
		$invoice_date = $order_data['invoice_date'];
		$invoice_date = date("Y/m/d", strtotime($invoice_date));
		$reference = $order_data['reference_no'];
		$total_tax = $order_data['total_tax'];
		$grand_total = $order_data['grand_total'];
		$customer_id = $order_data['customer_id'];
		$manifest_id = $order_data['manifest_id'];

		$sma_sales = $this->db->query("select accound_no,contact_person,contact_person_mob,name from sma_companies where id='$customer_id'")->row_array();                
		$customerAccountRef = $sma_sales['accound_no'];
		$contact_person = $sma_sales['contact_person'];
		$contact_person_mob = $sma_sales['contact_person_mob'];
		$name=$sma_sales['name']; 

		$customerorder = $this->db->query("select balance from sma_ledger where customer_id='$customer_id' and particulars='$reference'")->result_array();

		if (count($customerorder) == 0)
		{
			$customer_order = $this->db->query("select balance from sma_ledger where customer_id='$customer_id' order by id desc limit 1")->result_array();

			if (count($customer_order) == 0) 
			{
				$insert = array("date" => date('Y-m-d'), "particulars" => $reference, "customer_id" => $customer_id, "amount" => $grand_total, "payment_type" => 'd', "paid_by" => 'Invoice', "reference_number" => '', "balance" => $grand_total);
				$this->db->insert('sma_ledger', $insert);
			} else 
			{

				$balance = $customer_order[0]['balance'] + $grand_total;
				$balance = number_format((float) $balance, 2, '.', '');
				$insert = array("date" => date('Y-m-d'), "particulars" => $reference, "customer_id" => $customer_id, "amount" => $grand_total, "payment_type" => 'd', "paid_by" => 'Invoice', "reference_number" => '', "balance" => $balance);
				$this->db->insert('sma_ledger', $insert);

			}
		}

		$orders = $this->db->query("select id from sma_sales where manifest_id='$manifest_id' and sale_status !='Delivered'")->result_array();

		if (count($orders)==0){                                
			$date = date('Y-m-d');

			$data = array('delivered_status' => 'Y', 'deliverydate' => $date);
			$this->db->where('manifest_id', $manifest_id);
			$this->db->update('sma_manifest', $data);
		}


		$customerorder = $this->db->query("select balance from sma_ledger where customer_id='$customer_id'  order by id desc limit 1")->result_array();

		if(count($customerorder)==0)
		{
			$balance = 0.00;
			$balance =$balance-$amount_received;
		}else
		{
			$balance = $customerorder[0]['balance'] - $amount_received;
		}
		$driver_payment = $this->db->query("select driver_balance from sma_ledger where driver_id='$driver_id'  order by id desc limit 1")->result_array();
		if(count($driver_payment)==0)
		{
			$driver_balance = 0.00;
		}else
		{
			$driver_balance = $driver_payment[0]['driver_balance'] - $amount_paid;
		}

		$ledger1 = [
			"date" => date('Y-m-d'),
			"particulars" => 'driver collected',
			'customer_id' => $customer_id,
			"driver_id" => $driver_id,
			"amount" => $amount_received,
			"balance" => $balance,
			"driver_balance"=>$driver_balance,
			"payment_type" => 'c',
			"collected_payment_type" => 'd',
		];
		$this->db->insert('sma_ledger', $ledger1);

		$collected_payment = $this->db->query("select * from sma_customer_collected_payment where customer_id='$customer_id'")->result_array();
		if(count($collected_payment)==0)
		{
			$collected = [
				"customer_id" => $customer_id,
				"collected_amount" => $amount_received,
			];
			$this->db->insert('sma_customer_collected_payment', $collected);

		}else
		{
			$id=$collected_payment[0]['id'];
			$total=$collected_payment[0]['collected_amount'] + $amount_received;
			$collected = [
				"customer_id" => $customer_id,
				"collected_amount" => $total,
			];
			$this->db->where('id',$id);
			$this->db->update('sma_customer_collected_payment', $collected);

		}
		$response_arr = array(
			'success' => true,
			'message' => 'Marked deliverd',
		);
		echo json_encode($response_arr);

	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to Mark undeliverd',
		);
		echo json_encode($response_arr);
	}
}

public function get_due_invoice(){
	$customer_id = $this->input->post('customer_id');

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$driver_id = $user->id;
		//print_r($customer_id);exit();
	if($customer_id != ''){

		$due_pay = $this->db->query("select sma_sales.id as id, sma_sales.date, sma_sales.customer,sma_sales.customer_id,sma_sales.payment_method, sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, sma_sales.grand_total-paid as balance,sma_sales.payment_status,sma_sales.cheque_status,sma_companies.accound_no,sma_companies.postal_code, sma_companies.address from sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id  where sma_companies.id ='$customer_id' and sma_sales.payment_status='Due' ORDER BY sma_sales.id DESC")->result_array();
		$response_arr = array(
			'success' => true,
			'message' => 'Delivery Details Found',
			'due_pay_order_list' =>$due_pay,
		);
		echo json_encode($response_arr);

	}else{

		$response_arr = array(
			'success' => false,
			'message' => 'Unable to get dues',
		);
		echo json_encode($response_arr);
	}
}
public  function customer_update_payment()
{

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$driver_id = $user->id;
	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);

	$customer_id = $this->input->post('customer_id');
	$amount_paid = $this->input->post('payment_received');
	$date = date("Y-m-d");
	$mode_of_payment =$this->input->post('mode_of_payment');
	$chequeNo =$this->input->post('chequeNo');
	$chequeDate =$this->input->post('chequeDate');
	
	if ($_FILES['customer_signature']['size'] > 0) 
	{
		$config['upload_path'] = $url.'/assets/uploads/customer_signature';
		$config['allowed_types'] = '*';
		$config['max_size'] = $this->allowed_file_size;
		$config['overwrite'] = false;
		$config['max_filename'] = 25;
		$config['encrypt_name'] = true;
		$this->upload->initialize($config);
		if (!$this->upload->do_upload('customer_signature')) 
		{
			$error = $this->upload->display_errors();
		}
		$photo = $this->upload->file_name;
		$customer_signature  = $photo;
	}
	if (@$_FILES['driver_signature']['size'] > 0) 
	{
		$config['upload_path'] = $url.'/assets/uploads/driver_signature';
		$config['allowed_types'] = '*';
		$config['max_size'] = $this->allowed_file_size;
		$config['overwrite'] = false;
		$config['max_filename'] = 25;
		$config['encrypt_name'] = true;
		$this->upload->initialize($config);
		if (!$this->upload->do_upload('driver_signature')) {
			$error = $this->upload->display_errors();	
		}
		$photo = $this->upload->file_name;
		$driver_signature = $photo;
	}
	if($customer_id!='')
	{
		$get_role=$this->db->query("select group_id from sma_users where id='$driver_id'")->row_array();

		$group_id=$get_role['group_id'];
		if($group_id==16)
		{
			$ledger = [
				'customer_id' => $customer_id,
				'driver_id' => $driver_id,
				'amount' => $amount_paid,
				'payment_mode'=>$mode_of_payment,
				'customer_signature' => $customer_signature,
				'cheque_no' => $chequeNo,
				'cheque_date' => $chequeDate,
				'trip_id'=>$this->input->post('trip_id'),

			];
		}else
		{
			$ledger = [
				'customer_id' => $customer_id,
				'sales_person_id' => $driver_id,
				'amount' => $amount_paid,
				'payment_mode'=>$mode_of_payment,
				'customer_signature' => $customer_signature,
				'cheque_no' => $chequeNo,
				'cheque_date' => $chequeDate,
				'trip_id'=>$this->input->post('trip_id'),

			];



		}

		$ledger = $this->db->insert('sma_driver_collected_amount', $ledger);



		$customerorder = $this->db->query("select balance from sma_ledger where customer_id='$customer_id'  order by id desc limit 1")->result_array();
		if(count($customerorder)==0)
		{
			$balance = 0.00;
			$balance =$balance-$amount_paid;
		}else
		{
			$balance = $customerorder[0]['balance'] - $amount_paid;
		}

		if($group_id==16)
		{
			$driver_payment = $this->db->query("select driver_balance from sma_ledger where driver_id='$driver_id'  order by id desc limit 1")->result_array();
			if(count($driver_payment)==0)
			{
				$driver_balance = $amount_paid;
			}else
			{
				$driver_balance = $driver_payment[0]['driver_balance'] + $amount_paid;
			}


			$ledger1 = [
				"date" => date('Y-m-d'),
				"particulars" => 'driver collected',
				'customer_id' => $customer_id,
				"driver_id" => $driver_id,
				"amount" => $amount_paid,
				"balance" => $balance,
				"driver_balance"=>$driver_balance,
				"payment_type" => 'c',
				"collected_payment_type" => 'd',
			];

		}else
		{
			$sales_rep_payment = $this->db->query("select sales_rep_balance from sma_ledger where sales_person_id='$driver_id'  order by id desc limit 1")->result_array();
			if(count($sales_rep_payment)==0)
			{
				$sales_rep_balance =$amount_paid;
			}else
			{
				$sales_rep_balance = $sales_rep_payment[0]['sales_rep_balance'] + $amount_paid;
			}
			$ledger1 = [
				"date" => date('Y-m-d'),
				"particulars" => 'sales rep collected',
				'customer_id' => $customer_id,
				"sales_person_id" => $driver_id,
				"amount" => $amount_paid,
				"balance" => $balance,
				"sales_rep_balance"=>$sales_rep_balance,
				"payment_type" => 'c',
				"collected_payment_type" => 'd',
			];
		}

		$this->db->insert('sma_ledger', $ledger1);

		$collected_payment = $this->db->query("select * from sma_customer_collected_payment where customer_id='$customer_id'")->result_array();
		if(count($collected_payment)==0)
		{
			$collected = [
				"customer_id" => $customer_id,
				"collected_amount" => $amount_paid,
			];
			$this->db->insert('sma_customer_collected_payment', $collected);

		}else
		{
			$id=$collected_payment[0]['id'];
			$total=$collected_payment[0]['collected_amount'] + $amount_paid;
			$collected = [
				"customer_id" => $customer_id,
				"collected_amount" => $total,
			];
			$this->db->where('id',$id);
			$this->db->update('sma_customer_collected_payment', $collected);

		}

		$response_arr = array(
			'success' => true,
			'message' => 'Payment details updated',
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'unable to update payment details',
		);
		echo json_encode($response_arr);
	}
}
public function addtocart() {
	extract($_POST);

	$secure_key = $this->input->request_headers();		
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	// $token = 'e' . $str;
	// $user = jwt::decode($token, $this->config->item('jwt_key'));
	// $prefix=$user->prefix;
	// $this->load->database($prefix);
	$user_id = $user->id;
	$cart_details = json_decode(file_get_contents('php://input'), true);

	if (count($cart_details['cart_details']) != 0) 
	{

		for ($i = 0; $i < count($cart_details['cart_details']); $i++) {

			$id = $cart_details['cart_details'][$i]['product_id'];
			$product = $this->db->query("select tax_rate,split_price,price from sma_products where id='$id'")->row_array();

			$tax_rate = $product['tax_rate'];
			$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();
			if ($cart_details['cart_details'][$i]['piece_type'] == 'piece') 
			{
				$price = $product['split_price'];
				$order_type = 'piece';

			} else 
			{
				$price = $product['price'];
				$order_type = 'box';
			}
			$tax = $this->sma->formatDecimal((($price * $cart_details['cart_details'][$i]['qty']) * $tax_parcent['rate']) / 100, 2);
			if ($tax == '') 
			{
				$tax = '0.';
			}

			$product_details = $this->db->query("select qty from sma_cart_details where product_id='$id' and user_id='$user_id' and piece_type='$order_type'")->result_array();


			if (count($product_details) == 0) {
				
				$insert = [
					"product_id" => $cart_details['cart_details'][$i]['product_id'],
					"user_id" => $user_id,
					"customer_id" =>$cart_details['customer_id'],
					"qty" => $cart_details['cart_details'][$i]['qty'],
					"piece_type" => $order_type,
					"cart_price" =>$price,
					"sub_total" =>$cart_details['cart_details'][$i]['qty'] * $price,
					'vat' => $tax,
					'tax' => $tax,
				];
				$this->db->insert('sma_cart_details', $insert);

				$insert1 = [
					"product_id" => $cart_details['cart_details'][$i]['product_id'],
					"user_id" => $user_id,						
					"qty" => $cart_details['cart_details'][$i]['qty'],
					"piece_type" => $order_type,
					"cart_price" =>$price,
					"sub_total" =>$cart_details['cart_details'][$i]['qty'] * $price,
					'vat' => $tax,
					'tax' => $tax,
				];
				$this->db->insert('sma_cart_details1', $insert1);



			} else 
			{

				$product_id = $cart_details['cart_details'][$i]['product_id'];
				$pdetails = $this->db->query("select qty,cart_price from sma_cart_details where product_id='$product_id' and user_id='$user_id' and piece_type='$order_type'")->row_array();
				$cart_price=$pdetails['cart_price'];

				//updated by Mrudul
				if(($cart_details['cart_details'][$i]['method'])=="updteQty")
				{
					$qty=$cart_details['cart_details'][$i]['qty'];
				}else
				{
					$qty = $product_details[0]['qty'] + $cart_details['cart_details'][$i]['qty'];
				}
				
				
				$price1 = $qty * $cart_price;

				$tax = $this->sma->formatDecimal((($cart_price * $qty) * $tax_parcent['rate']) / 100, 2);
				if ($tax == '') 
				{
					$tax = '0.';
				}


				$update = [
					"qty" => $qty,
					"piece_type" => $order_type,
					"sub_total" => $price1,
					'vat' => $tax,
					'tax' => $tax,
					'customer_id' =>$cart_details['customer_id'],
				];

				$this->db->where('product_id', $product_id);
				$this->db->where('user_id', $user_id);
				$this->db->where('piece_type', $order_type);
				$this->db->update('sma_cart_details', $update);

			}

		}

		$response_arr = array(
			'success' => true,
			'message' => 'Details added to cart',
		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Please add to cart',
		);
		echo json_encode($response_arr);
	}
}
public function getcart_details() 
{
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	// $token = 'e' . $str;
	// $user = jwt::decode($token, $this->config->item('jwt_key'));
	// $prefix=$user->prefix;
	// $this->load->database($prefix);
	$user_id = $user->id;

	//to test image Mrudul
	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);
	//end 
	$products = $this->db->query("select sma_cart_details.id,sma_cart_details.product_id,sma_products.name,piece_type,sma_cart_details.sub_total,qty,vat,code,size,image,cart_price from sma_cart_details left join sma_products on sma_products.id=sma_cart_details.product_id where sma_cart_details.user_id='$user_id' and sma_cart_details.customer_id='$customer_id' order by sma_cart_details.id asc")->result_array();

	if (count($products) != 0) {

		foreach ($products as $product) 
		{
			$total+=$product['sub_total'] + $product['vat'];
			$cart_details[] = [
				'id' => $product['id'],
				'product_id'=>$product['product_id'],
				'code' => $product['code'],
				// 'image' => base_url('/assets/uploads') . '/' . $product['image'],

				'image' => $url . '/assets/uploads/thumbs/' .  $product['image'],
				'product_name' => $product['name'],
				'piece_type' => $product['piece_type'],
				'sub_total' => $product['sub_total'],
				'cart_price' => $product['cart_price'],
				'size' => $product['size'],
				'qty' => $product['qty'],
				'vat' => $product['vat'],
			];

		}
		$response_arr = array(
			'success' => true,
			'message' => 'get cart details',
			'data' => $cart_details,
			'total'=> "$total",
		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => true,
			'data' => [],
			'total'=>"0.0",
			'message' => 'get cart details',
		);
		echo json_encode($response_arr);
	}

}
public function clear_cart()
{
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$this->db->query("delete  from sma_cart_details where user_id='$user_id'");
	if ($user_id == 0) {
		$response_arr = array(
			'success' => false,
			'message' => 'Please Provide the user details',
		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => true,
			'message' => 'Cart is Clear',
		);
		echo json_encode($response_arr);
	}
}
public function clear_cart_by_cart_id()
{
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;
	$this->db->query("delete  from sma_cart_details where id='$cart_id'");
	if ($cart_id == 0) {
		$response_arr = array(
			'success' => false,
			'message' => 'Please Provide the user details',
		);
		echo json_encode($response_arr);
	} else {
		$response_arr = array(
			'success' => true,
			'message' => 'Cart item Clear',
		);
		echo json_encode($response_arr);
	}
}
public function update_price()
{
	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$cart_details=$this->db->query("select qty,product_id from sma_cart_details where id='$id'")->row_array();
	$qty=$cart_details['qty'];

	$product_id=$cart_details['product_id'];
	$product = $this->db->query("select tax_rate from sma_products where id='$product_id'")->row_array();
	$tax_rate = $product['tax_rate'];
	$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();

	$tax = $this->sma->formatDecimal((($cart_price * $cart_details['qty']) * $tax_parcent['rate']) / 100, 2);
	if ($tax == '') 
	{
		$tax = '0.';
	}

	$update = [
		"cart_price" =>$cart_price,
		"sub_total" =>$qty*$cart_price,
		'vat' => $tax,
		'tax' => $tax,
	];
	$this->db->where('id', $id);
	$this->db->update('sma_cart_details', $update);



	if ($id == 0)
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Please Provide the product details',
		);
		echo json_encode($response_arr);
	} else 
	{
		$response_arr = array(
			'success' => true,
			'message' => 'Cart Product Price is update',
		);
		echo json_encode($response_arr);
	}
}

public function payment_collection() 
{
	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;
	$cheque_total='0';
	$cash_total='0';
	$total='0';


	$data = $this->db->query("select sma_driver_collected_amount.id,sma_driver_collected_amount.created_at,sma_companies.name,sales_person_id,driver_id,amount as collected_amount,allocated_amount,customer_id,payment_mode,is_assign,sma_companies.accound_no as account_no from sma_driver_collected_amount LEFT JOIN sma_companies ON sma_companies.id = sma_driver_collected_amount.customer_id where (driver_id='$user_id' || sales_person_id='$user_id') and DATE_FORMAT(sma_driver_collected_amount.created_at,'%Y-%m-%d') BETWEEN '$start_date' and '$end_date' and amount!='0.00'")->result_array();
	foreach ($data as $d) 
	{
		if($d['payment_mode']=='cheque')
		{
			$cheque_total+=$d['collected_amount'];

		}else if($d['payment_mode']=='cash')
		{
			$cash_total+=$d['collected_amount'];

		}else if($d['payment_mode']=='card')
		{
			$card+=$d['collected_amount'];
		}
	}


	if (count($data) == 0) {
		$response_arr = array(
			'success' => false,
			'message' => 'Payment Not Available',
		);
		echo json_encode($response_arr);
	} else {
		$total=$cheque_total+$cash_total;
		$response_arr = array(
			'payment' => $data,
			'cheque_total' =>"$cheque_total",
			'cash_total' =>"$cash_total",
			'total' =>"$total",
			'success' => true,
			'message' => 'Get Payment',
		);
		echo json_encode($response_arr);
	}
}

public function customerledger() 
{

	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;
	$get_role = $this->db->query("select company_id from sma_users where id='$user_id'")->row_array();
	$customer_id = $get_role['company_id'];

	$start_date = date("Y-m-d", strtotime($_POST['start_date']));
	$end_date = date("Y-m-d", strtotime($_POST['end_date']));

	if ($_POST['start_date'] == "" || $_POST['end_date'] == "") 
	{
		$start_date = date("Y-m-d");
		$end_date = date("Y-m-d");
	}

	if ($customer_id != 0)
	{
		$entryDetails2 = $this->db->query("select amount,balance,collected_payment_type,customer_id,date,driver_balance,driver_id,sma_ledger.id,paid_by,particulars,payment_method,payment_type,reference_number,sales_person_id,sales_rep_balance from sma_ledger  where customer_id='$customer_id' and  collected_payment_type!='c' and date BETWEEN '$start_date' AND '$end_date' and payment_type is not null")->result_array();
		$data['customer_credit'] = $this->db->query("select amount as total from sma_ledger where customer_id='$customer_id' and collected_payment_type!='c' ORDER BY id DESC")->row_array();
		$data['customer'] = $this->db->query("select sma_companies.id,name,accound_no as account_no,address,route_name,phone from sma_companies left join sma_routes on sma_routes.id=sma_companies.route where sma_companies.id='$customer_id'")->row_array();
	}


	foreach ($entryDetails2 as $row) 
	{

		$entryDetails1[]= array(
			'amount' => $row['amount'],
			'balance' => $row['balance'],
			'payment_type' => $row['payment_type'],
				//'customer_id' => $row['customer_id'],
			'date' => $row['date'],
			'balance' => $row['balance'],
			//	'driver_id' => $row['driver_id'],
			'driver_name' => get_driver($row['driver_id']),
			'id' => $row['id'],
			'particulars' => $row['particulars'],
			'payment_type' => $row['payment_type'],
			'sales_person_id' => $row['sales_person_id'],
			'paid_by' => $row['paid_by'],
			'sales_person_name' => get_sales_rep($row['sales_person_id']),


		);

	}

	$data['companyDetails']=array('companyName'=>'D&D SNACK FOODS LTD','companyAddress'=>'UNIT FI LARKFIELD TRADING EST, NEW HYTHE LANE
		AYLESFORD KENT ME206SW','phoneNumber'=>'01622792470','vatNo'=>'619164534','wesite'=>'www.ddsnacks.co.uk');

	$data['ledgerDetails']=array('entryDetails'=>$entryDetails1,'fromDate'=>$start_date,'toDate'=>$end_date,'printDate'=>date('Y-m-d'));

	if (count($data['ledgerDetails']) != '0') 
	{
		$response_arr = array(
			'data' => $data,
			'success' => true,
			'message' => 'ledger details found',
		);
		echo json_encode($response_arr);
	} else 
	{
		$data = array();
		$response_arr = array(
			'data' => $data,
			'success' => false,
			'message' => 'ledger details not found',
		);
		echo json_encode($response_arr);
	}
}

public function customerledger_app1() 
{

	extract($_POST);
// 		$secure_key = $this->input->request_headers();
// 		$token = $secure_key['authorization'];
// 		$this->load->helper('jwt_helper');
// 		$str = ltrim($token, 'Bearer ');
// 		$token = 'e' . $str;
// 		$user = jwt::decode($token, $this->config->item('jwt_key'));
// 		$user_id = $user->id;
// 		$get_role = $this->db->query("select company_id from sma_users where id='$user_id'")->row_array();
	//	$customer_id = $get_role['company_id'];

	$start_date = date("Y-m-d", strtotime($_POST['start_date']));
	$end_date = date("Y-m-d", strtotime($_POST['end_date']));

	if ($_POST['start_date'] == "" || $_POST['end_date'] == "") 
	{
		if ($customer_id != 0)
		{
			$entryDetails2 = $this->db->query("select amount,balance,collected_payment_type,customer_id,date,driver_balance,driver_id,sma_ledger.id,paid_by,particulars,payment_method,payment_type,reference_number,sales_person_id,sales_rep_balance from sma_ledger  where customer_id='$customer_id' and  collected_payment_type!='c' and payment_type is not null")->result_array();
			$data['customer_credit'] = $this->db->query("select amount as total from sma_ledger where customer_id='$customer_id' and collected_payment_type!='c' ORDER BY id DESC")->row_array();
			$data['customer'] = $this->db->query("select sma_companies.id,name,accound_no as account_no,address,route_name,phone from sma_companies left join sma_routes on sma_routes.id=sma_companies.route where sma_companies.id='$customer_id'")->row_array();
		}
	}else{

		if ($customer_id != 0)
		{
			$entryDetails2 = $this->db->query("select amount,balance,collected_payment_type,customer_id,date,driver_balance,driver_id,sma_ledger.id,paid_by,particulars,payment_method,payment_type,reference_number,sales_person_id,sales_rep_balance from sma_ledger  where customer_id='$customer_id' and  collected_payment_type!='c' and date BETWEEN '$start_date' AND '$end_date' and payment_type is not null")->result_array();
			$data['customer_credit'] = $this->db->query("select amount as total from sma_ledger where customer_id='$customer_id' and collected_payment_type!='c' ORDER BY id DESC")->row_array();
			$data['customer'] = $this->db->query("select sma_companies.id,name,accound_no as account_no,address,route_name,phone from sma_companies left join sma_routes on sma_routes.id=sma_companies.route where sma_companies.id='$customer_id'")->row_array();
		}
	}


	foreach ($entryDetails2 as $row) 
	{
		if($row['paid_by']=='Invoice')
		{
			$paid_by= 'Inv';
			$invoice_number=$row['particulars'];
		}else
		{
			$paid_by='Pay';
			$invoice_number='';
		}

		$entryDetails1[]= array(
			'amount' => number_format($row['amount'],2),
			'balance' =>  number_format($row['balance'],2),
			'payment_type' => $row['payment_type'],
				//'customer_id' => $row['customer_id'],
			'date' => $row['date'],
			//	'balance' => $row['balance'],
			//	'driver_id' => $row['driver_id'],
			//	'driver_name' => get_driver($row['driver_id']),
			//	'id' => $row['id'],
			'number' => $invoice_number,
			//	'payment_type' => $row['payment_type'],
			//	'sales_person_id' => $row['sales_person_id'],
			'type' => $paid_by,
			//	'sales_person_name' => get_sales_rep($row['sales_person_id']),


		);

	}
	$get_dues_30=get_dues_total_30_day($customer_id);
	$get_dues_60=get_dues_total_60_day($customer_id);
	$get_dues_90=get_dues_total_90_day($customer_id);


	$moreThan30=@$get_dues_30;
	$moreThan60=@$get_dues_60;
	$moreThan90=@$get_dues_90;

	$total_due=get_total($customer_id);
	$result2=$this->companies_model->get_customer_details2($customer_id);		
	$total_sales_person_collected=total_sale_person_collected($customer_id);
	$total_driver_collected=total_driver_collected($customer_id);		
	$total=($total_sales_person_collected+$total_driver_collected);

	$debtProfile=array(
		"moreThan30DaysDue"=>$moreThan30,
		"moreThan60DaysDue"=>$moreThan60,
		"moreThan90DaysDue"=>$moreThan90,
	);
	$current_outstanding= $total_due-$total;
	$total_due1=$moreThan30+$moreThan60+$moreThan90;

	$data['companyDetails']=array('companyName'=>'D&D SNACK FOODS LTD','companyAddress'=>'UNIT FI LARKFIELD TRADING EST, NEW HYTHE LANE
		AYLESFORD KENT ME206SW','phoneNumber'=>'01622792470','vatNo'=>'619164534','wesite'=>'www.ddsnacks.co.uk');

	$data['ledgerDetails']=array('entryDetails'=>$entryDetails1,'fromDate'=>$start_date,'toDate'=>$end_date,'current_outstanding'=>$current_outstanding,'total_due'=>$total_due1,'printDate'=>date('Y-m-d'),'debtProfile'=>$debtProfile);

	if (count($data['ledgerDetails']) != '0') 
	{
		$response_arr = array(
			'data' => $data,
			'success' => true,
			'message' => 'ledger details found',
		);
		echo json_encode($response_arr);
	} else {
		$data = array();
		$response_arr = array(
			'data' => $data,
			'success' => false,
			'message' => 'ledger details not found',
		);
		echo json_encode($response_arr);
	}
}




public function customerledger_app() 
{

	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);



	$start_date = date("Y-m-d", strtotime($_POST['start_date']));
	$end_date = date("Y-m-d", strtotime($_POST['end_date']));

	if ($_POST['start_date'] == "" || $_POST['end_date'] == "") 
	{
		if ($customer_id != 0)
		{
			$entryDetails2 = $this->db->query("select amount,balance,collected_payment_type,customer_id,date,driver_balance,driver_id,sma_ledger.id,paid_by,particulars,payment_method,payment_type,reference_number,sales_person_id,sales_rep_balance from sma_ledger  where customer_id='$customer_id' and  collected_payment_type!='c' and payment_type is not null")->result_array();
			$data['customer_credit'] = $this->db->query("select amount as total from sma_ledger where customer_id='$customer_id' and collected_payment_type!='c' ORDER BY id DESC")->row_array();
			$data['customer'] = $this->db->query("select sma_companies.id,name,accound_no as account_no,address,route_name,phone from sma_companies left join sma_routes on sma_routes.id=sma_companies.route where sma_companies.id='$customer_id'")->row_array();
		}
	}else{

		if ($customer_id != 0)
		{
			$entryDetails2 = $this->db->query("select amount,balance,collected_payment_type,customer_id,date,driver_balance,driver_id,sma_ledger.id,paid_by,particulars,payment_method,payment_type,reference_number,sales_person_id,sales_rep_balance from sma_ledger  where customer_id='$customer_id' and  collected_payment_type!='c' and date BETWEEN '$start_date' AND '$end_date' and payment_type is not null")->result_array();
			$data['customer_credit'] = $this->db->query("select amount as total from sma_ledger where customer_id='$customer_id' and collected_payment_type!='c' ORDER BY id DESC")->row_array();
			$data['customer'] = $this->db->query("select sma_companies.id,name,accound_no as account_no,address,route_name,phone from sma_companies left join sma_routes on sma_routes.id=sma_companies.route where sma_companies.id='$customer_id'")->row_array();
		}
	}

	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);
	foreach ($entryDetails2 as $row) 
	{
		if($row['paid_by']=='Invoice')
		{
			$paid_by= 'Inv';
			$invoice_number=$row['particulars'];
			$data_sale = $this->db->query("select id,payment_status,invoice_name from sma_sales where reference_no='$invoice_number'")->row_array();
			$payment_status= $data_sale['payment_status'];
			$invoice_name= $data_sale['invoice_name'];
			if($payment_status=='Paid'){
				$is_invoice_paid = 'Y';
			}else{
				$is_invoice_paid = 'N';
			}
			if($invoice_name!='')
			{
				$invoice_url= $url.'/assets/uploads/inv_pdf/'.$invoice_name; 
			}else
			{
				$invoice_url= ''; 
			}

		}else
		{
			$paid_by='Pay';
			$invoice_number='';
			$is_invoice_paid='';
			$invoice_url='';
		}

		$entryDetails1[]= array(
			'amount' => number_format($row['amount'],2),
			'balance' =>  number_format($row['balance'],2),
			'payment_type' => $row['payment_type'],
			'date' => $row['date'],
			'number' => $invoice_number,
			'type' => $paid_by,
			'is_invoice_paid' => $is_invoice_paid,
			'invoice_url' => $invoice_url,
		);

	}
	$get_dues_30=get_dues_total_30_day($customer_id);
	$get_dues_60=get_dues_total_60_day($customer_id);
	$get_dues_90=get_dues_total_90_day($customer_id);


	$moreThan30=@$get_dues_30;
	$moreThan60=@$get_dues_60;
	$moreThan90=@$get_dues_90;

	$debtProfile=array(
		"moreThan30DaysDue"=>$moreThan30,
		"moreThan60DaysDue"=>$moreThan60,
		"moreThan90DaysDue"=>$moreThan90,
	);

	$data['companyDetails']=array('companyName'=>'D&D SNACK FOODS LTD','companyAddress'=>'UNIT FI LARKFIELD TRADING EST, NEW HYTHE LANE
		AYLESFORD KENT ME206SW','phoneNumber'=>'01622792470','vatNo'=>'619164534','wesite'=>'www.ddsnacks.co.uk');

	$data['ledgerDetails']=array('entryDetails'=>$entryDetails1,'fromDate'=>$start_date,'toDate'=>$end_date,'current_outstanding'=>0,'total_due'=>0,'printDate'=>date('Y-m-d'),'debtProfile'=>$debtProfile);

	if (count($data['ledgerDetails']) != '0') 
	{
		$response_arr = array(
			'data' => $data,
			'success' => true,
			'message' => 'ledger details found',
		);
		echo json_encode($response_arr);
	} else {
		$data = array();
		$response_arr = array(
			'data' => $data,
			'success' => false,
			'message' => 'ledger details not found',
		);
		echo json_encode($response_arr);
	}
}


public function changepassword()
{

	extract($_POST);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e' . $str;
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;

	$query = $this->db->select('id, password, salt')
	->where('id', $user_id)
	->limit(1)
	->get('sma_users');     

	$user = $query->row();

	$this->load->model('auth_model');

	$old_password_matches = $this->auth_model->hash_password_db($user->id, $old_pin);

	if ($old_password_matches === true) 
	{

		$hashed_new_password = $this->auth_model->hash_password($new_pin, $user->salt);
		$data                = [
			'password'      => $hashed_new_password,
			'remember_code' => null,
		];

		$successfully_changed_password_in_db = $this->db->update('sma_users', $data, ['id' => $user_id]);
		if ($successfully_changed_password_in_db) 
		{              

			$response_arr = array(
				'success' => true,
				'message' => 'Password Change Successfully',
			);
			echo json_encode($response_arr);
		} else 
		{

			$response_arr = array(
				'success' => false,
				'message' => 'Password Not Change',
			);
			echo json_encode($response_arr);
		}


	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Old Password Not Match',
		);
		echo json_encode($response_arr);
	}


}
public function changeprofile(){
	extract($_POST);
	extract($_FILES);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;

	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);
	if ($_FILES['avatar']['size'] > 0) 
	{
		$url_upload='/var/www/Multitenant_dds';

		$config['upload_path'] =  $url_upload.'/assets/uploads/thumbs/';
		$config['allowed_types'] = '*';
		$config['overwrite'] = false;
		$config['max_filename'] = 25;
		$config['encrypt_name'] = true;
		$this->upload->initialize($config);
		if (!$this->upload->do_upload('avatar')) 
		{
			echo $error = $this->upload->display_errors();								
		}
		$photo = $this->upload->file_name;		
		$profile = $photo;

		$update = ["avatar" =>$profile,];
		$this->db->where('id', $user_id);
		$this->db->update('sma_users', $update);



		$response_arr = array(
			'avatar' => $url.'/assets/uploads/thumbs/' .$photo,
			'success' => true,
			'message' => 'Profile picture updated.',
		);
		echo json_encode($response_arr);
	}else 
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to update profile picture',
		);
		echo json_encode($response_arr);
	} 

}

public function promotions_rules()
{




	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{
		
		$prefix=$str;
		$this->load->database($prefix);

	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}

	$user_id = $user->id;

	$get_customer_auth=$this->db->query("select group_id from sma_users where id='$user_id'")->result_array();

	$cdate=date('Y-m-d');
	$product_details=array();
	$details=array();


	if($get_customer_auth[0]['group_id']==3)
	{

		$data = $this->db->query("select * from sma_promos where start_date <= '$cdate' and end_date >= '$cdate'  ORDER BY id DESC")->result_array();

	}else
	{
		$data = $this->db->query("select * from sma_promos where start_date <= '$cdate' and end_date >= '$cdate' and type!='invoice_amount'  ORDER BY id DESC")->result_array();

	}

	foreach ($data as $row) 
	{
		if($row['type']=='buy_get')
		{
			$product=array(
				"name" => $row['name'],
				"buy_product_id" => $row['product2buy'],
				"buy_product_qty" => $row['quantity'],
				"free_product_id" => $row['product2get'],
				"free_product_qty" => $row['getquantity'],

			);
		}
		if($row['type']=='combo')
		{
			$pid=$row['id'];
			$buy_product=$this->db->query("select sma_products.id as buy_product_id from sma_productbuy left join sma_products on sma_products.id=sma_productbuy.productbuy_id  where sma_productbuy.promos_id='$pid'")->result_array();

			$get_product=$this->db->query("select sma_products.id as free_product_id,sma_promos.getquantity as free_product_qty from sma_productget left join sma_products on sma_products.id=sma_productget.product2get left join sma_promos on sma_promos.id=sma_productget.promo_id  where sma_productget.promo_id='$pid'")->result_array();

			$product=array(
				'buy_product_qty' => $row['quantity'],
				'free_product_qty' =>$row['getquantity'],
				'buy_product'=>$buy_product,
				'get_product'=>$get_product,
			);


		}


				// $product=array(
				// 	"name" => $row['name'],
				// 	"buy_product_id" => $row['product2buy'],
				// 	"buy_product_qty" => $row['quantity'],
				// 	"free_product_id" => $row['product2get'],
				// 	"free_product_qty" => $row['getquantity'],

				// );


		if($row['type'] == 'product_discount')
		{
			$id = $row['id'];
			$type = $row['type'];
			$percentage= $row['percentage'];
			$amt = $row['getamount'];
			$data_discount = $this->db->query("select sma_product_discount.product_id,sma_products.name,sma_products.code,sma_products.size,sma_products.price,sma_products.image FROM sma_product_discount LEFT JOIN sma_products ON sma_products.id=sma_product_discount.product_id where sma_product_discount.promo_id='$id'")->result_array();
			$product_details=array();

			foreach ($data_discount as $k)
			{
				$price= $k['price'];
				if($type == 'percentage')
				{
					$tdiscount =  number_format((float)($price-($price / 100) * $percentage), 2, '.', ''); 
				}else
				{
					$tdiscount =number_format((float)($price-$amt), 2, '.', ''); 
				}               		
				$product_details[]=array(
					"product_id" => $k['product_id'],
					"name" => $k['name'],  
					"code" => $k['code'], 
					"size" => $k['size'],  
					"price" => $k['price'],
					"image" => base_url('/assets/uploads/thumbs') . '/' . $k['image'],
					"discount_price" => $tdiscount,                        
				);
			}
			$product=array(
				"amount"=> '0',
				"percentage"=> $row['percentage'],
				'discount_amount' => $row['getamount'],
			); 

		}
		if($row['type'] == 'invoice_amount')
		{

			$product=array(
				"amount"=> $row['amount'],
				"percentage"=> $row['percentage'],
				'discount_amount' => $row['getamount'],

			); 

		}

		$details[]= array(
			'id' => $row['id'],
			'name' => $row['name'],
			'discount_type' => $row['type'],
			'description' => $row['description'],
			'subtype' => $row['gettype'],
			'discount_value' => $product,
			'product_details' => $product_details,
			'startDate' => $row['start_date'],
			'endDate' => $row['end_date'],

		);
		$product_details=array();
	}        
	if (count($details) != '0') 
	{
		$response_arr = array(
			'discounts_applicable' => $details,
			'success' => true,
			'message' => ' details found',
		);
		echo json_encode($response_arr);
	} else {
		$data = array();
		$response_arr = array(
			'discounts_applicable' =>$data,
			'success' => false,
			'message' => 'details not found',
		);
		echo json_encode($response_arr);
	}


}
public function get_product_stock_take()
{

	extract($_POST);
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$data=$this->db->query("select id from `sma_stock_take_uniqueid` where end_date IS NUll ORDER BY id DESC")->row_array(); 
	$uniqueid = $data['id'];
	if($category_id=="")
	{
		$result = $this->db->query("select id,code,name,image,category_id,subcategory_id,size,price,cost,bay,rack,inner_ean_number,outer_ean_number,inner_ean_number,quantity as available_qty_box,physical_qty as physical_qty_box,split,split_quantity as available_qty_piece,split_physical_qty as physical_qty_piece from sma_stock_take where uniqueId='$uniqueid'")->result();
	}else
	{
		$result = $this->db->query("select id,code,name,image,category_id,subcategory_id,size,price,cost,bay,rack,inner_ean_number,outer_ean_number,inner_ean_number,quantity as available_qty_box,physical_qty as physical_qty_box,split,split_quantity as available_qty_piece,split_physical_qty as physical_qty_piece from sma_stock_take where category_id='$category_id' and uniqueId='$uniqueid'")->result();
	}

	foreach ($result as $results) 
	{

		$results->image = base_url('/assets/uploads/thumbs') . '/' . $results->image;
		$results->available_qty_box =  (int)($results->available_qty_box);
		$results->physical_qty_box =  (int)($results->physical_qty_box);
		$results->available_qty_piece =  (int)($results->available_qty_piece);
		$results->physical_qty_piece =  (int)($results->physical_qty_piece);
	}
	$data = new stdClass();
	$data->result = $result;

	if (count($result)==0) 
	{				


		$response_arr = array(				
			'success' => false,
			'message' => 'Product Not Available',
		);
		echo json_encode($response_arr);
	}else 
	{
		$response_arr = array(
			'data' => $result,
			'success' => true,
			'message' => 'get Product list',
		);
		echo json_encode($response_arr);
	} 


}

public function update_physical_stock()
{
	extract($_POST);	

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];

	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$Type= strtolower($qtyType);

	if ($product_id!="") 
	{	
		if ($Type == "box"){
			$result = $this->db->query("select physical_qty from sma_stock_take where id='$product_id'")->result_array();
			$physicalqty=$result[0]['physical_qty'] + $physical_qty;	
			$update = ["physical_qty" =>$physicalqty,'bay'=>$bay,'rack'=>$rack,'inner_ean_number'=>$innerEan,'outer_ean_number'=>$outerEan];
		}else{
			$result = $this->db->query("select split_physical_qty from sma_stock_take where id='$product_id'")->result_array();
			$physicalqty=$result[0]['split_physical_qty'] + $physical_qty;	
			$update = ["split_physical_qty" =>$physicalqty,'bay'=>$bay,'rack'=>$rack,'inner_ean_number'=>$innerEan,'outer_ean_number'=>$outerEan];
		}

		$this->db->where('id', $product_id);
		$this->db->update('sma_stock_take', $update);

		$response_arr = array(				
			'success' => true,
			'message' => 'Stock updated.',
		);
		echo json_encode($response_arr);

	}else 
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to update Stock',
		);
		echo json_encode($response_arr);
	} 

}

public function add_orders_front_sheet(){

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;
	$result = $this->db->query("select sma_sales.id as id,sma_sales.date,sma_sales.reference_no,sma_sales.created_by, sma_sales.customer,sma_companies.company,sma_sales.grand_total as cashCollected FROM `sma_sales` LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id WHERE sma_sales.created_by='$user_id' and sma_sales.sale_status='New' and sma_sales.id NOT IN (SELECT sale_id FROM sma_daily_sales_sheet)")->result();

	foreach ($result as $results) 
	{
		$results->cashCollected = number_format(sale_person_collected($results->created_by, $results->id), 2);
	}
	$data = new stdClass();
	$data->result = $result;
	if (count($result)==0) 
	{
		$response_arr = array(				
			'success' => false,
			'message' => 'order details not found',
		);
		echo json_encode($response_arr);
	}else 
	{
		$response_arr = array(
			'data' => $result,
			'success' => true,
			'message' => 'order details found',
		);
		echo json_encode($response_arr);
	} 
}

public function create_edit_front_sheet(){

	extract($_POST);	
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$user_id = $user->id;
	$order_details = json_decode(file_get_contents('php://input'), true);

	$frontSheetId=$order_details['frontSheetId'];

	if (count($order_details['frontsheet_details']) != 0) 
	{

		$daily_sheet = $this->db->query("SELECT dss_no FROM `sma_daily_sales_sheet` ORDER BY `id` DESC")->row_array();

		$dss_no = $daily_sheet['dss_no'];
		$str2 = substr($dss_no, 2)+1;
		$dss_no='FS'.$str2;
		for ($i = 0; $i < count($order_details['frontsheet_details']); $i++) {
			$order_id = $order_details['frontsheet_details'][$i]['order_id'];
			$cash_collected = $order_details['frontsheet_details'][$i]['cashToBeCollectedByDriver'];
			if($frontSheetId==''){
				$data['sale_id'] = $order_id;
				$data['dss_no'] = $dss_no; 
				$data['date'] = date('Y-m-d');
				$data['cash_collected_by_driver'] = $cash_collected; 	
				$data['created_by'] = $user_id;
				$this->db->insert('sma_daily_sales_sheet',$data);

			}else{
				$check_order = $this->db->query("select id from sma_daily_sales_sheet where sale_id='$order_id' and dss_no='$frontSheetId'")->result_array();

				if(count($check_order)==0){
					$data['sale_id'] = $order_id;
					$data['dss_no'] = $frontSheetId; 
					$data['date'] = date('Y-m-d');
					$data['cash_collected_by_driver'] = $cash_collected; 
					$data['created_by'] = $user_id;
					$this->db->insert('sma_daily_sales_sheet',$data);

				}else{

					$update = array('cash_collected_by_driver' => $cash_collected);
					$this->db->where('sales_id', $order_id);
					$this->db->where('dss_no', $frontSheetId);
					$this->db->update('sma_daily_sales_sheet', $update);
				}
			}

		}
		$response_arr = array(				
			'success' => true,
			'message' => 'save front sheet.',
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(				
			'success' => false,
			'message' => 'order details not found',
		);
		echo json_encode($response_arr);
	}
}

public function front_sheet_list(){
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);	
	$user_id = $user->id;

	$result = $this->db->query("select sma_daily_sales_sheet.dss_no as id,sma_daily_sales_sheet.date,dss_no,COUNT(sma_daily_sales_sheet.sale_id) as total,sma_users.first_name,sma_users.last_name,sma_users.last_name as status  FROM `sma_daily_sales_sheet` LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id LEFT JOIN sma_users ON sma_users.id = sma_sales.created_by where sma_daily_sales_sheet.created_by='$user_id' GROUP by dss_no ORDER BY sma_daily_sales_sheet.id DESC")->result();
	foreach ($result as $results) 
	{
		$dss_no=$results->dss_no;
		$total=$this->db->query("select COUNT(sma_daily_sales_sheet.sale_id)as total FROM `sma_daily_sales_sheet` where dss_no='$dss_no'")->row_array();
		$results->total = $total['total'];
		$sheet = $this->db->query("select sma_sales.id as id from sma_sales where sma_sales.sale_status ='New' and sma_sales.id IN (SELECT sale_id FROM sma_daily_sales_sheet where sma_daily_sales_sheet.dss_no='$dss_no') ORDER BY sma_sales.id DESC")->result_array();
		if(count($sheet)!=0){        
			$results->status = 'pending';
		}else{
			$results->status = 'approved';
		}
	}
	$data = new stdClass();
	$data->result = $result;

	if (count($result)==0) 
	{
		$response_arr = array(				
			'success' => false,
			'message' => 'Front sheet details not found',
		);
		echo json_encode($response_arr);
	}else 
	{
		$response_arr = array(
			'data' => $result,
			'success' => true,
			'message' => 'Front sheet details found',
		);
		echo json_encode($response_arr);
	} 
}
public function front_sheet_order_list(){
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);	
	$user_id = $user->id;
	$dssid = $this->input->post('frontsheetID');

	$result = $this->db->query("select sma_sales.id as id,sma_sales.date, sma_sales.customer_id,sma_sales.reference_no,sma_sales.created_by,sma_sales.total_discount,sma_sales.customer,sma_sales.grand_total as cashCollected, sma_daily_sales_sheet.cash_collected_by_driver as cashToBeCollectedByDriver,sma_companies.accound_no FROM sma_daily_sales_sheet LEFT JOIN sma_sales ON sma_sales.id = sma_daily_sales_sheet.sale_id LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id WHERE sma_daily_sales_sheet.dss_no='$dssid'")->result();
	foreach ($result as $results) 
	{
		$results->cashCollected = number_format(sale_person_collected($results->created_by, $results->id), 2);
	}
	$data = new stdClass();
	$data->result = $result;
	if (count($result)==0) 
	{
		$response_arr = array(				
			'success' => false,
			'message' => 'order details not found',
		);
		echo json_encode($response_arr);
	}else 
	{
		$response_arr = array(
			'data' => $result,
			'success' => true,
			'message' => 'order details found',
		);
		echo json_encode($response_arr);
	} 
}

public function lock_picking_list(){
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$picklist_number = $this->input->post('picklist_number');
	    //picker_id='$user_id' and
	$result = $this->db->query("select id FROM sma_sales where picklist_number='$picklist_number'")->result();
	if(count($result)==0){

		$response_arr = array(				
			'success' => false,
			'message' => 'Picking list not found',
		);
		echo json_encode($response_arr);

	}else{

		foreach ($result as $results) 
		{
			$id = $results->id;
			$update = array('is_lock' => 1,'lock_by'=>$user_id,'lock_on'=>date('Y-m-d H:i:s'),);
// 			$this->db->where('picker_id', $user_id);
			$this->db->where('picklist_number', $picklist_number);
			$this->db->update('sma_sales', $update);
		}
		$response_arr = array(

			'success' => true,
			'message' => 'Picking list locked',
		);
		echo json_encode($response_arr);
	}

}

public function past_orders_for_productId(){
	extract($_POST);

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$productId = $this->input->post('productId');
	$customerId = $this->input->post('customerId');
	$olddate=date("Y-m-d",strtotime("-18 Months"));
	$cdate=date("Y-m-d");  
	$products = $this->db->query("select id FROM sma_products where parent_id='$productId'")->row_array(); 
	$parent_id= $products['id'];
	$result= $this->db->query("select sma_sale_items.id as itemId,sma_sales.reference_no,sma_sales.invoice_date,sma_sale_items.subtotal,sma_sale_items.product_id,sma_sale_items.quantity,sma_sale_items.unit_price,sma_sale_items.order_type,sma_sale_items.item_tax FROM `sma_sales` LEFT JOIN sma_sale_items ON sma_sale_items.sale_id= sma_sales.id WHERE sma_sales.sale_status='Delivered' and sma_sale_items.product_id in('$productId','$parent_id') and sma_sales.customer_id='$customerId' and sma_sale_items.quantity!='0' and sma_sale_items.add_return_order ='0' and (sma_sale_items.promo_id IS NULL || sma_sale_items.promo_id='0' ) and sma_sale_items.is_promoted ='0' and sma_sale_items.is_delete ='0' and sma_sales.invoice_date >='$olddate' and sma_sales.invoice_date <= '$cdate'")->result(); 
	foreach ($result as $results) 
	{
		$results->quantity = (int)($results->quantity);
		$results->subtotal =number_format($results->unit_price * $results->quantity,2);
		$results->unit_price =number_format($results->unit_price,2);
		$results->item_tax =number_format($results->item_tax,2);

	}
	$data = new stdClass();
	$data->result = $result;
	if(count($result)==0){
		$response_arr = array(
			'success' => false,
			'message' => 'Order Details not found',
			'orderDetails'=>$result,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => true,
			'message' => 'Order Details found',
			'orderDetails'=>$result,
		);
		echo json_encode($response_arr);
	}
}

public function return_request()
{
	extract($_POST);		
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$this->load->model('site');
	$this->load->model('returns_model');	    
	$user_id = $user->id;
	$order_details = json_decode(file_get_contents('php://input'), true);		
	$products=array();
	if (count($order_details['returnProductDetails']) != 0){ 
		$date             =  date('Y-m-d H:i:s');
		$reference        = $this->input->post('reference_no') ? $this->input->post('reference_no') : $this->site->getReference('rep');
		$warehouse_id     = $this->input->post('warehouse');
		$customer_id      = $order_details['customerId'];
		$total_items      = count($order_details['returnProductDetails']);
		$customer_details = $this->site->getCompanyByID($customer_id);
		$customer         = !empty($customer_details->company) && $customer_details->company != '-' ? $customer_details->company : $customer_details->name;
		$note             = @$order_details['note'];
		$staff_note       = $this->input->post('staff_note');
		$shipping         = $this->input->post('shipping') ? $this->input->post('shipping') : 0;

		$total            = 0;
		$product_tax      = 0;
		$product_discount = 0;
		$gst_data         = [];
		$total_cgst       = $total_sgst       = $total_igst       = 0;
		for ($i = 0; $i < count($order_details['returnProductDetails']); $i++){
			$return_item_id       = $order_details['returnProductDetails'][$i]['itemId'];
			$return_qty            = $order_details['returnProductDetails'][$i]['returnQty'];
			$return_type           = strtolower($order_details['returnProductDetails'][$i]['returnPieceType']);
			$returnReason          = $order_details['returnProductDetails'][$i]['returnReason'];

			$sale_items_data = $this->db->query("select sale_id,product_id,product_code,product_name,product_type,product_unit_id,product_unit_code,real_unit_price,unit_price,quantity,item_tax,tax_rate_id,tax,order_type FROM `sma_sale_items` WHERE id='$return_item_id'")->row_array();

			$item_id            = $sale_items_data['product_id'];
			$order_type         = $sale_items_data['order_type'];
			$item_type          = $sale_items_data['product_type'];
			$item_code          = $sale_items_data['product_code'];
			$item_name          = $sale_items_data['product_name'];
			$sale_id            = $sale_items_data['sale_id'];

			$item_option        = isset($_POST['product_option']) && $_POST['product_option'] != 'false' && $_POST['product_option'] != 'null' ? $_POST['product_option'] : null;
			$real_unit_price    = number_format($sale_items_data['real_unit_price'],2);
			$unit_price         = number_format($sale_items_data['unit_price'],2);
			$item_unit_quantity = $sale_items_data['quantity'];
			$return_quantity    = $order_details['returnProductDetails'][$i]['returnQty'];

			$item_serial        = @$_POST['serial'][$r]           ?? '';
			$item_tax_rate      = @$_POST['product_tax'][$r]      ?? null;
			$item_discount      = @$_POST['product_discount'][$r] ?? null;
			$item_unit          = @$_POST['product_unit'][$r] ?? '';
			$item_quantity      = $sale_items_data['quantity'];

			$update=array('add_return_order'=>1);
			$this->db->where('id',$return_item_id);
			$this->db->update('sma_sale_items',$update);

			if (isset($item_code) && isset($real_unit_price) && isset($unit_price) && isset($item_quantity)) 
			{
				$product_details  = $item_type != 'manual' ? $this->site->getProductByCode($item_code) : null;
				$pr_discount      = $this->site->calculateDiscount($item_discount, $unit_price);
				$unit_price       = number_format($unit_price - $pr_discount,2);
				$item_net_price   = $unit_price;
				$pr_item_discount = number_format($pr_discount * $item_unit_quantity,2);
				$product_discount += $pr_item_discount;
				$pr_item_tax = $item_tax = 0;
				$tax         = '';
				$product_data = $this->db->query("select pack,tax_rate from sma_products where id='$item_id'")->row_array();

				$tax_rate = $product_data['tax_rate'];
				$tax_parcent = $this->db->query("select rate from sma_tax_rates where id='$tax_rate'")->row_array();

				if ($order_type == 'box' && $return_type == 'piece') 
				{

					$tax = number_format((($unit_price * 1) * $tax_parcent['rate']) / 100, 2);

					$unitprice = ($unit_price)/$product_data['pack'];
					$item_net_price = $unitprice;
					$subtotal = $unitprice * $return_qty;
					$item_tax = number_format(($subtotal * $tax_parcent['rate']) / 100, 2);
					$product_tax += $item_tax;
					$subtotal = number_format(($subtotal + $item_tax), 2);

				}else
				{

					$tax = number_format((($item_net_price * 1) * $tax_parcent['rate']) / 100, 2);
					$item_tax= $tax*$return_quantity;
					$product_tax +=  $item_tax;
					$subtotal = (($item_net_price * $return_quantity) + $item_tax);
					$unit     = $this->site->getUnitByID($item_unit);
				}

				$product = [
					'product_id'        => $item_id,
					'product_code'      => $item_code,
					'product_name'      => $item_name,
					'product_type'      => $item_type,
					'option_id'         => 1,
					'net_unit_price'    => $item_net_price,
					'unit_price'        =>number_format($item_net_price, 2),
					'quantity'          => $item_quantity,
					'return_quantity'   => $return_quantity,
					'product_unit_id'   => $sale_items_data['product_unit_id'],
					'product_unit_code' => $sale_items_data['product_unit_code'],
					'unit_quantity'     => $return_quantity,
					'warehouse_id'      => 1,
					'item_tax'          => $item_tax,
					'tax_rate_id'       => $sale_items_data['tax_rate_id'],
					'tax'               => $item_tax,
					'discount'          => $item_discount,
					'item_discount'     => $pr_item_discount,
					'subtotal'          => number_format($subtotal, 2),
					'serial_no'         => $item_serial,
					'real_unit_price'   => $real_unit_price,
					'return_type'       => $return_type,
					'returnReason'      => $returnReason,
					'sale_id'           => $sale_id,
				];

				$products[] = ($product + $gst_data);
				$total += number_format(($item_net_price * $return_quantity), 2);
			}

		}


		$order_discount = $this->site->calculateDiscount($this->input->post('order_discount'), ($total + $product_tax), true);
		$total_discount = number_format(($order_discount + $product_discount), 2);
		$order_tax      = $this->site->calculateOrderTax($this->input->post('order_tax'), ($total + $product_tax - $order_discount));
		$total_tax      = number_format(($product_tax + $order_tax), 2);
		$grand_total    = number_format(($total + $total_tax + $shipping - $order_discount), 2);
		$data           = [
			'date'              => $date,
			'reference_no'      => 'RR'.$reference,
			'customer_id'       => $customer_id,
			'customer'          => $customer,
			'biller_id'         => 1,
			'request_for'       => @$request_for,
			'biller'            => '',
			'warehouse_id'      => $warehouse_id,
			'note'              => $note,
			'staff_note'        => $staff_note,
			'total'             => $total,
			'return_quantity'   => $return_quantity,
			'product_discount'  => $product_discount,
			'order_discount_id' => $this->input->post('order_discount'),
			'order_discount'    => $order_discount,
			'total_discount'    => $total_discount,
			'product_tax'       => $product_tax,
			'order_tax_id'      => $this->input->post('order_tax'),
			'order_tax'         => $order_tax,
			'total_tax'         => $total_tax,
			'grand_total'       => $grand_total,
			'total_items'       => $total_items,
			'paid'              => 0,
				//'created_by'        => $this->session->userdata('user_id'),
			'created_by'        => $user_id,
			'trip_id'=>$order_details['trip_id'],
			'hash'              => hash('sha256', microtime() . mt_rand()),
		];
	}
    //	print_r($product);exit();
	$data=$this->returns_model->addReturn($data, $products);
	if($data==1)
	{
		$response_arr = array(
			'success' => true,
			'message' => 'Return request submitted ',

		);
		echo json_encode($response_arr);
	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Return request not submitted',

		);
		echo json_encode($response_arr);
	}
}

public function return_request_list()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$result = $this->db->query("select sma_returns.id, sma_returns.date,sma_returns.reference_no,customer_id,customer,sma_companies.accound_no,note,grand_total,total_items,is_accept as status from sma_returns LEFT JOIN sma_companies on sma_companies.id= sma_returns.customer_id order by sma_returns.id desc")->result();
	foreach ($result as $results) 
	{
		$results->grand_total =number_format($results->grand_total,2);
		$status=$results->status;
		if($status=='N'){
			$results->status='Pending';	
		}
		if($status=='Y'){
			$results->status='Accepted';	
		}
		if($status=='R'){
			$results->status='Rejected';	
		}
	}
	$data = new stdClass();
	$data->result = $result;
	if(count($result)==0){
		$response_arr = array(
			'success' => false,
			'message' => 'Return Details not found',				
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => true,
			'message' => 'Return Details found',
			'returnDetails'=>$result,
		);
		echo json_encode($response_arr);
	}
}

public function return_request_details()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$return_id = $this->input->post('return_request_id');

	$this->db->select('sales.invoice_date as invoiceDate,
		sales.reference_no as invoiceNo,
		ri.product_name as productName,
		ri.product_code as productCode,
		p.size as size,
		p.image as image,
		ri.return_quantity as returnedQty,
		ri.return_type as returnQtyType,
		ri.unit_price as purchaseUnitPrice,
		ri.unit_price as totalPrice,
		ri.item_tax as totalVat,
		ri.subtotal as subtotal,
		ri.returnReason as returnReason');
	$this->db->from('sma_return_items as ri');		
	$this->db->join('sma_products as p','p.id = ri.product_id');
	$this->db->join('sma_sales as sales','sales.id = ri.sale_id');
	$this->db->where('ri.return_id', $return_id);
	$result = $this->db->get()->result();
	foreach ($result as $results) 
	{
		$results->image = base_url('/assets/uploads/thumbs') . '/' . $results->image;
		$results->purchaseUnitPrice = number_format($results->purchaseUnitPrice,2);
		$results->totalPrice = number_format($results->purchaseUnitPrice * $results->returnedQty,2);
		$results->totalVat = number_format($results->totalVat,2);
		$results->subtotal = number_format($results->subtotal,2);
	}
	$data = new stdClass();
	$data->result = $result;
	if(count($result)!=0)
	{
		$response_arr = array(
			'success' => true,
			'message' => 'Details found',
			'productsReturned' => $result,
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Details not found',
		);
		echo json_encode($response_arr);
	}       
}

public function vehicle_to_manifest()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$mId = $this->input->post('manifestId');
	$vId = $this->input->post('vehicleId');

	if($mId!=''&& $vId!='')
	{
		$data = array(
			'vehicle_id' =>$vId
		);
		$this->db->where('id',$mId);
		$this->db->update('sma_manifest',$data);
		$response_arr = array(
			'success' => true,
			'message' => 'Vehicle Assigned succesfully'
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Failed',
		);
		echo json_encode($response_arr);
	}       
}


public function get_vehicles()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$this->db->select('id as vehicleId,model_name as modelName,registration_number as registrationNo');
	$query = $this->db->get('sma_vehicle')->result();

	if(count($query) != 0)
	{
		$response_arr = array(
			'data' => $query,
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


public function get_company_details()
{

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$result= $this->db->query("select shop_id as company_id,shop_name as company_name,description,phone,email,logo,cookie_message FROM `sma_shop_settings` where shop_id='1'")->row_array();

	$result['logo']= base_url('assets/uploads/logos/'.$result['logo']);

	$ddata['company_details'] = $result;
	if(!empty($result))
	{  			
		$response_arr = array(
			'success' =>true,
			'message' => 'company details found',
			'data'=> $ddata,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}

public function get_business_types()
{
	$this->load->model('companies_model');
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$type_of_business = $this->companies_model->get_type_of_business();
	if(count($type_of_business)!= 0)
	{
		$response_arr = array(
			'businessTypes' => $type_of_business,
			'success' => true,
			'message' => 'business types found'
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'unable to fetch business types',
		);
		echo json_encode($response_arr);
	}

}
public function get_business_categories()
{
	$this->load->model('companies_model');
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$business_category = $this->companies_model->getbusiness_category();
	if(count($business_category)!= 0)
	{
		$response_arr = array(
			'businessCategories' => $business_category,
			'success' => true,
			'message' => 'business categories found'
		);
		echo json_encode($response_arr);
	}
	else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'unable to fetch business categories',
		);
		echo json_encode($response_arr);
	}

}

public function add_edit_customer_details()
{	

	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	if($str=='dds')
	{

		$prefix=$str;
		$this->load->database($prefix);
	}else
	{
		$token = 'e'.$str;

		$user =jwt::decode($token,$this->config->item('jwt_key'));
		$prefix=$user->prefix;
		$this->load->database($prefix);
	}
	// $token='e'.$str;
	// $user =jwt::decode($token,$this->config->item('jwt_key'));	
	// $prefix=$user->prefix;
	// $this->load->database($prefix);
	$user_id = $user->id;
	$this->load->model('site');
	$this->load->model('companies_model');
	$this->load->model('Auth_model');
	$email  = $this->input->post('emailId');
	$company = $this->input->post('companyName');
	$firstName = $this->input->post('firstName');
	$lastName = $this->input->post('lastName');
	$isPo = $this->input->post('isPo');	
	$userName= $this->input->post('userName');
	$addressLine1= $this->input->post('addressLine1');
	$city= $this->input->post('city');
	$country= $this->input->post('country');
	$typeOfBusiness= $this->input->post('typeOfBusiness');
	$businessCategory= $this->input->post('businessCategory');

	if($user_id!='')
	{
		if($company!='')
		{

			if ($firstName!='')
			{

				if ($lastName!='')
				{	
					if($email!='')
					{
						$validateEmail = $this->validateEmail($email);
						if ($validateEmail==''){
							$response_arr = array(
								'success' => false,
								'message' => 'invalied email.',
							);
							echo json_encode($response_arr);
							exit();
						}
						// $check_result = $this->db->query("select id from sma_companies where email ='$email'")->result();
						// if (count($check_result)!=0){
						// 	$response_arr = array(
						// 		'success' => false,
						// 		'message' => 'email already exist.',
						// 	);
						// 	echo json_encode($response_arr);
						// 	exit();
						// }
					}

					if ($isPo ==''){
						$response_arr = array(
							'success' => false,
							'message' => 'po number in required',
						);
						echo json_encode($response_arr);
						exit();
					}				
					if ($isPo !='1' && $isPo !='0'){
						$response_arr = array(
							'success' => false,
							'message' => 'Invalid Po Number',
						);
						echo json_encode($response_arr);
						exit();
					}
					if ($userName==''){
						$response_arr = array(
							'success' => false,
							'message' => 'user name in required',
						);
						echo json_encode($response_arr);
						exit();
					}

					if ($userName!=''){
						$check_user = $this->db->query("select id from sma_users where username ='$userName'")->result();
						if (count($check_user)!='0') {
							$response_arr = array(
								'success' => false,
								'message' => 'username already exist.',
							);
							echo json_encode($response_arr);
							exit();
						}

					}

					if ($addressLine1=='') {
						$response_arr = array(
							'success' => false,
							'message' => 'addressLine1 in required.',
						);
						echo json_encode($response_arr);
						exit();
					}

					if ($city=='') {
						$response_arr = array(
							'success' => false,
							'message' => 'city in required.',
						);
						echo json_encode($response_arr);
						exit();
					}

					if ($country=='') {
						$response_arr = array(
							'success' => false,
							'message' => 'country in required.',
						);
						echo json_encode($response_arr);
						exit();
					}	


					if($typeOfBusiness==''){
						$response_arr = array(
							'success' => false,
							'message' => 'type Of Business in required.',
						);
						echo json_encode($response_arr);
						exit();
					}
					if ($typeOfBusiness!=''){
						$check_type_of_business = $this->db->query("select id from sma_type_of_business where id ='$typeOfBusiness'")->result();

						if (count($check_type_of_business)=='0') {
							$response_arr = array(
								'success' => false,
								'message' => 'invalied type Of Business.',
							);
							echo json_encode($response_arr);
							exit();
						}

					}	
					if($businessCategory==''){
						$response_arr = array(
							'success' => false,
							'message' => 'business Category in required.',
						);
						echo json_encode($response_arr);
						exit();
					}
					if ($businessCategory!=''){
						$check_businessCategory = $this->db->query("select id from sma_business_category where id ='$businessCategory'")->result();
						if (count($check_businessCategory)=='0') {
							$response_arr = array(
								'success' => false,
								'message' => 'invalied Business Category.',
							);
							echo json_encode($response_arr);
							exit();
						}
					}	

					$default_delivery=$this->input->post('default_delivery');
					$deliverydate='';
					if($default_delivery == 1){

						$deliverydate=Date('Y-m-d', strtotime('+3 days'));

					}elseif($default_delivery == 2){

						$deliverydate=Date('Y-m-d', strtotime('+1 days'));

					}elseif($default_delivery == 3){

						$deliverydate=Date('Y-m-d', strtotime('+7 days'));
					}

					$credit_facility = $this->input->post('credit_facility');

					$day_wise = $this->input->post('day_wise');
					$total_day = $this->input->post('total_day');
					$invoice_wise = $this->input->post('invoice_wise');
					$total_invoice = $this->input->post('total_invoice');
					$amount_wise = $this->input->post('amount_wise');
					$total_amount = $this->input->post('total_amount');
					$product_price = $this->input->post('product_price');
					if($product_price ==''){
						$product_price='0';
					}
					if($day_wise == ''){
						$total_day = '';
					}

					if($amount_wise ==''){
						$total_amount = '';
					}

					if($invoice_wise ==''){
						$total_invoice = '';
					}

					$people = array();
					$person = array();
					$credit_type_name='Cash';
					if($day_wise!=''){
						$credit_type_name='Credit';
						$person['name'] = $day_wise;
						$person['total_day'] = $total_day;
						$people[] = $person;
						$person = array();
					}
					if($invoice_wise!=''){
						$credit_type_name='TC';
						$person['name'] = $invoice_wise;
						$person['total_amount'] = $total_amount;
						$people[] = $person;
						$person = array();
					}
					if($amount_wise!=''){
						$credit_type_name='Cash';
						$person['name'] = $amount_wise;
						$person['total_invoice'] = $total_invoice;
						$people[] = $person;
					}
					$credit_type = json_encode($people);

					$cg   = $this->site->getCustomerGroupByID(1);
					$pg   = $this->site->getPriceGroupByID($this->input->post('price_group'));
					$data = [
						'name'                => $this->input->post('firstName') . ' ' . $this->input->post('lastName'),
						'email'               => $this->input->post('emailId'),
						'group_id'            => '3',
						'group_name'          => 'customer',
						'customer_group_id'   => 1,
						'customer_group_name' => $cg->name,
						'price_group_id'      => $this->input->post('price_group') ? $this->input->post('price_group') : null,
						'price_group_name'    => $this->input->post('price_group') ? $pg->name : null,
						'company'             => $this->input->post('companyName'),
						'address'             => $this->input->post('addressLine1'),
						'vat_no'              => $this->input->post('vatNo'),
						'city'                => $this->input->post('city'),
						'state'               => $this->input->post('county'),
						'postal_code'         => $this->input->post('postalCode'),
						'country'             => $this->input->post('country'),
						'phone'               => $this->input->post('phone'),
						'cf1'                 => $this->input->post('cf1'),
						'cf2'                 => $this->input->post('cf2'),
						'cf3'                 => $this->input->post('cf3'),
						'cf4'                 => $this->input->post('cf4'),
						'cf5'                 => $this->input->post('cf5'),
						'cf6'                 => $this->input->post('cf6'),
						'gst_no'              => $this->input->post('gst_no'),
						'is_po'               => $this->input->post('isPo'),
						'status'              =>'0',
						'route'               => $this->input->post('route'),
						'type_of_business'    => $this->input->post('typeOfBusiness'),
						'business_category'   => $this->input->post('businessCategory'),
						'default_delivery'    => $default_delivery,
						'deliverydate'        => $deliverydate,
						'accound_no'          => $this->input->post('accound_no'),
						'credit_facility'     => $credit_facility,
						'credit_type'         => $credit_type,
						'product_price_type'   => $product_price,
						'credit_type_name'    => $credit_type_name,
					];
					$data1 = [
						'line1'       => $this->input->post('addressLine1'),
						'line2'       => $this->input->post('addressLine2'),
						'city'        => $this->input->post('city'),
						'postal_code' => $this->input->post('postalCode'),
						'state'       => $this->input->post('county'),
						'country'     => $this->input->post('country'),
						'phone'       => $this->input->post('phone'),
					]; 


					if ($cid = $this->companies_model->addCompany($data,$data1)) {

						$company = $this->companies_model->getCompanyByID1($cid);
						$active                  = '0';
						$notify                  = '1';
						list($username, $domain) = explode('@', $this->input->post('emailId'));
						$username1 = $this->input->post('userName');
						$email                   = strtolower($this->input->post('emailId'));
						$password                = '123456';
						$salt='';
							//$ip_address = $this->_prepare_ip($this->input->ip_address());
							// $additional_data         = [
							// 	'first_name' => $this->input->post('firstName'),
							// 	'last_name'  => $this->input->post('lastName'),
							// 	'phone'      => $this->input->post('phone'),
							// 	'gender'     => $this->input->post('gender'),
							// 	'company_id' => $company->id,
							// 	'company'    => $company->company,
							// 	'group_id'   => 3,
							// ];
							// $this->load->library('ion_auth');
							//print_r($active);exit();
							//$this->ion_auth->register($username1, $password, $email, $additional_data, $active, $notify);
							$additional_data         = [
							'first_name' => $this->input->post('firstName'),
							'last_name'  => $this->input->post('lastName'),
							'phone'      => $this->input->post('phone'),
							'gender'     => $this->input->post('gender'),
							'company_id' => $company->id,
							'company'    => $company->company,
							'group_id'   => 3,
							'username'   => $username1,
							'password'   => $this->auth_model->hash_password($password,$salt),
							'email'      => $email,
					            //'ip_address' => $ip_address,
							'created_on' => time(),
							'last_login' => time(),

						];
						$this->db->insert('sma_users', $additional_data);
						$uid = $this->db->insert_id();
						if($prefix == 'tsc'){

							$config_key = $prefix . '_url';
							$url2 = $this->config->item($config_key);
							$url= $url2.'/shop/new_customer/'.$uid;
							$headers = array('AuthToken:' ."4ekLHJqvq2LNK3xcQhGr2Pcz", 'Content-Type: application/json');
							//$fields = json_encode($inv);

							$ch = curl_init();

							curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
							curl_setopt($ch, CURLOPT_URL, $url);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of outputting it
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL verification (use with caution)

							$response = curl_exec($ch);

							curl_close($ch);
					}
					$response_arr = array(
						'success' => true,
						'message' => 'Customer details saved successfully',
					);
					echo json_encode($response_arr);
					}else{
						$response_arr = array(
							'success' => false,
							'message' => 'customer details not save',
						);
						echo json_encode($response_arr);
					}

				}else{
					$response_arr = array(
						'success' => false,
						'message' => 'last name in required',
					);
					echo json_encode($response_arr);
				}

			}else{
				$response_arr = array(
					'success' => false,
					'message' => 'first name in required',
				);
				echo json_encode($response_arr);
			}
		}else{

			$response_arr = array(
				'success' => false,
				'message' => 'company name in required',
			);
			echo json_encode($response_arr);
		}
	}else{

		$response_arr = array(
			'success' => false,
			'message' => 'invalied token',
		);
		echo json_encode($response_arr);
	}
}

public function get_all_customer_list()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$this->load->model('companies_model');	
	$result=$this->companies_model->get_all_customer_list();
	if(!empty($result))
	{  
		$response_arr = array(
			'success' =>true,
			'message' => 'Customer details found',
			'customer_details'=>$result,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records for matching pattern',
		);
		echo json_encode($response_arr);
	}
}

function validateEmail($email) 
{
	if (preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", $email)) 
	{
		return true;  
	}
	return false;
}


public function get_company_setting()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$user_details = $this->db->query("SELECT id FROM sma_users WHERE id = ? AND active = 1", array($user_id))->row_array();
	if (empty($user_details)) {
		$response_arr = array(
			'success' => false,
			'message' => 'User inactive or unauthorized',
		);
		echo json_encode($response_arr);
        http_response_code(401); // Unauthorized
        return;
    }
    
    $result=$this->db->query("select * from sma_company_setting")->row_array();
    if(!empty($result))
    {  
    	$response_arr = array(
    		'success' =>true,
    		'message' => 'company setting details found',
    		'company_setting'=>$result,
    	);
    	echo json_encode($response_arr);
    }else{
    	$response_arr = array(
    		'success' => false,
    		'message' => 'No records for matching pattern',
    	);
    	echo json_encode($response_arr);
    }
    
}

public function catalogue_details()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$config_key = $prefix . '_url';
	$url = $this->config->item($config_key);
	
	$result=$this->db->query("select id,date as createDate,ref_num,pdf_file as pdfUrl,option as catalogueName from sma_brochure where status='1' order by id desc")->result();
	foreach ($result as $results) 
	{

		if ($results->catalogueName == '1') 
		{
			$option='Products Wise';
		}elseif ($results->catalogueName == '2') 
		{
			$option='Categories Wise';
		}elseif ($results->catalogueName == '3') 
		{
			$option='Brands Wise';
		}elseif ($results->catalogueName == '4') 
		{
			$option='Promoted Wise';
		}elseif ($results->catalogueName == '5') 
		{
			$option='New Arrivals';
		}
		$results->catalogueName = $option;
		$results->pdfUrl = $url . '/assets/uploads/inv_pdf/' . $results->pdfUrl;
	}
	if(!empty($result))
	{  
		$response_arr = array(
			'success' =>true,
			'message' => 'Catalogue details found',
			'catalogueDetails'=>$result,
		);
		echo json_encode($response_arr);
	}else{
		$response_arr = array(
			'success' => false,
			'message' => 'No records found',
		);
		echo json_encode($response_arr);
	}
}


///trip code start
//manifest_list
public function get_manifest_list_to_add_trip()
{
	$secure_key=$this->input->request_headers();
	$token=$secure_key['authorization'];
	$this->load->helper('jwt_helper');
	$str = ltrim($token, 'Bearer ');
	$token='e'.$str;
	$user =jwt::decode($token,$this->config->item('jwt_key'));	
	$prefix=$user->prefix;
	$this->load->database($prefix);

	$driver_id = $user->id;
	$result = $this->db->query("SELECT 
		id,
		date,
		manifest_id AS manifest_number,
		route_number AS route_name,
		vehicle_id
		FROM 
		sma_manifest
		WHERE 
		is_deleted = 'N' 
		AND intransit = 'Y' 
		AND is_manifest = 'Y' 
		AND driver_id = '$driver_id' 
		AND id NOT IN (SELECT manifest_id FROM sma_manifest_trip)
		AND delivered_status IN ('N', NULL)
		ORDER BY 
		id DESC;
		")->result();
	if(count($result)!='0')
	{
		foreach ($result as $results)
		{
			$manifest_route = $this->db->query("select route_number from sma_routes where id in($results->route_name)")->result();
			$route_name=array();
			foreach ($manifest_route as $r)
			{
				$route_name[]=$r->route_number;
			}
			$route_name=implode(",",$route_name);
			$results->route_name = $route_name;

		}
		$data = new stdClass();
		$data->result = $result;
		$response_arr = array(
			'success' => true,
			'message' => 'Manifest Details found',
			'manifest_list'=>$result,
		);
		echo json_encode($response_arr);

	}else
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch manifest List',
		);
		echo json_encode($response_arr);
	}
}

public function create_new_trip()
{

	$manifest_details_json = $this->input->post('manifest_details'); 
	$manifest_details = json_decode(stripslashes($manifest_details_json), true);
	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));

	$prefix = $user->prefix;
	$this->load->database($prefix);


	$user_id = $user->id;
	$trip_number = $this->site->getReference('trip');   
	$insert_data = array(); 

	$insert_trip = array(			
		'driver_id' => $user_id,
		'vehicle_id' => $this->input->post('vehicleId'),
		'trip_number' => $trip_number,
		'status' => 'Intransit',
		'createdOn'=>date('Y-m-d h:i:s')			
	);
	$this->db->insert('sma_trip', $insert_trip);
	$trip_id=$this->db->insert_id();


	foreach ($manifest_details as $manifest) 
	{
		$insert_data[] = array(
			'manifest_id' => $manifest['id'],
			'trip_id'=>$trip_id,			
			'manifest_no' => $manifest['manifest_number'],
		);
	}

	$this->site->updateReference('trip');

	$inserted_ids = array();
	foreach ($insert_data as $insert) {
		$this->db->insert('sma_manifest_trip', $insert);
		$inserted_ids[] = $this->db->insert_id();
	}


	if (!empty($inserted_ids)) 
	{
		$response_arr = array(
			'success' => true,
			'message' => 'New Trip created successfully',
			'inserted_ids' => $inserted_ids
		);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Failed to create new trip',
		);
	}
	echo json_encode($response_arr);
}

public function my_trip()
{

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));
	$prefix = $user->prefix;
	$this->load->database($prefix);
	$user_id = $user->id;
	$activeTripsOnly = $this->input->post('activeTripsOnly');
	
	if($activeTripsOnly==1)
	{

		$tripDetails = $this->db->query("select sma_trip.id as tripId,trip_number as tripNumber,createdOn,status as tripStatus,sma_users.first_name,sma_users.last_name from sma_trip left join sma_users on sma_users.id=sma_trip.driver_id where sma_trip.status='Intransit' and  driver_id='$user_id' order by sma_trip.id desc")->result();
	}else
	{
		$tripDetails = $this->db->query("select sma_trip.id as tripId,trip_number as tripNumber,createdOn,status as tripStatus,sma_users.first_name,sma_users.last_name from sma_trip left join sma_users on sma_users.id=sma_trip.driver_id where driver_id='$user_id' order by sma_trip.id desc")->result();

	}
	if (!empty($tripDetails)) 
	{
		$response_arr = array(
			'success' => true,
			'message' => 'Trip details found',
			'tripDetails' => $tripDetails
		);
	} else 
	{
		$response_arr = array(
			'success' => false,
			'message' => 'Trip details not found',
		);
	}
	echo json_encode($response_arr);
}

public function get_order_by_trip_id()
{

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);   
	$driver_id = $user->id;
	$trip_id = $this->input->post('tripId');

	if ($trip_id != '0') 
	{
		$result = array();       

		$trip = $this->db->query("SELECT manifest_id FROM sma_manifest_trip WHERE trip_id='$trip_id'")->result_array();

		

		$result = array(); // Initialize an empty array to store all sales details

		foreach ($trip as $row) {
			$manifest_id = $row['manifest_id'];

			$manifest = $this->db->query("SELECT print_id FROM sma_manifest WHERE id='$manifest_id'")->row_array();
			$print_id = $manifest['print_id'];

			$sales_details = $this->db->query("SELECT 
				sma_sales.id as id, 
				sma_sales.date, 
				sma_sales.reference_no, 
				sma_sales.invoice_date, 
				sma_sales.deliverydate, 
				sma_sales.customer, 
				sma_sales.customer_id, 
				sma_sales.driver_deliver, 
				sma_sales.driver_deliver as total_due, 
				sma_sales.payment_method, 
				sma_sales.sale_status, 
				sma_sales.grand_total, 
				sma_sales.paid, 
				sma_sales.grand_total - paid as balance, 
				sma_sales.payment_status, 
				sma_sales.cheque_status,
				sma_sales.manifest_id, 
				sma_companies.accound_no, 
				sma_companies.postal_code, 
				sma_companies.address, 
				sma_routes.route_number, 
				sma_routes.route_number as manifestDetails
				FROM 
				sma_sales 
				LEFT JOIN 
				sma_companies ON sma_companies.id = sma_sales.customer_id  
				LEFT JOIN 
				sma_routes ON sma_companies.route = sma_routes.id 
				WHERE 
				sma_sales.id IN ($print_id)  
				ORDER BY 
				sma_sales.id DESC")->result();


			foreach ($sales_details as $detail) {
				$manifest_id = $detail->manifest_id;
				$total_due = number_format(get_total($detail->customer_id), 2) - number_format(total_sale_person_collected($detail->customer_id), 2) - number_format(total_driver_collected($detail->customer_id), 2);
				$detail->total_due = number_format($total_due,2);
				$detail->manifestDetails = $this->db->query("SELECT id, date, manifest_id AS manifest_number, vehicle_id FROM sma_manifest WHERE manifest_id='$manifest_id'")->row_array();

        // Append the sales detail to the result array
				$result[] = $detail;
			}
		}

// Return the result array containing all sales details



		if (!empty($result)) {
			$response_arr = array(
				'success' => true,
				'message' => 'Delivery Details Found',
				'delivery_details' => $result,
			);
		} else {
			$response_arr = array(
				'success' => false,
				'message' => 'Unable to fetch order list',
			);
		}
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Invalid trip ID',
		);
	}

	echo json_encode($response_arr);
}

public function trip_summary()
{
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);   
	$driver_id = $user->id;

	$trip_id = $this->input->post('tripId');
	$trip_delivered_order = $this->db->query("select sum(sma_sales.grand_total) as grand_total FROM sma_sales INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sales.sale_status = 'Delivered' and sma_manifest_trip.trip_id = '$trip_id';
		")->result_array();

	$trip_undelivered_order = $this->db->query("select  sum(sma_sales.grand_total) as  grand_total 
		FROM sma_sales
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sales.sale_status = 'Undelivered' and sma_manifest_trip.trip_id = '$trip_id'		
		AND sma_sale_items.is_delete = '0'
		")->result_array();
	$partial_undeliv = $this->db->query("select SUM(sma_sale_items.not_delivered_qty * sma_sale_items.unit_price) AS grand_total 
		FROM sma_sales
		INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		WHERE sma_sale_items.not_delivered_qty  IS NOT NULL  and sma_manifest_trip.trip_id = '$trip_id'		
		AND sma_sale_items.is_delete = '0'
		")->result_array();


	$trip_delivered_order_product = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.order_type AS qtyType,  CAST(sma_sale_items.quantity AS UNSIGNED) AS qty FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sales.sale_status = 'Delivered' AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0' and sma_sale_items.quantity!='0'")->result_array();


	$trip_undelivered_product = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode,  CAST(sma_sale_items.quantity AS UNSIGNED) AS qty,sma_sale_items.order_type AS qtyType FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sales.sale_status = 'Undelivered' 
		and sma_sale_items.quantity!='0'  AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();
	$trip_partial_undeliv = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode,  CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS qty,sma_sale_items.order_type AS qtyType FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_sale_items.not_delivered_qty  IS NOT NULL  AND sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();
	$trip_undelivered_order_product = array_merge($trip_undelivered_product, $trip_partial_undeliv);


	$delivered_products_box = 0;
	$delivered_products_piece = 0;
	

	foreach ($trip_delivered_order_product as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$delivered_products_box += $product['qty'];
			
		} elseif ($product['qtyType'] == 'piece') 
		{
			
			$delivered_products_piece += $product['qty'];
		}
	}


	$undelivered_products_box = 0;
	$undelivered_products_piece = 0;
	$trip_box_balance = 0;
	$trip_piece_balance = 0;

	foreach ($trip_undelivered_order_product as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$undelivered_products_box += $product['qty'];
			$trip_box_balance += $product['qty'];
		} elseif ($product['qtyType'] == 'piece') 
		{
			$undelivered_products_piece += $product['qty'];
			$trip_piece_balance += $product['qty'];
		}
	}

	$trip_delivered_order_details = $this->db->query("select 
		sma_sales.id AS orderId,
		sma_companies.id AS customerId,
		sma_companies.name AS customerName,
		sma_companies.company,
		sma_sales.reference_no AS referenceNo,
		sma_companies.accound_no AS customerAccountNo
		FROM 
		sma_sales
		LEFT JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) > 0
		LEFT JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id
		LEFT JOIN 
		sma_companies ON sma_companies.id = sma_sales.customer_id
		WHERE 
		sma_sales.sale_status = 'Delivered' 
		AND FIND_IN_SET(sma_manifest_trip.trip_id, '$trip_id') > 0")->result_array();


	$trip_undelivered_order_details = $this->db->query("select 	sma_sales.id AS orderId,
		sma_companies.id AS customerId,
		sma_companies.name AS customerName,
		sma_companies.company,
		sma_sales.reference_no AS referenceNo,
		sma_companies.accound_no AS customerAccountNo FROM sma_sales LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id WHERE sma_sales.sale_status = 'Undelivered' AND EXISTS ( SELECT 1 FROM sma_manifest JOIN sma_manifest_trip ON FIND_IN_SET(sma_manifest.id, sma_manifest_trip.manifest_id) > 0 WHERE sma_manifest_trip.trip_id = '$trip_id' AND sma_manifest.print_id = sma_sales.id )")->result_array();



	

	$total_product_list = $this->db->query("select sma_sale_items.product_id AS productId, sma_sale_items.product_name AS productName, sma_sale_items.product_code AS productCode, sma_sale_items.order_type AS qtyType, CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS not_delivered_qty, CAST(sma_sale_items.quantity AS UNSIGNED) AS qty FROM sma_sales INNER JOIN sma_sale_items ON sma_sales.id = sma_sale_items.sale_id INNER JOIN sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) INNER JOIN sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id WHERE sma_manifest_trip.trip_id = '$trip_id' AND sma_sale_items.is_delete = '0'")->result_array();


	$total_products_box = 0;
	$total_products_piece = 0;

	foreach ($total_product_list as $product) 
	{

		if ($product['qtyType'] == 'box') 
		{
			$total_products_box += $product['qty'] + ($product['not_delivered_qty'] ?? 0);
		} elseif ($product['qtyType'] == 'piece') 
		{
			$total_products_piece += $product['qty'] + ($product['not_delivered_qty'] ?? 0);
		}
	}

	//Return start

	$return_product_list = $this->db->query("select sma_return_items.product_id as productId,sma_return_items.product_name as productName,product_code as productCode,return_type as qtyType,sma_return_items.return_quantity as qty FROM `sma_returns` left join sma_return_items on sma_return_items.return_id=sma_returns.id where trip_id='$trip_id'")->result_array();

	$return_products_box = 0;
	$return_products_piece = 0;

	foreach ($return_product_list as $row) 
	{

		if ($row['qtyType'] == 'box') 
		{
			$return_products_box += $row['qty'];
			$trip_box_balance += $row['qty'];

		} elseif ($row['qtyType'] == 'piece') 
		{
			$return_products_piece += $row['qty'];
			$trip_piece_balance += $row['qty'];
		}
	}

	//Return End



//payment collection start

	$driver_payment_collection['cash_collection_details'] = $this->db->query("select sma_companies.name as customerName,sma_companies.accound_no AS customerAccountNo,amount as cashCollected,payment_mode from sma_driver_collected_amount left join sma_companies on sma_companies.id=sma_driver_collected_amount.customer_id where trip_id='$trip_id'")->result_array();

	$payment_collection = [
		'cash_collection_details' => [],
		'cheque_collection_details' => []
	];


	foreach ($driver_payment_collection['cash_collection_details'] as $payment) {

		if ($payment['payment_mode'] == 'cash') {

			$payment_collection['cash_collection_details'][] = [
				'customerName' => $payment['customerName'],
				'customerAccountNo' => $payment['customerAccountNo'],
				'cashCollected' => $payment['cashCollected']
			];
		} elseif ($payment['payment_mode'] == 'cheque') {

			$payment_collection['cheque_collection_details'][] = [
				'customerName' => $payment['customerName'],
				'customerAccountNo' => $payment['customerAccountNo'],
				'chequeAmount' => $payment['cashCollected'] 
			];
		}
	}


	$total_balance_product = $this->db->query("
		SELECT 
		sma_sale_items.product_id AS productId,
		sma_sale_items.sale_id, 
		sma_sale_items.product_name AS productName, 
		sma_sale_items.product_code AS productCode, 
		sma_sale_items.order_type AS qtyType,  
		SUM(CAST(sma_sale_items.quantity AS UNSIGNED)) AS qty,
		CAST(order_qty AS UNSIGNED) AS order_qty,
		CAST(sma_sale_items.not_delivered_qty AS UNSIGNED) AS not_delivered_qty
		FROM 
		sma_sales 
		INNER JOIN 
		sma_sale_items ON sma_sales.id = sma_sale_items.sale_id 
		INNER JOIN 
		sma_manifest ON FIND_IN_SET(sma_sales.id, sma_manifest.print_id) 
		INNER JOIN 
		sma_manifest_trip ON sma_manifest.id = sma_manifest_trip.manifest_id 
		WHERE 
		sma_manifest_trip.trip_id = '$trip_id' 
		AND sma_sale_items.is_delete = '0' 
		GROUP BY 
		sma_sale_items.product_id, 
		sma_sale_items.order_type
		")->result_array();

// Loop through each product
	foreach ($total_balance_product as &$product) {
    // Initialize total quantities
		$TotalQty = $product['qty'] + ($product['not_delivered_qty'] ? $product['not_delivered_qty'] : 0);

    // Prepare variables for queries
		$productId = $product['productId'];
		$sale_id = $product['sale_id'];

    // Execute the inner query to get the delivered quantity
		$Delivered = $this->db->query("
			SELECT sma_sale_items.quantity as qty 
			FROM sma_sales 
			LEFT JOIN sma_sale_items ON sma_sale_items.sale_id = sma_sales.id 
			WHERE sma_sales.sale_status = 'Delivered' 
			AND sma_sale_items.sale_id = '$sale_id' 
			AND sma_sale_items.product_id = '$productId'
			")->row_array();

    // Calculate the delivered quantity
		$delivered_qty = isset($Delivered['qty']) ? $Delivered['qty'] : 0;
		$product['delivered_qty'] = $delivered_qty;

    // Update quantities and balance based on quantity type
		$product['qty'] = $TotalQty;
		$product['balance_qty'] = $TotalQty - $delivered_qty;
	}
	unset($product);
	

	$data['deliverySummary'] = array(
		'delivered_orders' => count($trip_delivered_order_details),
		'delivered_amt' => str_replace(',', '', number_format($trip_delivered_order[0]['grand_total'],2)),		
		'delivered_order_details' => $trip_delivered_order_details,
		'undelivered_orders' => count($trip_undelivered_order_details),
		'undelivered_amt' => str_replace(',', '', number_format((($trip_undelivered_order[0]['grand_total'] ?? 0) + ($partial_undeliv[0]['grand_total'] ?? 0)),2)),
		'undelivered_order_details'=>$trip_undelivered_order_details	

		
	);
	$data['delivered_products_list'] =$trip_delivered_order_product;
	$data['undelivered_products_list'] =$trip_undelivered_order_product;
	$data['return_products_list'] =$return_product_list;
	$data['return_products_piece'] =$return_products_piece;
	$data['return_products_box'] =$return_products_box;
	$data['payment_collection'] =$payment_collection;
	$data['total_products_box'] =$total_products_box;
	$data['total_products_piece'] =$total_products_piece;
	$data['delivered_products_box'] =$delivered_products_box;
	$data['delivered_products_piece'] =$delivered_products_piece;
	$data['undelivered_products_box'] =$undelivered_products_box;
	$data['undelivered_products_piece'] =$undelivered_products_piece;
	$data['total_products_list'] = $total_product_list;
	$data['trip_box_balance'] = $trip_box_balance;
	$data['trip_piece_balance'] = $trip_piece_balance;
	$data['total_balance_product']=$total_balance_product;

	

	if (!empty($data)) {
		$response_arr = array(
			'success' => true,
			'message' => 'Delivery Details Found',
			'data' => $data
			
		);
	} else {
		$response_arr = array(
			'success' => false,
			'message' => 'Unable to fetch order list',
		);
	}
	echo json_encode($response_arr);
}


public function get_dashboard_count_api()
{   
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);   
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
	}else{
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
		// array(
		// 	"title" => "Sage Push Invoice",
		// 	"value" => moneyFormat($sage_push_invoice['grand_total'] - $sage_push_invoice['tax_total']),
		// 	"details" => 'VAT '.moneyFormat(($sage_push_invoice['tax_total'] == '') ? 0 : $sage_push_invoice['tax_total'])
		// ),
		
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
			"value" =>  moneyFormat(($users[0]['grand_total'] - $users[0]['tax_total'])),
			"details" => ($users[0]['first_name'] == '') ? '' : $users[0]['first_name']
		),

		array(
			"title" => "Max Value Order",
			"value" =>  moneyFormat(($top_sales[0]['total_amount'] - $top_sales[0]['tax_total'])),
			"details" => ($top_sales[0]['name'] == '') ? '' : $top_sales[0]['name']
		)
	);

	$response_arr = array(
		"success" => true,
		"message" => "Data Found",
		"data" =>$data

	);

	echo json_encode($response_arr);
}


public function get_dashboard_count_api_version_2()
{   
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);   
	if(@$start_date=="" &&  @$end_date="")
	{
		$this->session->unset_userdata('start_date');
		$this->session->unset_userdata('end_date');
		$this->session->unset_userdata('push_invoice_start_date');
		$this->session->unset_userdata('push_invoice_end_date');
		$start_date = date('Y-m-d', strtotime('first day of this month')) . ' 00:00:00';
		$end_date   =  date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59';
		$startdate  = date('Y-m-d 00:00:00');
		$enddate    = date('Y-m-d 23:59:59');
	}else{
		// $reportrange = $_POST['reportrange'];
		// $arry = explode("-", $reportrange);
		// $start_date = $arry[0];
		// $end_date = $arry[1];
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
			"details" => 'VAT '.moneyFormat(($sales_order['item_tax'] == '') ? 0 : $sales_order['item_tax']),
			"section" => 'monthly_mis'
		),
		array(
			"title" => "Delivered Invoice",
			"value" => moneyFormat($delivered_invoice['grand_total'] - $delivered_invoice['tax_total']),
			"details" => 'VAT '.moneyFormat(($delivered_invoice['tax_total'] == '') ? 0 : $delivered_invoice['tax_total']),
			"section" => 'monthly_mis'
		),
		// array(
		// 	"title" => "Sage Push Invoice",
		// 	"value" => moneyFormat($sage_push_invoice['grand_total'] - $sage_push_invoice['tax_total']),
		// 	"details" => 'VAT '.moneyFormat(($sage_push_invoice['tax_total'] == '') ? 0 : $sage_push_invoice['tax_total'])
		// ),
		
		array(
			"title" => "Pending Sage Push Invoice",
			"value" => moneyFormat($pending_sage_push_invoice['grand_total'] - $pending_sage_push_invoice['tax_total']),
			"details" => 'VAT '.moneyFormat(($pending_sage_push_invoice['tax_total'] == '') ? 0 : $pending_sage_push_invoice['tax_total']),
			"section" => 'monthly_mis'
		),
		array(
			"title" => "New Sales",
			"value" => $new_sales['total_new_sale'],
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Order Accepted",
			"value" => $sale_accept['total_accept_sale'],
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Picklist Generated",
			"value" => count($picklistgenerated) . '(' . $picker_count['total_picker'] . ')',
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Picking Confirmed",
			"value" => count($picking_confirmed) . '(' . count($picking_confirmed_inv) . ')',
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Manifest Generated",
			"value" => $manifest_generated['total_manifest_generated'] . '(' . $manifest_order . ')',
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Delivery Confirmed",
			"value" => $delivery_confirmed['total_delivery_confirmed'],
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Total Sage Push",
			"value" => $total_push['total_inv'],
			"details" => "",
			"section" => 'daily_mis'
		),
		array(
			"title" => "Top Sales",
			"value" =>  moneyFormat(($users[0]['grand_total'] - $users[0]['tax_total'])),
			"details" => ($users[0]['first_name'] == '') ? '' : $users[0]['first_name'],
			"section" => 'daily_mis'
		),

		array(
			"title" => "Max Value Order",
			"value" =>  moneyFormat(($top_sales[0]['total_amount'] - $top_sales[0]['tax_total'])),
			"details" => ($top_sales[0]['name'] == '') ? '' : $top_sales[0]['name'],
			"section" => 'daily_mis'
		)
	);

	$response_arr = array(
		"success" => true,
		"message" => "Data Found",
		"data" =>$data

	);

	echo json_encode($response_arr);
}

public function get_po_requests(){

	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);
	$status = $this->input->post('status');
	if ($status=='all') {	

		$query = "select sma_purchases.id as poId,sma_purchases.reference_no as poNumber,sma_purchases.status,sma_purchases.date, sma_purchases.supplier_id as supplierId,
		sma_companies.company,SUM(CASE WHEN sma_purchase_items.type = '1' THEN sma_purchase_items.quantity ELSE 0 END) as orderedQtyBox,SUM(CASE WHEN sma_purchase_items.type = '2' THEN sma_purchase_items.quantity ELSE 0 END) as orderedQtyPiece FROM sma_purchases
		LEFT JOIN sma_companies ON sma_companies.id = sma_purchases.supplier_id
		LEFT JOIN sma_purchase_items ON sma_purchase_items.purchase_id = sma_purchases.id   
		GROUP BY sma_purchases.id, sma_purchases.reference_no, sma_purchases.supplier_id, sma_companies.company order by sma_purchases.id desc";
	}else{
		$query = "select sma_purchases.id as poId,sma_purchases.reference_no as poNumber,sma_purchases.supplier_id as supplierId,
		sma_companies.company,sma_purchases.status,sma_purchases.date, SUM(CASE WHEN sma_purchase_items.type = '1' THEN sma_purchase_items.quantity ELSE 0 END) as orderedQtyBox,SUM(CASE WHEN sma_purchase_items.type = '2' THEN sma_purchase_items.quantity ELSE 0 END) as orderedQtyPiece FROM sma_purchases
		LEFT JOIN sma_companies ON sma_companies.id = sma_purchases.supplier_id
		LEFT JOIN sma_purchase_items ON sma_purchase_items.purchase_id = sma_purchases.id
		WHERE sma_purchases.status='$status'
		GROUP BY sma_purchases.id, sma_purchases.reference_no, sma_purchases.supplier_id, sma_companies.company order by sma_purchases.id desc";
	}

	$purchases = $this->db->query($query)->result();

	if (count($purchases)!='0') {
		foreach ($purchases as &$purchase) {
			$purchase->orderedQtyBox = (int) $purchase->orderedQtyBox;
			$purchase->orderedQtyPiece = (int) $purchase->orderedQtyPiece;
		}
		$response_arr = array(
			"success" => true,
			"message" => "GRN requests found",
			"grnData" =>$purchases

		);
	}else{

		$response_arr = array(
			"success" => false,
			"message" => "No PO requests found",
			"grnData" =>$data

		);
	}
	echo json_encode($response_arr);

}
public function po_details_by_po_id(){
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);
	$poId = $this->input->post('poId');
	$po_details = $this->db->query("select id as itemId,product_id as productId,product_code as productCode,product_name as productName,quantity as orderedQty,type as qtyType  FROM sma_purchase_items where purchase_id ='$poId'")->result();
	//print_r($po_details);exit();
	foreach($po_details as $pd){
		$qtyType = $pd->qtyType;
		if($qtyType=='2'){
			$pd->qtyType = 'piece';
		}else{
			$pd->qtyType = 'box';
		}
	}
	if (count($po_details)!='0') {
		$response_arr = array(
			"success" => true,
			"message" => "GRN requests found",
			"poDetails" =>$po_details

		);
	}else{

		$response_arr = array(
			"success" => false,
			"message" => "No PO requests found",
			"data" =>$data

		);
	}
	echo json_encode($response_arr);


}

public function add_grn(){
	extract($_POST);

	$secure_key = $this->input->request_headers();
	$token = $secure_key['authorization'];  
	$str = ltrim($token, 'Bearer ');
	$token = 'e'.$str;   
	$this->load->helper('jwt_helper');
	$user = jwt::decode($token, $this->config->item('jwt_key'));      
	$prefix = $user->prefix;
	$this->load->database($prefix);
	$poId = $this->input->post('poId');
	$sales_product = json_decode(file_get_contents('php://input'), true);

	$data = $this->db->query('select id FROM sma_grn order by id desc')->result_array();
	$grn = $data[0]['id']+1;
	$reference = 'GRN00'.$grn;
	$po = $this->purchases_model->getPurchaseByID($poId);
	$date = date('Y-m-d H:i:s');

	$total_vat=0;
	$grand_total=0;

	$data  = array(
		'reference_no'     		      => $reference,
		'invoice_number'   			  => $invoice_number,
		'date'                        => $date,
		'supplier_id'                 => $po->supplier_id,
		'supplier'                    => $po->supplier,
		'warehouse_id'                => $po->warehouse_id,
		'note'                        => $po->note,
		'total'                       => $grand_total,
		'product_discount'            => $po->product_discount,
		'order_discount_id'           => $this->input->post('discount'),
		'order_discount'              => $po->order_discount,
		'total_discount'              => $po->total_discount,
		'product_tax'                 => $po->product_tax,
		'order_tax_id'                => $this->input->post('order_tax'),
		'order_tax'                   => $total_vat,
		'total_tax'                   => $total_vat,
		'shipping'                    => $po->shipping,
		'grand_total'                 => $grand_total,
		'status'                      => 'Draft',
		'created_by'                  => $this->session->userdata('user_id'),
		'payment_term'                => $po->payment_term,
		'due_date'                    => $po->due_date,
		'expected_delivery_date'      => $po->expected_delivery_date,
		'recurrence_type'             => $po->recurrence_type,
		'pachrches_id'          	  => $poId,
	);  

	$this->db->insert('sma_grn', $data);
	$insert_id = $this->db->insert_id();


	for ($i = 0; $i < count($code); $i++) 
	{
		$code1=$code[$i];
		$sma_product=$this->db->query("select id,quantity,tax_rate from sma_products where code='$code1' limit 1")->result_array();

		$product_id=$sma_product[0]['id'];
		$tax_rate_id=$sma_product[0]['tax_rate'];
		$product = array(
			'purchase_id'        =>$insert_id,
			'product_id'=>$product_id, 
			'product_code'      =>$code[$i],
			'product_name'      =>$name[$i],
			'option_id'         => 0,
			'net_unit_cost'     =>$_POST['cost'][$i],
			'unit_cost'         => $_POST['cost'][$i],
			'quantity'              => $_POST['order_qty'][$i],
			'product_unit_id'       => 0,
			'product_unit_code'     => 0,
			'unit_quantity'         => $_POST['order_qty'][$i],
			'quantity_balance'      => 0,
			'quantity_received'     => $_POST['order_qty'][$i],
			'warehouse_id'          =>1,
			'item_tax'              =>$_POST['vat'][$i],
			'tax_rate_id'           => $tax_rate_id,
			'tax'                   =>$_POST['vat'][$i],
			'discount'              =>0,
			'item_discount'         =>0,
			'subtotal'               =>$_POST['subtotal'][$i],
			'expiry'                 => date('Y-m-d') ,
			'real_unit_cost'         => $_POST['cost'][$i],
			'date'                   => date('Y-m-d', strtotime($date)),
			'status'                 => 'Draft',
			'supplier_part_no'       =>0,

		);
		$grand_total+=$this->sma->formatDecimal($_POST['subtotal'][$i]);
		$products[] = $product;   
		$this->db->insert('sma_grn_items', $product);

		$pro_qty=$sma_product[0]['quantity'] + $_POST['order_qty'][$i];
		$update_qty=array(
			"quantity"=> $pro_qty,
			"cost"=>$_POST['cost'][$i],
			"price"=>$_POST['sale_price'][$i],
		);


		$this->db->where('id',$product_id);
		$this->db->update('sma_products', $update_qty);

	}



	$update=array("status"=>'Close');
	$this->db->where('id',$pachrches_id);
	$this->db->update('sma_purchases', $update);

	$totalbalance = $this->db->query("select balance from sma_supplier_ledger where supplier_id='$po->supplier_id' order by id desc")->row_array();
	$balance = $totalbalance['balance'];
	if ($balance == '' || $balance == NULL) 
	{
		$balance = 0.00;
	}
	$bal = $balance + $grand_total;
	$ledger = [
		'supplier_id' => $po->supplier_id,
		'date' => $date,
		'amount' => $grand_total,
		'payment_type' => 'd',
		'balance' => $bal,
		'paid_by' => $paid_by,
		'particulars' => $insert_id,
		'reference_number' => '',
		'created_by' => $this->session->userdata('user_id'),
	];

	$ledger = $this->db->insert('sma_supplier_ledger', $ledger);
}


}
