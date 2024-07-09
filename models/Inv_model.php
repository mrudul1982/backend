<?php 
defined('BASEPATH') or exit('No direct script access allowed');

class Inv_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
      
    }

public function inv_update_product_stock($stage,$product_id,$item_id,$order_qty,$order_type)
{
	
	switch ($stage) 
	{
		case "Accept":
		return $this->update_accept_product_stock($stage,$product_id,$item_id,$order_qty,$order_type);
		break;
		case "Picking":
		$this->update_picking_product_stock($stage,$product_id,$item_id,$order_qty,$order_type);
		break;
		case "Short_qty":
		$this->update_short_qty($stage,$product_id,$item_id,$order_qty,$order_type);
		break;
		case "Undelivered":
		$this->update_Undelivered_product_stock($stage,$product_id,$item_id,$order_qty);
		break;
		case "Partial_undelivered":
		$this->update_Partial_undelivered_product_stock($stage,$product_id,$item_id,$order_qty,$order_type);
		break;
		case "Return":
		$this->update_return_product_stock($stage,$product_id,$item_id,$order_qty,$returnReason);
		break;
		default:
		echo "";
	}
}
function update_accept_product_stock($stage,$product_id,$item_id,$order_qty,$order_type)
	{


		$check_quantity = $this->sales_model->check_quantity($product_id);
		$sale_items = $this->db->query("select id,quantity,order_qty,order_type from sma_sale_items where id='$item_id'")->row_array();

		$quantity = $sale_items['quantity'];
		$order_type = $sale_items['order_type'];

		$quan = round($check_quantity->quantity);


		if ($order_type == "box")
		{
			$check_quantity = $this->sales_model->check_quantity($product_id);
			$quan = round($check_quantity->quantity);
			$quantity = ($quan - $order_qty);
			$data = ['quantity' => $quantity];
			$cid = $product_id;

			return $c = $this->update_quantity($cid, $data);

		} else 
		{

			$products1 = $this->db->query("select parent_id,pack,id from sma_products where id='$product_id'")->row_array();
			$parent_id = $products1['parent_id'];

			if($parent_id==0)
			{
				$parent_id = $products1['id'];
			}       


		$check_quantity = $this->sales_model->check_quantity($parent_id);//pro qty
		$quan = round($check_quantity->split_quantity);
		$quan1 = round($check_quantity->quantity);
		$pack = $products1['pack'];
		$productid = $parent_id;


		if ($order_qty <= $quan)
		{
			$quantity= $quan-$order_qty;
			$data = ['split_quantity' => $quantity];
		}else
		{

			$item_unit_quantity = round($order_qty);
			$result1 = $item_unit_quantity / $pack;
			$result3 = intval($result1);
			if ($result3 > 0) {
				$result3++; 
			}

			if ($result3 == 0) 
			{

				$result4 = max(0, intval($quan1 - 1));
				$quantity = ($pack - $item_unit_quantity) + $quan;
				$data = ['quantity' => $result4, 'split_quantity' => $quantity];
			} else 
			{

				$result4 = max(0, intval($quan1 - $result3));
				$total = ($pack * $result3) + $quan;
				$split_quantity = $total - $item_unit_quantity;
				if ($split_quantity > $pack) 
				{

					$boxes = floor($split_quantity / $pack);
					$split_quantity -= ($boxes * $pack);
					$result4 += $boxes;
				}

				$data = ['quantity' => $result4, 'split_quantity' => $split_quantity];
			}
		}

		$cid = $product_id;                   
		return $c = $this->update_quantity($cid, $data);
	}

}


