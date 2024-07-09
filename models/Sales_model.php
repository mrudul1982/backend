<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sales_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop_model');
    }

    public function addDelivery($data = [])
    {
        if ($this->db->insert('deliveries', $data)) {
            if ($this->site->getReference('do') == $data['do_reference_no']) {
                $this->site->updateReference('do');
            }
            return true;
        }
        return false;
    }

    /* ----------------- Gift Cards --------------------- */

    public function addGiftCard($data = [], $ca_data = [], $sa_data = [])
    {
        if ($this->db->insert('gift_cards', $data)) {
            if (!empty($ca_data)) {
                $this->db->update('companies', ['award_points' => $ca_data['points']], ['id' => $ca_data['customer']]);
            } elseif (!empty($sa_data)) {
                $this->db->update('users', ['award_points' => $sa_data['points']], ['id' => $sa_data['user']]);
            }
            return true;
        }
        return false;
    }

    public function addOptionQuantity($option_id, $quantity)
    {
        if ($option = $this->getProductOptionByID($option_id)) {
            $nq = $option->quantity + $quantity;
            if ($this->db->update('product_variants', ['quantity' => $nq], ['id' => $option_id])) {
                return true;
            }
        }
        return false;
    }

    public function addPayment($data = [], $customer_id = null)
    {
        if ($this->db->insert('payments', $data)) {
            if ($this->site->getReference('pay') == $data['reference_no']) {
                $this->site->updateReference('pay');
            }

            $this->site->syncSalePayments1($data['sale_id'],$data['cheque_status'],$data['amount']);
            if ($data['paid_by'] == 'gift_card') {
                $gc = $this->site->getGiftCardByNO($data['cc_no']);
                $this->db->update('gift_cards', ['balance' => ($gc->balance - $data['amount'])], ['card_no' => $data['cc_no']]);
            } elseif ($customer_id && $data['paid_by'] == 'deposit') {
                $customer = $this->site->getCompanyByID($customer_id);
                $this->db->update('companies', ['deposit_amount' => ($customer->deposit_amount - $data['amount'])], ['id' => $customer_id]);
            }
            return true;
        }
        return false;
    }

    public function addSale_temp($data = [], $items = [], $payment = [], $si_return = [], $collect_amount)
    {
        if (empty($si_return)) {
            $cost = $this->site->costing($items);
            // $this->sma->print_arrays($cost);
        }

        $this->db->trans_start();
        if ($this->db->insert('sma_sales', $data)) {
            $sale_id = $this->db->insert_id();
            if ($this->site->getReference('so') == $data['reference_no']) {
                $this->site->updateReference('so');
            }
            
            if($collect_amount!='')
            {
              $sales_collect_amount = array(
                 'sales_id' => @$sale_id,
                 'customer_id' => @$data['customer_id'],
                 'amount'=> @$collect_amount,
                 'sales_person_id' =>  @$data['created_by'],
                 'payment_mode'=>@$data['payment_method'],
                 'cheque_no' => @$data['cheque_number'],
                 'cheque_date' => @$data['cheque_date']
             );

              $this->db->insert('sma_driver_collected_amount', $sales_collect_amount);
          }
          foreach ($items as $item) {
            $item['sale_id'] = $sale_id;
            $this->db->insert('sale_items', $item);
            $sale_item_id = $this->db->insert_id();
            if ($data['sale_status'] == 'Waiting to be Invoiced' && empty($si_return)) {
                $item_costs = $this->site->item_costing($item);
                foreach ($item_costs as $item_cost) {
                    if (isset($item_cost['date']) || isset($item_cost['pi_overselling'])) {
                        $item_cost['sale_item_id'] = $sale_item_id;
                        $item_cost['sale_id']      = $sale_id;
                        $item_cost['date']         = date('Y-m-d', strtotime($data['date']));
                        if (!isset($item_cost['pi_overselling'])) {
                            $this->db->insert('sma_costing', $item_cost);
                        }
                    } else {
                        foreach ($item_cost as $ic) {
                            $ic['sale_item_id'] = $sale_item_id;
                            $ic['sale_id']      = $sale_id;
                            $ic['date']         = date('Y-m-d', strtotime($data['date']));
                            if (!isset($ic['pi_overselling'])) {
                                $this->db->insert('sma_costing', $ic);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment['subject_id']   = $sale_id;
                $attachment['subject_type'] = 'sale';
                $this->db->insert('sma_attachments', $attachment);
            }
        }

        if ($data['sale_status'] == 'Invoiced') {
            $this->site->syncPurchaseItems($cost);
        }

        if (!empty($si_return)) {
            foreach ($si_return as $return_item) {
                $product = $this->site->getProductByID($return_item['product_id']);
                if ($product->type == 'combo') {
                    $combo_items = $this->site->getProductComboItems($return_item['product_id'], $return_item['warehouse_id']);
                    foreach ($combo_items as $combo_item) {
                        $this->updateCostingAndPurchaseItem($return_item, $combo_item->id, ($return_item['quantity'] * $combo_item->qty));
                    }
                } elseif ($product->type != 'service') {
                    $this->updateCostingAndPurchaseItem($return_item, $return_item['product_id'], $return_item['quantity']);
                }
            }
            $this->db->update('sales', ['return_sale_ref' => $data['return_sale_ref'], 'surcharge' => $data['surcharge'], 'return_sale_total' => $data['grand_total'], 'return_id' => $sale_id], ['id' => $data['sale_id']]);
        }

        if ($data['payment_status'] == 'partial' || $data['payment_status'] == 'paid' && !empty($payment)) {
            if (empty($payment['reference_no'])) {
                $payment['reference_no'] = $this->site->getReference('pay');
            }
            $payment['sale_id'] = $sale_id;
            if ($payment['paid_by'] == 'gift_card') {
                $this->db->update('gift_cards', ['balance' => $payment['gc_balance']], ['card_no' => $payment['cc_no']]);
                unset($payment['gc_balance']);
                $this->db->insert('payments', $payment);
            } else {
                if ($payment['paid_by'] == 'deposit') {
                    $customer = $this->site->getCompanyByID($data['customer_id']);
                    $this->db->update('sma_companies', ['deposit_amount' => ($customer->deposit_amount - $payment['amount'])], ['id' => $customer->id]);
                }
                $this->db->insert('payments', $payment);
            }
            if ($this->site->getReference('pay') == $payment['reference_no']) {
                $this->site->updateReference('pay');
            }
            $this->site->syncSalePayments($sale_id);
        }

        $this->site->syncQuantity($sale_id);
        $this->sma->update_award_points($data['grand_total'], $data['customer_id'], $data['created_by']);
    }
    $customer_id = $data['created_by'];
    $this->db->query("delete  from sma_cart_details where user_id='$customer_id'");
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        log_message('error', 'An errors has been occurred while adding the sale (Add:Sales_model.php)');
    } else {
        return $sale_id;
    }

    return false;
}
public function addSale($data, $products, $payment, $additional_data, $collect_amount)
{
    // Insert sale data
    $this->db->insert('sma_sales', $data);
    $sale_id = $this->db->insert_id();

    // Update reference number if necessary
    if ($this->site->getReference('so') == $data['reference_no']) {
        $this->site->updateReference('so');
    }

    // Insert sale details
    foreach ($products as $product) {
        $product['sale_id'] = $sale_id; // Assign the sale_id to each product
        $this->db->insert('sma_sale_items', $product); // Insert the product into the database
    }

    // Insert payment data if available
    // if (!empty($payment)) {
    //     // Insert payment data into the payment table
    //     // Assuming $payment is an array containing payment information
    //     $this->db->insert('payment_table', $payment);
    // }

    // Update collect amount if available
    if (!empty($collect_amount)) {
        $sales_collect_amount = array(
            'sales_id' => $sale_id,
            'customer_id' => $data['customer_id'],
            'amount' => $collect_amount,
            'sales_person_id' => $data['created_by'],
            'payment_mode' => $data['payment_method'],
            'cheque_no' => $data['cheque_number'],
            'cheque_date' => $data['cheque_date']
        );
        $this->db->insert('sma_driver_collected_amount', $sales_collect_amount);
    }

    // You can return any data if needed
    return $sale_id; // For example, returning the ID of the inserted sale
}


    public function addSale1($data = [], $items = [], $payment = [], $si_return = [], $attachments = [],$collect_amount)
    {
        if (empty($si_return)) {
            $cost = $this->site->costing($items);
            // $this->sma->print_arrays($cost);
        }

        $this->db->trans_start();
        if ($this->db->insert('sales', $data)) {
            $sale_id = $this->db->insert_id();
            if ($this->site->getReference('so') == $data['reference_no']) {
                $this->site->updateReference('so');
            }
            
            if($collect_amount!=''){
              $sales_collect_amount = array(
                 'sales_id' => $sale_id,
                 'customer_id' => $data['customer_id'],
                 'amount'=> $collect_amount,
                 'sales_person_id' =>  $data['created_by'],
                 'payment_mode'=>'cod',
             );

              $this->db->insert('sma_driver_collected_amount', $sales_collect_amount);
          }
          foreach ($items as $item) {
            $item['sale_id'] = $sale_id;
            $this->db->insert('sale_items', $item);
            $sale_item_id = $this->db->insert_id();
            if ($data['sale_status'] == 'Waiting to be Invoiced' && empty($si_return)) {
                $item_costs = $this->site->item_costing($item);
                foreach ($item_costs as $item_cost) {
                    if (isset($item_cost['date']) || isset($item_cost['pi_overselling'])) {
                        $item_cost['sale_item_id'] = $sale_item_id;
                        $item_cost['sale_id']      = $sale_id;
                        $item_cost['date']         = date('Y-m-d', strtotime($data['date']));
                        if (!isset($item_cost['pi_overselling'])) {
                            $this->db->insert('costing', $item_cost);
                        }
                    } else {
                        foreach ($item_cost as $ic) {
                            $ic['sale_item_id'] = $sale_item_id;
                            $ic['sale_id']      = $sale_id;
                            $ic['date']         = date('Y-m-d', strtotime($data['date']));
                            if (!isset($ic['pi_overselling'])) {
                                $this->db->insert('costing', $ic);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment['subject_id']   = $sale_id;
                $attachment['subject_type'] = 'sale';
                $this->db->insert('attachments', $attachment);
            }
        }

        if ($data['sale_status'] == 'Invoiced') {
            $this->site->syncPurchaseItems($cost);
        }

        if (!empty($si_return)) {
            foreach ($si_return as $return_item) {
                $product = $this->site->getProductByID($return_item['product_id']);
                if ($product->type == 'combo') {
                    $combo_items = $this->site->getProductComboItems($return_item['product_id'], $return_item['warehouse_id']);
                    foreach ($combo_items as $combo_item) {
                        $this->updateCostingAndPurchaseItem($return_item, $combo_item->id, ($return_item['quantity'] * $combo_item->qty));
                    }
                } elseif ($product->type != 'service') {
                    $this->updateCostingAndPurchaseItem($return_item, $return_item['product_id'], $return_item['quantity']);
                }
            }
            $this->db->update('sales', ['return_sale_ref' => $data['return_sale_ref'], 'surcharge' => $data['surcharge'], 'return_sale_total' => $data['grand_total'], 'return_id' => $sale_id], ['id' => $data['sale_id']]);
        }

        if ($data['payment_status'] == 'partial' || $data['payment_status'] == 'paid' && !empty($payment)) {
            if (empty($payment['reference_no'])) {
                $payment['reference_no'] = $this->site->getReference('pay');
            }
            $payment['sale_id'] = $sale_id;
            if ($payment['paid_by'] == 'gift_card') {
                $this->db->update('gift_cards', ['balance' => $payment['gc_balance']], ['card_no' => $payment['cc_no']]);
                unset($payment['gc_balance']);
                $this->db->insert('payments', $payment);
            } else {
                if ($payment['paid_by'] == 'deposit') {
                    $customer = $this->site->getCompanyByID($data['customer_id']);
                    $this->db->update('companies', ['deposit_amount' => ($customer->deposit_amount - $payment['amount'])], ['id' => $customer->id]);
                }
                $this->db->insert('payments', $payment);
            }
            if ($this->site->getReference('pay') == $payment['reference_no']) {
                $this->site->updateReference('pay');
            }
            $this->site->syncSalePayments($sale_id);
        }

        $this->site->syncQuantity($sale_id);
        $this->sma->update_award_points($data['grand_total'], $data['customer_id'], $data['created_by']);
    }
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        log_message('error', 'An errors has been occurred while adding the sale (Add:Sales_model.php)');
    } else {
        return $sale_id;
    }

    return false;
}





public function deleteDelivery($id)
{
    $this->site->log('Delivery', ['model' => $this->getDeliveryByID($id)]);
    if ($this->db->delete('deliveries', ['id' => $id])) {
        return true;
    }
    return false;
}
public function delete_sale($id){
    if ($this->db->delete('sale_items', ['id ' => $id])) {
        return true;
    }
    return false;
}

public function deleteGiftCard($id)
{
    $this->site->log('Gift card', ['model' => $this->site->getGiftCardByID($id)]);
    if ($this->db->delete('gift_cards', ['id' => $id])) {
        return true;
    }
    return false;
}

public function deletePayment($id)
{
    $opay = $this->getPaymentByID($id);
    $this->site->log('Payment', ['model' => $opay]);
    if ($this->db->delete('payments', ['id' => $id])) {
        $this->site->syncSalePayments($opay->sale_id);
        if ($opay->paid_by == 'gift_card') {
            $gc = $this->site->getGiftCardByNO($opay->cc_no);
            $this->db->update('gift_cards', ['balance' => ($gc->balance + $opay->amount)], ['card_no' => $opay->cc_no]);
        } elseif ($opay->paid_by == 'deposit') {
            $sale     = $this->getInvoiceByID($opay->sale_id);
            $customer = $this->site->getCompanyByID($sale->customer_id);
            $this->db->update('companies', ['deposit_amount' => ($customer->deposit_amount + $opay->amount)], ['id' => $customer->id]);
        }
        return true;
    }
    return false;
}

public function deleteSale($id)
{
    $this->db->trans_start();
    $sale_items = $this->resetSaleActions($id);
    $this->site->log('Sale', ['model' => $this->getInvoiceByID($id), 'items' => $sale_items]);
    if ($this->db->delete('sale_items', ['sale_id' => $id]) && $this->db->delete('sales', ['id' => $id]) && $this->db->delete('costing', ['sale_id' => $id])) {
        $this->db->delete('sales', ['sale_id' => $id]);
        $this->db->delete('payments', ['sale_id' => $id]);
        $this->site->syncQuantity(null, null, $sale_items);
    }
    $this->db->delete('attachments', ['subject_id' => $id, 'subject_type' => 'sale']);
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        log_message('error', 'An errors has been occurred while adding the sale (Delete:Sales_model.php)');
    } else {
        return true;
    }
    return false;
}

public function getAllGCTopups($card_id)
{
    $this->db->select("{$this->db->dbprefix('gift_card_topups')}.*, {$this->db->dbprefix('users')}.first_name, {$this->db->dbprefix('users')}.last_name, {$this->db->dbprefix('users')}.email")
    ->join('users', 'users.id=gift_card_topups.created_by', 'left')
    ->order_by('id', 'desc')->limit(10);
    $q = $this->db->get_where('gift_card_topups', ['card_id' => $card_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getAllInvoiceItems_order_history($sale_id, $return_id = null) 
{
    $d=$this->db->select('id,sale_id,product_id,quantity')->where('sale_id', $sale_id)->get('sale_items');
    if ($d->num_rows() > 0) {
        foreach (($d->result()) as $row) 
        {

            $get_promo = $this->db->query("select sma_sale_items.id,sma_promos.type,sma_promos.id as promo_id  from sma_sale_items left join sma_promos on sma_promos.id=sma_sale_items.promo_id  where sale_id='$sale_id' and product_id='$row->product_id' ")->row_array();
            $ptype=$get_promo['type']; 
            

            $pid=$row->product_id;

            if($ptype=='combo')
            {
                $getPromost = $this->shop_model->getPromosByProduct1($row->product_id); 
                $promo_id= $getPromost[0]->pid;
                $sale_items = $this->db->query("select sum(sma_sale_items.quantity) as qty  from sma_sale_items  where sale_id='$sale_id' and sma_sale_items.is_promoted='0' and sma_sale_items.is_delete='0' and product_id in(select productbuy_id from sma_productbuy where sma_productbuy.promos_id='$promo_id')")->result_array();
                $sqty=$sale_items[0]['qty'];
                $qty=$sqty;
                
            }else
            {
                $getPromost = $this->getPromostion($row->product_id);
            }



            if(count($getPromost)!=0)
            {
                if($ptype=='combo')
                {
                    $qty=$sqty;

                }else
                {
                    $qty= $row->quantity;
                }



                $pro_qty = $getPromost[0]->quantity;
                $pro_id =  $getPromost[0]->product2get;
                $get_quantity = $getPromost[0]->getquantity;

                if($qty >= $pro_qty)
                {        
                    $buy = $pro_qty; 
                    $get = $get_quantity;   
                    $q = $get*(intval($qty/$buy));


                    if($q!='0'){
                        $sale_items = $this->db->query("select id from sma_sale_items where sale_id='$sale_id' and product_id='$pro_id' and is_promoted='1'")->row_array();
                        if ($sale_items !='') 
                        {
                            $update2 = array(
                                'quantity' => $q,'unit_quantity' => $q,'is_delete' => 0,                
                            ); 
                            

                            $id = $sale_items['id'];

                            $this->db->where('id', $id);
                            $this->db->update('sma_sale_items', $update2);

                        }else
                        {

                            $products = $this->db->query("select tax_rate,split_price,price,code,name,unit from sma_products where id='$pro_id'")->row_array();     
                            

                            $insert = array(
                                'sale_id' => $sale_id,
                                'product_id' => $pro_id,
                                'product_code' => $products['code'],
                                'product_name' => $products['name'],
                                'product_type' => 'standard',
                                'net_unit_price' => 0,
                                'unit_price' => 0,
                                'quantity' => $q,
                                'warehouse_id' => '1',
                                'subtotal' => 0,
                                'real_unit_price' => 0,
                                'product_unit_id' => $products['unit'],
                                'product_unit_code' => '',
                                'unit_quantity' => $q,
                                'order_type' =>'box',
                                'item_tax'=>0,
                                'tax_rate_id'=>0,
                                'is_promoted'=>1,
                            ); 
                            

                            $this->db->insert('sma_sale_items', $insert);

                        }
                    }
                }
                else
                {
                  $sale_items = $this->db->query("select id from sma_sale_items where sale_id='$sale_id' and product_id='$pro_id' and is_promoted='1'")->row_array();
                  if ($sale_items !='') {
                    $update2 = array(
                        'quantity' => 0,
                        'unit_quantity' => 0,                 
                    );           
                    $id = $sale_items['id'];
                    $this->db->where('id', $id);
                    $this->db->update('sma_sale_items', $update2); 
                }
            }
        }
    }
    
}

$this->db->select('sale_items.*, tax_rates.code as tax_code, tax_rates.name as tax_name, tax_rates.rate as tax_rate, products.image,products.size, products.details as details, product_variants.name as variant, products.hsn_code as hsn_code, products.second_name as second_name,products.quantity as stock, products.unit as base_unit_id, units.code as base_unit_code,products.split_quantity,products.split,products.inner_ean_number,products.outer_ean_number')
->join('products', 'products.id=sale_items.product_id', 'left')
->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
->join('tax_rates', 'tax_rates.id=sale_items.tax_rate_id', 'left')
->join('units', 'units.id=products.unit', 'left')

->group_by('sale_items.id')
->order_by('id', 'asc');
if ($sale_id && !$return_id) {
    $this->db->where('sale_id', $sale_id);
} elseif ($return_id) {
    $this->db->where('sale_id', $return_id);
}
$this->db->where('is_delete', 0);
  //  $this->db->where('sale_items.quantity !=',0.0000);
$q = $this->db->get('sale_items');

if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $row->image= base_url('/assets/uploads/').$row->image;
        $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
        $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
        $row->outer_ean_number= chop($outer_ean_number);
        $data[] = $row;
    }
    return $data;
}
return false;
}


public function getAllInvoiceItems($sale_id,$prefix = null, $return_id = null) 
{
    // Update promotion quantities
    $sale_items = $this->db->select('id, product_id, quantity')->where('sale_id', $sale_id)->get('sma_sale_items');
    if ($sale_items->num_rows() > 0) {
        foreach ($sale_items->result() as $row) {
            $get_promo = $this->db->query("SELECT sma_sale_items.id, sma_promos.type, sma_promos.id AS promo_id FROM sma_sale_items LEFT JOIN sma_promos ON sma_promos.id = sma_sale_items.promo_id WHERE sale_id = '$sale_id' AND product_id = '$row->product_id'")->row_array();
            $ptype = $get_promo['type']; 

            $pid = $row->product_id;

            if ($ptype == 'combo') {
                $getPromost = $this->shop_model->getPromosByProduct1($row->product_id); 
                $promo_id = $getPromost[0]->pid;
                $sale_items = $this->db->query("SELECT SUM(sma_sale_items.quantity) AS qty FROM sma_sale_items WHERE sale_id = '$sale_id' AND sma_sale_items.is_promoted = '0' AND sma_sale_items.is_delete = '0' AND product_id IN (SELECT productbuy_id FROM sma_productbuy WHERE sma_productbuy.promos_id = '$promo_id')")->result_array();
                $sqty = $sale_items[0]['qty'];
                $qty = $sqty;
            } else {
                $getPromost = $this->getPromostion($row->product_id);
            }

            if (count($getPromost) != 0) {
                if ($ptype == 'combo') {
                    $qty = $sqty;
                } else {
                    $qty = $row->quantity;
                }

                $pro_qty = $getPromost[0]->quantity;
                $pro_id = $getPromost[0]->product2get;
                $get_quantity = $getPromost[0]->getquantity;

                if ($qty >= $pro_qty) {        
                    $buy = $pro_qty; 
                    $get = $get_quantity;   
                    $q = $get * (intval($qty / $buy));

                    if ($q != '0') {
                        $sale_items = $this->db->query("SELECT id FROM sma_sale_items WHERE sale_id = '$sale_id' AND product_id = '$pro_id' AND is_promoted = '1'")->row_array();
                        if ($sale_items != '') {
                            $update2 = array(
                                'quantity' => $q,
                                'unit_quantity' => $q,
                                'is_delete' => 0
                            ); 
                            $id = $sale_items['id'];
                            $this->db->where('id', $id);
                            $this->db->update('sma_sale_items', $update2);
                        } else {
                            $products = $this->db->query("SELECT tax_rate, split_price, price, code, name, unit FROM sma_products WHERE id = '$pro_id'")->row_array();     
                            $insert = array(
                                'sale_id' => $sale_id,
                                'product_id' => $pro_id,
                                'product_code' => $products['code'],
                                'product_name' => $products['name'],
                                'product_type' => 'standard',
                                'net_unit_price' => 0,
                                'unit_price' => 0,
                                'quantity' => $q,
                                'warehouse_id' => '1',
                                'subtotal' => 0,
                                'real_unit_price' => 0,
                                'product_unit_id' => $products['unit'],
                                'product_unit_code' => '',
                                'unit_quantity' => $q,
                                'order_type' => 'box',
                                'item_tax' => 0,
                                'tax_rate_id' => 0,
                                'is_promoted' => 1
                            ); 
                            $this->db->insert('sma_sale_items', $insert);
                        }
                    }
                } else {
                    $sale_items = $this->db->query("SELECT id FROM sma_sale_items WHERE sale_id = '$sale_id' AND product_id = '$pro_id' AND is_promoted = '1'")->row_array();
                    if ($sale_items != '') {
                        $update2 = array(
                            'quantity' => 0,
                            'unit_quantity' => 0
                        );           
                        $id = $sale_items['id'];
                        $this->db->where('id', $id);
                        $this->db->update('sma_sale_items', $update2); 
                    }
                }
            }
        }
    }

    // Fetch invoice items
    $this->db->select('sma_sale_items.*,sma_tax_rates.code as tax_code,sma_tax_rates.name as tax_name,sma_tax_rates.rate as tax_rate,sma_products.image,sma_products.size,sma_products.details as details,sma_product_variants.name as variant,sma_products.hsn_code as hsn_code,sma_products.second_name as second_name,sma_products.quantity as stock,sma_products.unit as base_unit_id,sma_units.code as base_unit_code,sma_products.split_quantity,sma_products.split,sma_products.inner_ean_number,sma_products.outer_ean_number')
        ->from('sma_sale_items')
        ->join('sma_products', 'sma_products.id = sma_sale_items.product_id', 'left')
        ->join('sma_product_variants', 'sma_product_variants.id = sma_sale_items.option_id', 'left')
        ->join('sma_tax_rates', 'sma_tax_rates.id = sma_sale_items.tax_rate_id', 'left')
        ->join('sma_units', 'sma_units.id = sma_products.unit', 'left')
        ->group_by('sma_sale_items.id')
        ->order_by('sma_sale_items.id', 'asc');

    if ($sale_id && !$return_id) {
        $this->db->where('sale_id', $sale_id);
    } elseif ($return_id) {
        $this->db->where('sale_id', $return_id);
    }
    $this->db->where('is_delete', 0);
    $this->db->where('is_return', 0);
    $this->db->where('sma_sale_items.quantity !=', 0.0000);
    $q = $this->db->get();


    if ($q->num_rows() > 0) {
        $data = array();
        foreach ($q->result() as $row) {
             $config_key = $prefix . '_url';
             $url = $this->config->item($config_key);
            $row->image = $url . '/assets/uploads/' . $row->image;

            // $row->image = base_url('/assets/uploads/') . $row->image;
            $row->inner_ean_number = str_replace(' ', '', $row->inner_ean_number);
            $outer_ean_number = str_replace(' ', '', $row->outer_ean_number);
            $row->outer_ean_number = chop($outer_ean_number);
            $data[] = $row;
        }
        return $data;
    }
    return false;
}




public function getAllInvoiceItems_no_return($sale_id, $return_id = null) 
{
    $d=$this->db->select('id,sale_id,product_id,quantity')->where('sale_id', $sale_id)->get('sale_items');
    if ($d->num_rows() > 0) {
        foreach (($d->result()) as $row) 
        {

            $get_promo = $this->db->query("select sma_sale_items.id,sma_promos.type,sma_promos.id as promo_id  from sma_sale_items left join sma_promos on sma_promos.id=sma_sale_items.promo_id  where sale_id='$sale_id' and product_id='$row->product_id' ")->row_array();
            $ptype=$get_promo['type']; 


            $pid=$row->product_id;

            if($ptype=='combo')
            {
                $getPromost = $this->shop_model->getPromosByProduct1($row->product_id); 
                $promo_id= $getPromost[0]->pid;
                $sale_items = $this->db->query("select sum(sma_sale_items.quantity) as qty  from sma_sale_items  where sale_id='$sale_id' and sma_sale_items.is_promoted='0' and sma_sale_items.is_delete='0' and product_id in(select productbuy_id from sma_productbuy where sma_productbuy.promos_id='$promo_id')")->result_array();
                $sqty=$sale_items[0]['qty'];
                $qty=$sqty;

            }else
            {
                $getPromost = $this->getPromostion($row->product_id);
            }

        	//	echo $row->product_id;


            if(count($getPromost)!=0)
            {
                if($ptype=='combo')
                {
                    $qty=$sqty;

                }else
                {
                    $qty= $row->quantity;
                }



                $pro_qty = $getPromost[0]->quantity;
                $pro_id =  $getPromost[0]->product2get;
                $get_quantity = $getPromost[0]->getquantity;

                if($qty >= $pro_qty)
                {        
                    $buy = $pro_qty; 
                    $get = $get_quantity;   
                    $q = $get*(intval($qty/$buy));


                    if($q!='0'){
                        $sale_items = $this->db->query("select id from sma_sale_items where sale_id='$sale_id' and product_id='$pro_id' and is_promoted='1'")->row_array();
                        if ($sale_items !='') 
                        {
                            $update2 = array(
                                'quantity' => $q,'unit_quantity' => $q,'is_delete' => 0,                
                            ); 

                            
                            $id = $sale_items['id'];
                            
                            $this->db->where('id', $id);
                            $this->db->update('sma_sale_items', $update2);

                        }else
                        {

                            $products = $this->db->query("select tax_rate,split_price,price,code,name,unit from sma_products where id='$pro_id'")->row_array();     


                            $insert = array(
                                'sale_id' => $sale_id,
                                'product_id' => $pro_id,
                                'product_code' => $products['code'],
                                'product_name' => $products['name'],
                                'product_type' => 'standard',
                                'net_unit_price' => 0,
                                'unit_price' => 0,
                                'quantity' => $q,
                                'warehouse_id' => '1',
                                'subtotal' => 0,
                                'real_unit_price' => 0,
                                'product_unit_id' => $products['unit'],
                                'product_unit_code' => '',
                                'unit_quantity' => $q,
                                'order_type' =>'box',
                                'item_tax'=>0,
                                'tax_rate_id'=>0,
                                'is_promoted'=>1,
                            ); 


                            $this->db->insert('sma_sale_items', $insert);

                        }
                    }
                }
                else
                {
                  $sale_items = $this->db->query("select id from sma_sale_items where sale_id='$sale_id' and product_id='$pro_id' and is_promoted='1'")->row_array();
                  if ($sale_items !='') {
                    $update2 = array(
                        'quantity' => 0,
                        'unit_quantity' => 0,                 
                    );           
                    $id = $sale_items['id'];
                    $this->db->where('id', $id);
                    $this->db->update('sma_sale_items', $update2); 
                }
            }
        }
    }

}

$this->db->select('sale_items.*, tax_rates.code as tax_code, tax_rates.name as tax_name, tax_rates.rate as tax_rate, products.image,products.size, products.details as details, product_variants.name as variant, products.hsn_code as hsn_code, products.second_name as second_name,products.quantity as stock, products.unit as base_unit_id, units.code as base_unit_code,products.split_quantity,products.split,products.inner_ean_number,products.outer_ean_number')
->join('products', 'products.id=sale_items.product_id', 'left')
->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
->join('tax_rates', 'tax_rates.id=sale_items.tax_rate_id', 'left')
->join('units', 'units.id=products.unit', 'left')

->group_by('sale_items.id')
->order_by('id', 'asc');
if ($sale_id && !$return_id) {
    $this->db->where('sale_id', $sale_id);
} elseif ($return_id) {
    $this->db->where('sale_id', $return_id);
}
$this->db->where('is_delete', 0);
$this->db->where('sale_items.quantity !=',0.0000);
$this->db->where('sale_items.is_return',0);
$q = $this->db->get('sale_items');

if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $row->image= base_url('/assets/uploads/').$row->image;
        $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
        $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
        $row->outer_ean_number= chop($outer_ean_number);
        $data[] = $row;
    }
    return $data;
}
return false;
}


public function getAllInvoiceItems_old($sale_id, $return_id = null) {
    $this->db->select('sale_items.*, tax_rates.code as tax_code, tax_rates.name as tax_name, tax_rates.rate as tax_rate, products.image,products.size, products.details as details, product_variants.name as variant, products.hsn_code as hsn_code, products.second_name as second_name,products.quantity as stock, products.unit as base_unit_id, units.code as base_unit_code,products.split_quantity,products.split,products.inner_ean_number,products.outer_ean_number')
    ->join('products', 'products.id=sale_items.product_id', 'left')
    ->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
    ->join('tax_rates', 'tax_rates.id=sale_items.tax_rate_id', 'left')
    ->join('units', 'units.id=products.unit', 'left')

    ->group_by('sale_items.id')
    ->order_by('id', 'asc');
    if ($sale_id && !$return_id) {
        $this->db->where('sale_id', $sale_id);
    } elseif ($return_id) {
        $this->db->where('sale_id', $return_id);
    }
    $this->db->where('is_delete', 0);
    $this->db->group_start();
    $this->db->where('sale_items.quantity !=',0.0000);       
    $this->db->or_where('is_zero','Y');
    $this->db->group_end();
    $q = $this->db->get('sale_items');

    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $row->image= base_url('/assets/uploads/').$row->image;
            $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
            $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
            $row->outer_ean_number= chop($outer_ean_number);
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function getAllInvoiceItems1($sale_id,$return_id = null){
    $this->db->select('sale_items.*, tax_rates.code as tax_code, tax_rates.name as tax_name, tax_rates.rate as tax_rate, products.image, products.details as details, product_variants.name as variant, products.hsn_code as hsn_code, products.second_name as second_name, products.unit as base_unit_id, units.code as base_unit_code')
    ->join('products', 'products.id=sale_items.product_id', 'left')
    ->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
    ->join('tax_rates', 'tax_rates.id=sale_items.tax_rate_id', 'left')
    ->join('units', 'units.id=products.unit', 'left')
    ->group_by('sale_items.id')
    ->order_by('id', 'asc');
    if ($sale_id && !$return_id) {
        $this->db->where('sale_id', $sale_id);
    } elseif ($return_id) {
        $this->db->where('sale_id', $return_id);
    }
    $query = $this->db->get('sale_items');
        // $query = $this->db->get();

    return $query->result_array();
}
public function getAllInvoiceItemsWithDetails($sale_id)
{
    $this->db->select('sale_items.*, products.details, product_variants.name as variant');
    $this->db->join('products', 'products.id=sale_items.product_id', 'left')
    ->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
    ->group_by('sale_items.id');
    $this->db->order_by('id', 'asc');
    $q = $this->db->get_where('sale_items', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
}

public function getAllQuoteItems($quote_id)
{
    $q = $this->db->get_where('quote_items', ['quote_id' => $quote_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function get_supplier($customer_id)
{
    $q = $this->db->get_where('companies', ['customer_group_id' => $customer_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function logistic_planing(){
    $this->db->select('order_logistic_planing.*,companies.name');
    $this->db->from('order_logistic_planing');
    $this->db->join('companies', 'companies.id=order_logistic_planing.delivery_man', 'left');
    $query = $this->db->get();
    return$query->result_array();
    
}
 // public function get_supplier($customer_id)
 //    {

 //    return $this->db->get_where('companies', ['customer_group_id' => $customer_id])->result();
 //    }
    // public function get_supplier($customer_id)
    // {
    //     $q = $this->db->get_where('companies', ['customer_group_id' => $customer_id], );
    //     if ($q->num_rows() > 0) {
    //         return $q->row();
    //     }
    //     return false;
    // }

public function getCostingLines($sale_item_id, $product_id, $sale_id = null)
{
    if ($sale_id) {
        $this->db->where('sale_id', $sale_id);
    }
    $orderby = ($this->Settings->accounting_method == 1) ? 'asc' : 'desc';
    $this->db->order_by('id', $orderby);
    $q = $this->db->get_where('costing', ['sale_item_id' => $sale_item_id, 'product_id' => $product_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getDeliveryByID($id)
{
    $q = $this->db->get_where('deliveries', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getDeliveryBySaleID($sale_id)
{
    $q = $this->db->get_where('deliveries', ['sale_id' => $sale_id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getInvoiceByID($id) {
    $this->db->select('sma_sales.*, sma_routes.route_name, sma_users.first_name, sma_users.last_name, sma_routes.route_number, sma_companies.vat_no, sma_companies.accound_no, sma_addresses.line1, sma_addresses.line2, sma_addresses.state, sma_addresses.country,sma_sales.over_all_discount as invoiceWiseDiscountPercentage, sma_sales.over_all_amount as invoiceWiseDiscountAmount');
    $this->db->from('sma_sales');
    $this->db->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left');
    $this->db->join('sma_routes', 'sma_routes.id = sma_companies.route', 'left');
    $this->db->join('sma_addresses', 'sma_companies.id = sma_addresses.company_id', 'left');
    $this->db->join('sma_users', 'sma_users.id = sma_sales.created_by', 'left');
    $this->db->where('sma_sales.id', $id);
    $this->db->limit(1);

    $query = $this->db->get();

    if ($query->num_rows() > 0) {
        return $query->row();
    } else {
        return false;
    }
}



public function getInvoicePayments($sale_id)
{
    $this->db->order_by('id', 'asc');
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
}

public function getItemByID($id)
{
    $q = $this->db->get_where('sale_items', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }

    return false;
}

public function getItemRack($product_id, $warehouse_id)
{
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse_id], 1);
    if ($q->num_rows() > 0) {
        $wh = $q->row();
        return $wh->rack;
    }
    return false;
}

public function getPaymentByID($id)
{
    $q = $this->db->get_where('payments', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getPaymentsForSale($sale_id)
{
    $this->db->select('payments.date, payments.paid_by, payments.amount, payments.cc_no, payments.cheque_no, payments.reference_no, users.first_name, users.last_name, type')
    ->join('users', 'users.id=payments.created_by', 'left');
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getPaypalSettings()
{
    $q = $this->db->get_where('paypal', ['id' => 1]);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getProductByCode($code)
{
    $q = $this->db->get_where('sma_products', ['code' => $code], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getProductByName($name)
{
    $q = $this->db->get_where('sma_products', ['name' => $name], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getProductComboItems($pid, $warehouse_id = null)
{
    $this->db->select('sma_products.id as id,sma_combo_items.item_code as code,sma_combo_items.quantity as qty,sma_products.name as name,sma_products.type as type,sma_warehouses_products.quantity as quantity')
    ->join('sma_products', 'sma_products.code=sma_combo_items.item_code', 'left')
    ->join('sma_warehouses_products', 'sma_warehouses_products.product_id=sma_products.id', 'left')
    ->group_by('sma_combo_items.id');
    if ($warehouse_id) {
        $this->db->where('sma_warehouses_products.warehouse_id', $warehouse_id);
    }
    $q = $this->db->get_where('sma_combo_items', ['sma_combo_items.product_id' => $pid]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
    return false;
}

public function getProductNames($term, $warehouse_id, $pos = false, $limit = 5)
{
    $wp = "( SELECT product_id, warehouse_id, quantity as quantity from {$this->db->dbprefix('warehouses_products')} ) FWP";

    $this->db->select('products.*, FWP.quantity as quantity, categories.id as category_id, categories.name as category_name', false)
    ->join($wp, 'FWP.product_id=products.id', 'left')
            // ->join('warehouses_products FWP', 'FWP.product_id=products.id', 'left')
    ->join('categories', 'categories.id=products.category_id', 'left')
    ->group_by('products.id');
    if ($this->Settings->overselling) {
        $this->db->where("({$this->db->dbprefix('products')}.name LIKE '%" . $term . "%' OR {$this->db->dbprefix('products')}.code LIKE '%" . $term . "%' OR  concat({$this->db->dbprefix('products')}.name, ' (', {$this->db->dbprefix('products')}.code, ')') LIKE '%" . $term . "%')");
    } else {
        $this->db->where("((({$this->db->dbprefix('products')}.track_quantity = 0 OR FWP.quantity > 0) AND FWP.warehouse_id = '" . $warehouse_id . "') OR {$this->db->dbprefix('products')}.type != 'standard') AND "
            . "({$this->db->dbprefix('products')}.name LIKE '%" . $term . "%' OR {$this->db->dbprefix('products')}.code LIKE '%" . $term . "%' OR  concat({$this->db->dbprefix('products')}.name, ' (', {$this->db->dbprefix('products')}.code, ')') LIKE '%" . $term . "%')");
    }
        // $this->db->order_by('products.name ASC');
    if ($pos) {
        $this->db->where('hide_pos !=', 1);
    }
    $this->db->limit($limit);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
}

public function getProductOptionByID($id)
{
    $q = $this->db->get_where('product_variants', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getProductOptions($product_id, $warehouse_id, $all = null)
{
    $wpv = "( SELECT option_id, warehouse_id, quantity from {$this->db->dbprefix('warehouses_products_variants')} WHERE product_id = {$product_id}) FWPV";
    $this->db->select('product_variants.id as id, product_variants.name as name, product_variants.price as price, product_variants.quantity as total_quantity, FWPV.quantity as quantity', false)
    ->join($wpv, 'FWPV.option_id=product_variants.id', 'left')
            //->join('warehouses', 'warehouses.id=product_variants.warehouse_id', 'left')
    ->where('product_variants.product_id', $product_id)
    ->group_by('product_variants.id');

    if (!$this->Settings->overselling && !$all) {
        $this->db->where('FWPV.warehouse_id', $warehouse_id);
        $this->db->where('FWPV.quantity >', 0);
    }
    $q = $this->db->get('product_variants');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getProductQuantity($product_id, $warehouse)
{
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse], 1);
    if ($q->num_rows() > 0) {
            return $q->row_array(); //$q->row();
        }
        return false;
    }

    public function getProductVariantByName($name, $product_id)
    {
        $q = $this->db->get_where('product_variants', ['name' => $name, 'product_id' => $product_id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getProductVariants($product_id)
    {
        $q = $this->db->get_where('product_variants', ['product_id' => $product_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getProductWarehouseOptionQty($option_id, $warehouse_id)
    {
        $q = $this->db->get_where('warehouses_products_variants', ['option_id' => $option_id, 'warehouse_id' => $warehouse_id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getPurchaseItemByID($id)
    {
        $q = $this->db->get_where('purchase_items', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getPurchaseItems($purchase_id)
    {
        return $this->db->get_where('purchase_items', ['purchase_id' => $purchase_id])->result();
    }

    public function getQuoteByID($id)
    {
        $q = $this->db->get_where('quotes', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getReturnByID($id)
    {
        $q = $this->db->get_where('sales', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getReturnBySID($sale_id)
    {
        $q = $this->db->get_where('sales', ['sale_id' => $sale_id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }
      public function check_quantity($item_id){
      $q = $this->db->get_where('sma_products', ['id' => $item_id], 1);
      if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;   
}

public function getSaleCosting($sale_id)
{
    $q = $this->db->get_where('costing', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getSaleItemByID($id)
{
    $q = $this->db->get_where('sale_items', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getSkrillSettings()
{
    $q = $this->db->get_where('skrill', ['id' => 1]);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getStaff()
{
    if (!$this->Owner) {
        $this->db->where('group_id !=', 1);
    }
    $this->db->where('group_id !=', 3)->where('group_id !=', 4);
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getTaxRateByName($name)
{
    $q = $this->db->get_where('tax_rates', ['name' => $name], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getTransferItems($transfer_id)
{
    return $this->db->get_where('purchase_items', ['transfer_id' => $transfer_id])->result();
}

public function getWarehouseProduct($pid, $wid)
{
    $this->db->select($this->db->dbprefix('products') . '.*, ' . $this->db->dbprefix('warehouses_products') . '.quantity as quantity')
    ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left');
    $q = $this->db->get_where('products', ['warehouses_products.product_id' => $pid, 'warehouses_products.warehouse_id' => $wid]);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getWarehouseProductQuantity($warehouse_id, $product_id)
{
    $q = $this->db->get_where('warehouses_products', ['warehouse_id' => $warehouse_id, 'product_id' => $product_id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function resetSaleActions($id, $return_id = null, $check_return = null)
{
    if ($sale = $this->getInvoiceByID($id)) {
        if ($check_return && $sale->sale_status == 'returned') {
            $this->session->set_flashdata('warning', lang('sale_x_action'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'welcome');
        }
        if ($sale->sale_status == 'completed') {
            if ($costings = $this->getSaleCosting($id)) {
                foreach ($costings as $costing) {
                    $pi = null;
                    if ($costing->purchase_id) {
                        $purchase_items = $this->getPurchaseItems($costing->purchase_id);
                        foreach ($purchase_items as $row) {
                            if ($row->product_id == $costing->product_id && $row->option_id == $costing->option_id) {
                                $pi = $row;
                            }
                        }
                    } elseif ($costing->transfer_id) {
                        $purchase_items = $this->getTransferItems($costing->transfer_id);
                        foreach ($purchase_items as $row) {
                            if ($row->product_id == $costing->product_id && $row->option_id == $costing->option_id) {
                                $pi = $row;
                            }
                        }
                    }
                    if ($pi) {
                        $this->site->setPurchaseItem(['id' => $pi->id, 'product_id' => $pi->product_id, 'option_id' => $pi->option_id], $costing->quantity);
                    } else {
                        $pi = $this->site->getPurchasedItem(['product_id' => $costing->product_id, 'option_id' => $costing->option_id ? $costing->option_id : null, 'purchase_id' => null, 'transfer_id' => null, 'warehouse_id' => $sale->warehouse_id]);
                        $this->site->setPurchaseItem(['id' => $pi->id, 'product_id' => $pi->product_id, 'option_id' => $pi->option_id], $costing->quantity);
                    }
                }
                $this->db->delete('costing', ['id' => $costing->id]);
            }
            $items = $this->getAllInvoiceItems($id);
            $this->site->syncQuantity(null, null, $items);
            $this->sma->update_award_points($sale->grand_total, $sale->customer_id, $sale->created_by, true);
            return $items;
        }
    }
}

public function syncQuantity($sale_id)
{
    if ($sale_items = $this->getAllInvoiceItems($sale_id)) {
        foreach ($sale_items as $item) {
            $this->site->syncProductQty($item->product_id, $item->warehouse_id);
            if (isset($item->option_id) && !empty($item->option_id)) {
                $this->site->syncVariantQty($item->option_id, $item->warehouse_id);
            }
        }
    }
}

public function topupGiftCard($data = [], $card_data = null)
{
    if ($this->db->insert('gift_card_topups', $data)) {
        $this->db->update('gift_cards', $card_data, ['id' => $data['card_id']]);
        return true;
    }
    return false;
}

public function updateCostingAndPurchaseItem($return_item, $product_id, $quantity)
{
    $bln_quantity = $quantity;
    if ($costings = $this->getCostingLines($return_item['id'], $product_id)) {
        foreach ($costings as $costing) {
            if ($costing->quantity > $bln_quantity && $bln_quantity != 0) {
                $qty = $costing->quantity                                                                                     - $bln_quantity;
                $bln = $costing->quantity_balance && $costing->quantity_balance >= $bln_quantity ? $costing->quantity_balance - $bln_quantity : 0;
                $this->db->update('costing', ['quantity' => $qty, 'quantity_balance' => $bln], ['id' => $costing->id]);
                $bln_quantity = 0;
                break;
            } elseif ($costing->quantity <= $bln_quantity && $bln_quantity != 0) {
                $this->db->delete('costing', ['id' => $costing->id]);
                $bln_quantity = ($bln_quantity - $costing->quantity);
            }
        }
    }
    $clause = ['product_id' => $product_id, 'warehouse_id' => $return_item['warehouse_id'], 'purchase_id' => null, 'transfer_id' => null, 'option_id' => $return_item['option_id']];
    $this->site->setPurchaseItem($clause, $quantity);
    $this->site->syncQuantity(null, null, null, $product_id);
}

public function updateDelivery($id, $data = [])
{
    if ($this->db->update('deliveries', $data, ['id' => $id])) {
        return true;
    }
    return false;
}
public function update_quantity($id, $data){

  $this->db->where('id', $id);
  $this->db->update('products', $data);

}

public function update_product_stock($productid,$iteamid,$orderqty)
{

    $check_quantity = $this->sales_model->check_quantity($productid);

    $sale_items = $this->db->query("select id,quantity,order_qty,order_type from sma_sale_items where id='$iteamid'")->row_array();
    $order_qty=$sale_items['order_qty'];
    $quantity=$sale_items['quantity'];
    $order_type = $sale_items['order_type'];
    $quan = round($check_quantity->quantity);


    if ($quantity != $orderqty) 
    {
       if ($order_type == "box")
       { 
        if ($quantity < $quan) 
        {
            $item_unit_quantity = round($orderqty - $quantity);

        } else 
        {
            $item_unit_quantity = round($quantity - $orderqty);
        }

        $check_quantity = $this->sales_model->check_quantity($productid);
        $quan = round($check_quantity->quantity);
        $quantity = ($quan - $item_unit_quantity);
        $data = ['quantity' => $quantity];
        $cid = $productid;
        $c = $this->update_quantity($cid, $data);

    } else 
    {
     $products1 = $this->db->query("select parent_id,pack,id from sma_products where id='$productid'")->row_array();
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
    //echo 'a';
      //  $quantity= $quan-$item_unit_quantity;

      // $result1 = ($quantity/$pack);
      // $result3 = intval($quan1 + $result1); 
      // $split_qty = ($quantity %$pack);  
      // $data = ['quantity' => $result3,'split_quantity' => $quantity];

        $quantity= $quan-$item_unit_quantity;
        $data = ['split_quantity' => $quantity];

      //print_r($data);exit();
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

public function updateGiftCard($id, $data = [])
{
    $this->db->where('id', $id);
    if ($this->db->update('gift_cards', $data)) {
        return true;
    }
    return false;
}

public function updateOptionQuantity($option_id, $quantity)
{
    if ($option = $this->getProductOptionByID($option_id)) {
        $nq = $option->quantity - $quantity;
        if ($this->db->update('product_variants', ['quantity' => $nq], ['id' => $option_id])) {
            return true;
        }
    }
    return false;
}

public function updatePayment($id, $data = [], $customer_id = null)
{
    $opay = $this->getPaymentByID($id);
    if ($this->db->update('payments', $data, ['id' => $id])) {
        $this->site->syncSalePayments($data['sale_id']);
        if ($opay->paid_by == 'gift_card') {
            $gc = $this->site->getGiftCardByNO($opay->cc_no);
            $this->db->update('gift_cards', ['balance' => ($gc->balance + $opay->amount)], ['card_no' => $opay->cc_no]);
        } elseif ($opay->paid_by == 'deposit') {
            if (!$customer_id) {
                $sale        = $this->getInvoiceByID($opay->sale_id);
                $customer_id = $sale->customer_id;
            }
            $customer = $this->site->getCompanyByID($customer_id);
            $this->db->update('companies', ['deposit_amount' => ($customer->deposit_amount + $opay->amount)], ['id' => $customer->id]);
        }
        if ($data['paid_by'] == 'gift_card') {
            $gc = $this->site->getGiftCardByNO($data['cc_no']);
            $this->db->update('gift_cards', ['balance' => ($gc->balance - $data['amount'])], ['card_no' => $data['cc_no']]);
        } elseif ($customer_id && $data['paid_by'] == 'deposit') {
            $customer = $this->site->getCompanyByID($customer_id);
            $this->db->update('companies', ['deposit_amount' => ($customer->deposit_amount - $data['amount'])], ['id' => $customer_id]);
        }
        return true;
    }
    return false;
}

public function updateProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id)
{
    if ($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
        $nq = $option->quantity - $quantity;
        if ($this->db->update('warehouses_products_variants', ['quantity' => $nq], ['option_id' => $option_id, 'warehouse_id' => $warehouse_id])) {
            $this->site->syncVariantQty($option_id, $warehouse_id);
            return true;
        }
    } else {
        $nq = 0 - $quantity;
        if ($this->db->insert('warehouses_products_variants', ['option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $nq])) {
            $this->site->syncVariantQty($option_id, $warehouse_id);
            return true;
        }
    }
    return false;
}

public function updateSale($id, $data, $items = [], $attachments = [])
{

    $this->db->trans_start();
    $this->resetSaleActions($id, false, true);
    if ($data['sale_status'] == 'Invoiced') {
        $this->Settings->overselling = true;
        $cost = $this->site->costing($items, true);
    }

    if ($this->db->update('sales', $data, ['id' => $id]) && $this->db->delete('sale_items', ['sale_id' => $id]) && $this->db->delete('costing', ['sale_id' => $id])) {
        foreach ($items as $item) {
            $item['sale_id'] = $id;
            $this->db->insert('sale_items', $item);
            $sale_item_id = $this->db->insert_id();
            if ($data['sale_status'] == 'Accept' && $this->site->getProductByID($item['product_id'])) {
                $item_costs = $this->site->item_costing($item);
                foreach ($item_costs as $item_cost) {
                    if (isset($item_cost['date']) || isset($item_cost['pi_overselling'])) {
                        $item_cost['sale_item_id'] = $sale_item_id;
                        $item_cost['sale_id']      = $id;
                        $item_cost['date']         = date('Y-m-d', strtotime($data['date']));
                        if (!isset($item_cost['pi_overselling'])) {
                            $this->db->insert('costing', $item_cost);
                        }
                    } else {
                        foreach ($item_cost as $ic) {
                            $ic['sale_item_id'] = $sale_item_id;
                            $ic['sale_id']      = $id;
                            $item_cost['date']  = date('Y-m-d', strtotime($data['date']));
                            if (!isset($ic['pi_overselling'])) {
                                $this->db->insert('costing', $ic);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment['subject_id']   = $id;
                $attachment['subject_type'] = 'sale';
                $this->db->insert('attachments', $attachment);
            }
        }

        if ($data['sale_status'] == 'Invoiced') {
            $this->site->syncPurchaseItems($cost);
        }

        $this->site->syncSalePayments($id);
        $this->site->syncQuantity($id);
        $sale = $this->getInvoiceByID($id);
        $this->sma->update_award_points($data['grand_total'], $data['customer_id'], $sale->created_by);
    }
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        log_message('error', 'An errors has been occurred while adding the sale (Update:Sales_model.php)');
    } else {
        return true;
    }
    return false;
}

public function updateStatus($id, $status, $reason_note,$accept_date,$accept_id)
{
    $this->db->trans_start();
    $sale  = $this->getInvoiceByID($id);
    $items = $this->getAllInvoiceItems($id);
    $cost  = [];

    if ($status == 'Invoiced' && $sale->sale_status != 'Invoiced') {
        foreach ($items as $item) {
            $items_array[] = (array) $item;
        }
        $cost = $this->site->costing($items_array);
    }
    if ($status != 'Invoiced' && $sale->sale_status == 'Invoiced') {
        $this->resetSaleActions($id);
    }

    if ($this->db->update('sales', ['sale_status' => $status, 'reason_note' => $reason_note,'accept_date'=>$accept_date,'accept_user_id'=> $accept_id], ['id' => $id])) {
        if ($status == 'Invoiced' && $sale->sale_status != 'Invoiced') {
            $this->db->delete('costing', ['sale_id' => $id]);
            foreach ($items as $item) {
                $item = (array) $item;
                if ($this->site->getProductByID($item['product_id'])) {
                    $item_costs = $this->site->item_costing($item);
                    foreach ($item_costs as $item_cost) {
                        if (isset($item_cost['date']) || isset($item_cost['pi_overselling'])) {
                            $item_cost['sale_item_id'] = $item['id'];
                            $item_cost['sale_id']      = $id;
                            $item_cost['date']         = date('Y-m-d', strtotime($sale->date));
                            if (!isset($item_cost['pi_overselling'])) {
                                $this->db->insert('costing', $item_cost);
                            }
                        } else {
                            foreach ($item_cost as $ic) {
                                $ic['sale_item_id'] = $item['id'];
                                $ic['sale_id']      = $id;
                                $ic['date']         = date('Y-m-d', strtotime($sale->date));
                                if (!isset($ic['pi_overselling'])) {
                                    $this->db->insert('costing', $ic);
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($cost)) {
                $this->site->syncPurchaseItems($cost);
            }
            $this->site->syncQuantity($id);
        }
    }
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        log_message('error', 'An errors has been occurred while adding the sale (UpdataStatus:Sales_model.php)');
    } else {
        return true;
    }
    return false;
}

public function get_all_product($limit,$offset){
 $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name,{$this->db->dbprefix('products')}.product_details as product_details, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status,split,size", false)
 ->join('categories', 'products.category_id=categories.id', 'left')
 ->join('units', 'products.unit=units.id', 'left')
 ->join('brands', 'products.brand=brands.id', 'left')
 ->where('products.status',1)
 ->limit($limit,$offset)
 ->group_by('products.id');
 $q = $this->db->get('products');
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
return false;

}
public function get_all_product1($search_product,$limit,$offset){
    $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status,split,size", false)
    ->join('categories', 'products.category_id=categories.id', 'left')
    ->join('units', 'products.unit=units.id', 'left')
    ->join('brands', 'products.brand=brands.id', 'left')
            // ->like('products.name',$search_product)
            // ->or_like('products.code',$search_product)
    ->where('products.name', $search_product)
    ->or_where('products.code', $search_product)
    ->group_by('products.id')
    ->where('products.status',1)
    ->limit($limit,$offset);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
    return false;

}
//     public function get_all_product(array $where = NULL, array $fields = NULL, $limit = NULL, $start = NULL)
// {
//     $query = NULL;
//     // return all records with all fields from table
//     if($fields == NULL and $where == NULL){
//     $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
//             //->from('products')
//             ->join('categories', 'products.category_id=categories.id', 'left')
//             ->join('units', 'products.unit=units.id', 'left')
//             ->join('brands', 'products.brand=brands.id', 'left')
//             ->where('products.status',1)
//             ->group_by('products.id');
//            // $q = $this->db->get('products');
//            $this->db->limit($limit, $start);
//             $query = $this->db->get('products');

//         if ($query->num_rows() > 0 ) {

//             return $query->result();
//         }
//         else
//             return false;
//     }
//     elseif($fields != NULL and $where == NULL){

//         $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
//             //->from('products')
//             ->join('categories', 'products.category_id=categories.id', 'left')
//             ->join('units', 'products.unit=units.id', 'left')
//             ->join('brands', 'products.brand=brands.id', 'left')
//             ->where('products.status',1)
//             ->group_by('products.id');
//            // $q = $this->db->get('products');


//         $this->db->limit($limit, $start);
//         //$this->db->select($fields);
//         $query = $this->db->get('products');

//         if ($query->num_rows() > 0) 
//             return $query->result();

//         else
//             return false;

//     }
//     elseif($fields == NULL and $where != NULL){
//         $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
//             //->from('products')
//             ->join('categories', 'products.category_id=categories.id', 'left')
//             ->join('units', 'products.unit=units.id', 'left')
//             ->join('brands', 'products.brand=brands.id', 'left')
//             ->where('products.status',1)
//             ->group_by('products.id');
//             //$q = $this->db->get('products');
//             $this->db->limit($limit, $start);
//              $query = $this->db->get_where('products', $where);
//              if ($query->num_rows() > 0) 
//             return $query->result();

//         else
//             return false;
//     }
//     else{
//         $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
//             //->from('products')
//             ->join('categories', 'products.category_id=categories.id', 'left')
//             ->join('units', 'products.unit=units.id', 'left')
//             ->join('brands', 'products.brand=brands.id', 'left')
//             ->where('products.status',1)
//             ->group_by('products.id');
//             //$q = $this->db->get('products');
//         $this->db->limit($limit, $start);
//        // $this->db->select($fields);
//         $query = $this->db->get_where('products', $where);

//         if ($query->result() > 0 )
//             return $query->result();

//         else
//             return false;
//     }
// }

public function get_count() {
 $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
            //->from('products')
 ->join('categories', 'products.category_id=categories.id', 'left')
 ->join('units', 'products.unit=units.id', 'left')
 ->join('brands', 'products.brand=brands.id', 'left')
 ->where('products.status',1)
 ->group_by('products.id');
 $q = $this->db->get('products');
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
return false;
}

public function get_count1($search_product) {
 $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
 ->join('categories', 'products.category_id=categories.id', 'left')
 ->join('units', 'products.unit=units.id', 'left')
 ->join('brands', 'products.brand=brands.id', 'left')
 ->where('products.status',1)
 ->group_by('products.id')
 ->like('products.code', $search_product)
 ->or_like('products.name', $search_product);
 $q = $this->db->get('products');
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
return false;
}
public function get_count_categories($categories_id) {
 $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
 ->join('categories', 'products.category_id=categories.id', 'left')
 ->join('units', 'products.unit=units.id', 'left')
 ->join('brands', 'products.brand=brands.id', 'left')
 ->where('products.status',1)
 ->where('products.category_id',$categories_id)
 ->group_by('products.id');

           // ->like('products.price', $search_product)
           // ->or_like('products.name', $search_product);
 $q = $this->db->get('products');
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
return false;
}

public function get_product_categories($categories_id,$limit,$offset) {
 $this->db->select("products.id as productid, {$this->db->dbprefix('products')}.image as image, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('brands')}.name as brand, {$this->db->dbprefix('categories')}.name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, {$this->db->dbprefix('units')}.code as unit, '' as rack, alert_quantity,{$this->db->dbprefix('products')}.status as status", false)
 ->join('categories', 'products.category_id=categories.id', 'left')
 ->join('units', 'products.unit=units.id', 'left')
 ->join('brands', 'products.brand=brands.id', 'left')
 ->where('products.status',1)
 ->where('products.category_id',$categories_id)
 ->group_by('products.id');
 $q = $this->db->get('products');
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
return false;
}
public function addlogistic_planing($data){
 if ($this->db->insert('order_logistic_planing', $data)) {

    return true;
}
return false;

}

public function picking_reference_no_all($sale_id){
  $this->db->select('sma_sales.reference_no,sma_sales.customer,sma_companies.accound_no,,sma_sales.deliverydate,sma_routes.route_number,sma_addresses.line1,sma_addresses.line2,sma_addresses.city,sma_addresses.state,sma_addresses.country')
  ->from('sma_sales')
  ->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left')
  ->join('sma_routes', 'sma_companies.route = sma_routes.id', 'left')
  ->join('sma_addresses', 'sma_addresses.company_id  = sma_companies.id', 'left')
  ->where_in('sma_sales.id', $sale_id);
        //->group_by('sma_companies.accound_no');
       // ->group_by('sma_routes.route_number');
  $q = $this->db->get();
  if ($q->num_rows() > 0) {
    foreach (($q->result_array()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public function picking_route_number_all($sale_id){
  $this->db->select('sma_routes.route_number')
  ->from('sma_sales')
  ->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left')
  ->join('sma_routes', 'sma_companies.route = sma_routes.id', 'left')
  ->where_in('sma_sales.id', $sale_id)
  ->group_by('sma_routes.route_number');
  $q = $this->db->get();
  if ($q->num_rows() > 0) {
    foreach (($q->result_array()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public function picking_accound_no_all($sale_id) {

  $this->db->select("sma_sales.id,sma_companies.accound_no,sma_sales.staff_note,sma_sales.note,sma_sales.created_by,sma_companies.name,sma_users.first_name,last_name,sma_users.group_id,sma_companies.group_id as cgroup_id")
  ->from('sma_sales')
  ->join('sma_companies', 'sma_companies.id = sma_sales.customer_id', 'left')
  ->join('sma_users', 'sma_users.id = sma_sales.created_by', 'left')
  ->where_in('sma_sales.id', $sale_id);
		//	->group_by('sma_companies.accound_no');
//   return $q = $this->db->query("SELECT sma_sales.id,sma_companies.accound_no,sma_sales.staff_note,sma_sales.note,sma_sales.created_by,sma_users.group_id,sma_companies.group_id as cgroup_id FROM `sma_sales` LEFT JOIN sma_companies ON sma_companies.id = sma_sales.customer_id LEFT JOIN sma_users ON sma_users.id = sma_sales.customer_id WHERE sma_sales.id IN $sale_id")->result_array();
  $q = $this->db->get();
//echo $this->db->last_query();exit();
  if ($q->num_rows() > 0) {
   foreach (($q->result_array()) as $row) {
    $data[] = $row;
}
return $data;
}
return false;
}

public function view_picking_history($sale_id, $return_id = null)
{

  $this->db->select($this->db->dbprefix('sale_items') . '.id as id,sale_id,picked_qty,product_id,product_name,product_code,order_type,products.size, sum(' . $this->db->dbprefix('sale_items') . '.order_qty) as total,sum(' . $this->db->dbprefix('sale_items') . '.confirmed_qty) as confirmed_total,'.$this->db->dbprefix('products') .'.size,pack,price,bay,rack,split_price,split'  , false)
  ->from('sale_items')
  ->join('products', 'sale_items.product_id=products.id', 'left')
  ->where_in('sale_id', $sale_id)
  ->where('order_qty !=',0)
  ->group_by('product_code')
  ->group_by('order_type')
  ->order_by('product_code', "asc");
  $q = $this->db->get();

  if ($q->num_rows() > 0) {
    foreach (($q->result_array()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
} 
public function picking_all($sale_id, $return_id = null)
{
    $this->db->select('sma_sale_items.id as id, picked_qty, product_id, product_name, product_code, order_type,sma_products.size, SUM(sma_sale_items.quantity) as total, sma_products.size as product_size, pack, price, bay, rack, split_price, split', false)
             ->from('sma_sale_items')
             ->join('sma_products', 'sma_sale_items.product_id = sma_products.id', 'left')
             ->where_in('sale_id', $sale_id)
             ->where('sma_sale_items.is_delete !=', 1)
             ->group_by('product_code')
             ->group_by('order_type')
             ->order_by('product_code', 'asc');

    $q = $this->db->get();

    if ($q->num_rows() > 0) 
    {
        return $q->result_array();
    }

    return false;
}

public function picking_all1($sale_id, $picklist_number) {

  $q = $this->db->query("select sma_sale_items.id as id,sma_sale_items.picked_qty,sma_sale_items.order_type,
   sma_sale_items.product_id,product_name,product_code,order_type,sum(sma_sale_items.quantity) as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id  where sale_id in($sale_id) and sma_sale_items.is_short_qty_delete='0' and  sma_sale_items.product_id in(select product_id from sma_picked_product_details where sma_picked_product_details.picking_list_no='$picklist_number' and sma_picked_product_details.picked_qty!=sma_picked_product_details.actual_qty)  group by product_id,order_type")->result_array();

  if (count($q) > 0) {
   foreach ($q as $row) {
    $data[] = $row;
}
return $data;
}
return false;
}
public function picking_all2($sale_id, $product_id,$order_type) 
{

  $q = $this->db->query("select sma_sale_items.id as id,picked_qty,product_id,product_name,product_code,order_type,sale_id,sma_sale_items.confirmed_qty,sma_sale_items.unit_quantity as total,sma_products.size,pack,price,bay,rack,split_price,split from sma_sale_items left join sma_products on sma_products.id=sma_sale_items.product_id where sale_id in($sale_id) and sma_sale_items.product_id='$product_id' and sma_sale_items.order_type='$order_type'")->result_array();

  if (count($q) > 0) {
   foreach ($q as $row) {
    $data[] = $row;
}
return $data;
}
return false;
}
public function states_update($id)
{
    $data['sale_status'] = 'Accept';
    $data['accept_date'] = date('Y-m-d');
    $data['accept_user_id'] = $this->session->userdata('user_id');
    $this->db->where('id', $id);
    $this->db->update('sma_sales',$data);
    return true;
}


public function add_daily_sheet($ids){


    $daily_sheet = $this->db->query("select ref_id,fs FROM sma_order_ref")->row_array();

    $ref_id = $daily_sheet['ref_id'];
    $dss_no = $daily_sheet['fs'];
    $str2 = $dss_no+1;
    $data1['fs'] = $str2;

    $this->db->where('ref_id', $ref_id);
    $this->db->update('sma_order_ref',$data1);
    
    $dss_no='FS'.$str2;
    foreach ($ids as $id) {
        $data['sale_id'] = $id;
        $data['dss_no'] = $dss_no; 
        $data['date'] = date('Y-m-d');
        $data['created_by'] = $this->session->userdata('user_id');

        $this->db->insert('sma_daily_sales_sheet',$data);
    }
    return true;
}  

public function getPromosByProduct($pId)
{
    $today = date('Y-m-d');
    $this->db
    ->group_start()->where('start_date <=', $today)->or_where('start_date IS NULL')->group_end()
    ->group_start()->where('end_date >=', $today)->or_where('end_date IS NULL')->group_end();
    $q = $this->db->get_where('promos', ['id' => $pId]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function getPromost($t)
{
   $today = date('Y-m-d');
   return $data = $this->db->query("SELECT * FROM `sma_promos` WHERE type='invoice_amount' and amount <=$t and start_date <= '$today' and end_date >= '$today'")->result_array();
        // if(count($data)!=0){
        //   return $data; 
        // }else{
        //      return $data=array();
        // }
    //     $this->db
    //     ->group_start()->where('start_date <=', $today)->or_where('start_date IS NULL')->group_end()
    //     ->group_start()->where('end_date >=', $today)->or_where('end_date IS NULL')->group_end();
    //      $this->db->where('type','invoice_amount');
    //     $this->db->where('amount <=', $t);
    //   // $this->db->order_by("id", "asc");
    //     $q = $this->db->get('promos');
    //  // echo  $this->db->last_query();exit();
    // // echo $q->num_rows;exit();

    //       if ($q->num_rows() > 0)
    //       {
    //         foreach (($q->result()) as $row) 
    //         {
    //             $data[] = $row;
    //         }

    //         return $data;
    //     }else
    //     {
    //     return $data=array();
    //     }
}

public function getPromost2($id,$t){

  $this->db->where('type','invoice_amount');
  $this->db->where('amount <=',$t);
  $this->db->where('id <=',$id);
  $this->db->order_by("amount", "desc");
  $q = $this->db->get('promos');    
  if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public function getPromostion($id)
{
    $today = date('Y-m-d');
    $this->db
    ->group_start()->where('start_date <=', $today)->or_where('start_date IS NULL')->group_end()
    ->group_start()->where('end_date >=', $today)->or_where('end_date IS NULL')->group_end();
    $q = $this->db->get_where('sma_promos', ['type' => 'buy_get','product2buy'=>$id]);

    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return $data=array();
}


}