function update_picking_product_stock($stage,$product_id,$item_id,$order_qty,$order_type)
{

	if($order_type!='box')
	{

		$products1 = $this->db->query("select parent_id from sma_products where id='$product_id'")->row_array();
		$p_id = $products1['parent_id'];

		if($p_id=='' || $p_id==0)
		{
			$p_id = $product_id; 				    				 
		}
		$products1 = $this->db->query("select split_quantity from sma_products where id='$product_id'")->row_array();
		$split_quantity = $products1['split_quantity'];
		$qty = $split_quantity + $order_qty; 
        $update_sales = array('split_quantity' => $qty); //need to discuss

    }else
    {
    	$products = $this->db->query("select quantity from sma_products where id='$product_id'")->row_array();
    	$quantity = $products['quantity'];
    	$qty = $quantity + $order_qty; 
    	$update_sales = array('quantity' => $qty); 
    }

    $this->db->where('id',$product_id);
    $this->db->update('sma_products',$update_sales);  


}
public function update_short_qty($stage,$product_id,$item_id,$order_qty,$order_type)
{

	$check_quantity = $this->sales_model->check_quantity($product_id);

	$sale_items = $this->db->query("select id,quantity,order_qty,order_type from sma_sale_items where id='$item_id'")->row_array();
	$order_qty=$sale_items['order_qty'];
	$quantity=$sale_items['quantity'];
	$order_type = $sale_items['order_type'];
	$quan = round($check_quantity->quantity);


	if ($quantity != $order_qty) 
	{
		if ($order_type == "box")
		{ 

			$item_unit_quantity = round($order_qty - $quantity);
			$check_quantity = $this->sales_model->check_quantity($product_id);
			$quan = round($check_quantity->quantity);
			$quantity = ($quan - $item_unit_quantity);

			$data = ['quantity' => $quantity];
			$cid = $productid;
			$c = $this->update_quantity($cid, $data);

		} else 
		{
			$products1 = $this->db->query("select parent_id,pack,id from sma_products where id='$product_id'")->row_array();
			$parent_id = $products1['parent_id'];
			if($parent_id==0)
			{
				$parent_id =$productid;
			}

			$check_quantity = $this->sales_model->check_quantity($parent_id);

			$quan = round($check_quantity->split_quantity);
			$quan1 = round($check_quantity->quantity);

			$item_unit_quantity = round($orderqty - $quantity);


			$pack = $products1['pack'];
			$productid=$parent_id;

			if ($item_unit_quantity <= $quan) 
			{  

				$quantity= $quan-$item_unit_quantity;
				$data = ['split_quantity' => $quantity];


			}else
			{
				$result1 = ($item_unit_quantity/$pack);
				$result3 = intval($result1);
				$result_2 = ($item_unit_quantity %$pack);
				$result_3 = intval($result1);

				if($result_3 >0)
				{

					$result3=$result3+1;
				}

				if ($result3 == '0')
				{

					$result4 = intval($quan1 - 1);    

					$quantity= ($pack-$item_unit_quantity)+$quan;

					$data = ['quantity' => $result4, 'split_quantity' => $quantity];

				}else
				{ 
					$result4 = intval($quan1 - $result3);
					$total = ($pack*$result3)+$quan;
					$split_quantity=$total-$item_unit_quantity;
					$data = ['quantity' => $result4, 'split_quantity' => $split_quantity];

				}
			}
			$cid = $productid;

			$c = $this->update_quantity($cid, $data);
		}                 
	}
}			
function update_Partial_undelivered_product_stock($stage,$product_id,$item_id,$order_qty,$order_type)
{


	if($order_type!='box') 
	{
		$products1 = $this->db->query("select split_quantity, parent_id from sma_products where id='$productid'")->row_array();
		$split_quantity = $products['split_quantity'];
		$qty = $split_quantity + $return_quantity; 
		$update_sales = array('split_quantity' => $qty); 
	}else
	{
		$products = $this->db->query("select quantity from sma_products where id='$product_id'")->row_array();
		$quantity = $products['quantity'];
		$qty = $quantity + $return_quantity; 
		$update_sales = array('quantity' => $qty); 
	}

	$this->db->where('id', $product_id);
	$this->db->update('sma_products', $update_sales);


}
function update_return_product_stock($stage,$product_id,$item_id,$order_qty,$returnReason)
{
	if ($row['returnReason']=='Unwanted')
	{        
		if($return_type!='box') 
		{
			$products1 = $this->db->query("select split_quantity, parent_id from sma_products where id='$productid'")->row_array();
			$split_quantity = $products['split_quantity'];
			$qty = $split_quantity + $return_quantity; 
			$update_sales = array('split_quantity' => $qty); 
		}else
		{
			$products = $this->db->query("select quantity from sma_products where id='$product_id'")->row_array();
			$quantity = $products['quantity'];

			$qty = $quantity + $return_quantity; 
			$update_sales = array('quantity' => $qty); 
		}

		$this->db->where('id', $product_id);
		$this->db->update('sma_products', $update_sales);

	}




}
public function update_quantity($id, $data)
{
	$this->db->where('id', $id);
	$this->db->update('sma_products', $data);
	return 1;

}
}
?>