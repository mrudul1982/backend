<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Companies_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addAddress($data)
    {
        if ($this->db->insert('addresses', $data)) {
            return true;
        }
        return false;
    }


    public function addCompanies($data = [])
    {
        if ($this->db->insert_batch('companies', $data)) {
            return true;
        }
        return false;
    }
    public function addCompanies1($data = [],$data1 =[],$data2 =[])
    {
        $i=0;
        foreach ($data as $key ) 
        {

            $this->db->insert('companies', $key);
            $cid = $this->db->insert_id();
            
            $this->db->insert('addresses', $data1[$i]); 
            $aid = $this->db->insert_id();
            $company_id = array('company_id' => $cid);
            $this->db->where('id',$aid);
            $this->db->update('addresses', $company_id);

            $this->db->insert('users', $data2[$i]); 
            $uid = $this->db->insert_id();
            $company_id2 = array('company_id' => $cid);
            $this->db->where('id',$uid);
            $this->db->update('users', $company_id2);
            $i++;
        }
        return true;
    }
    public function address($data = [])
    {
        if ($this->db->insert_batch('addresses', $data)) {
            return true;
        }
        return false;
    }
    public function allusersinsert($data = [])
    {
        if ($this->db->insert_batch('users', $data)) {
            return true;
        }
        return false;
    }
    public function manifest_data($data){
        if ($this->db->insert('manifest', $data)) {
           $id = $this->db->insert_id();
           return $id;

       }
       return false;
   }
   public function update_table_data($data,$id){
       $this->db->where('id',$id);
       if ($this->db->update('manifest', $data)) {
        return true;
    }
    return false;
}

public function addCompany($data = [] , $data1=[])
{
    if ($this->db->insert('sma_companies', $data)) {
        $cid = $this->db->insert_id();
        $this->db->insert('sma_addresses', $data1); 
        $aid = $this->db->insert_id();
        $company_id = array('company_id' => $cid);
        $this->db->where('id',$aid);
        $this->db->update('sma_addresses', $company_id);
        return $cid;
    }
    return false;
}

public function addDeposit($data, $cdata)
{
    if ($this->db->insert('deposits', $data) && $this->db->update('companies', $cdata, ['id' => $data['company_id']])) {
        return true;
    }
    return false;
}

public function deleteAddress($id)
{
    if ($this->db->delete('addresses', ['id' => $id])) {
        return true;
    }
    return false;
}

public function deleteBiller($id)
{
    if ($this->getBillerSales($id)) {
        return false;
    }
    $this->site->log('Biller', ['model' => $this->getCompanyByID($id)]);
    if ($this->db->delete('companies', ['id' => $id, 'group_name' => 'biller'])) {
        return true;
    }
    return false;
}

public function get_customer_details($id){
    $this->db->select('sum(grand_total)as total');
    $this->db->where('payment_status','Due');
    $this->db->where_not_in('sale_status','Reject');
    $this->db->where('customer_id',$id);
    $q = $this->db->get('sales');
    if ($q->num_rows() > 0) {
        return $q->row();
    }
}
public function get_customer_details2($id){
    $this->db->select('*');
    $this->db->where('payment_status','Due');
   // $this->db->where_not_in('sale_status','Reject');
    $this->db->where('customer_id',$id);
    $q = $this->db->get('sma_sales');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    } 
}

public function deleteCustomer($id)
{
    if ($this->getCustomerSales($id)) {
        return false;
    }
    $this->site->log('Customer', ['model' => $this->getCompanyByID($id)]);
    if ($this->db->delete('companies', ['id' => $id, 'group_name' => 'customer']) && $this->db->delete('users', ['company_id' => $id])) {
        return true;
    }
    return false;
}

public function deleteDeposit($id)
{
    $deposit = $this->getDepositByID($id);
    $company = $this->getCompanyByID($deposit->company_id);
    $cdata   = [
        'deposit_amount' => ($company->deposit_amount - $deposit->amount),
    ];
    if ($this->db->update('companies', $cdata, ['id' => $deposit->company_id]) && $this->db->delete('deposits', ['id' => $id])) {
        return true;
    }
    return false;
}

public function deleteSupplier($id)
{
    if ($this->getSupplierPurchases($id)) {
        return false;
    }
    $this->site->log('Supplier', ['model' => $this->getCompanyByID($id)]);
    if ($this->db->delete('companies', ['id' => $id, 'group_name' => 'supplier']) && $this->db->delete('users', ['company_id' => $id])) {
        return true;
    }
    return false;
}

public function getAddressByID($id)
{
    $q = $this->db->get_where('addresses', ['company_id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getAllBillerCompanies()
{
    $q = $this->db->get_where('companies', ['group_name' => 'biller']);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function get_customer_by_id($id)
{
    $query = $this->db->query("SELECT * FROM `sma_users` WHERE id='$id'");

     // print_r($query);exit();
    $q = $this->db->get_where('compay', ['company_id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}
public function getAllCustomerCompanies()
{
    $q = $this->db->get_where('companies', ['group_name' => 'customer']);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getAllCustomerGroups()
{
    $q = $this->db->get('customer_groups');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public  function getAllRoute()
{
   $q = $this->db->get('routes');			
   if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public  function get_type_of_business()
{
   $q = $this->db->get('sma_type_of_business');

   if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public  function getbusiness_category()
{
   $q = $this->db->get('sma_business_category');
   if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public  function getdefault_delivery_category()
{
   $q = $this->db->get('default_delivery_category');
   if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }
    return $data;
}
return false;
}
public function getAllPriceGroups()
{
    $q = $this->db->get('price_groups');
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getAllSupplierCompanies()
{
    $q = $this->db->get_where('companies', ['group_name' => 'supplier']);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function getcustomer_self_orders($id)
{
    $this->db->order_by('id', 'desc');
    $q = $this->db->get_where('sma_sales', ['customer_id' => $id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function getBillerSales($id)
{
    $this->db->where('biller_id', $id)->from('sales');
    return $this->db->count_all_results();
}

public function getBillerSuggestions($term, $limit = 10)
{
    $this->db->select('id, company as text');
    $this->db->where(" (id LIKE '%" . $term . "%' OR name LIKE '%" . $term . "%' OR company LIKE '%" . $term . "%') ");
    $q = $this->db->get_where('companies', ['group_name' => 'biller'], $limit);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
}

public function getCompanyAddresses($company_id)
{
    $q = $this->db->get_where('addresses', ['company_id' => $company_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getCompanyByEmail($email)
{
    $q = $this->db->get_where('companies', ['email' => $email], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}
public function getCompanyByname($name)
{
    $q = $this->db->get_where('companies', ['name' => $name], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}
public function getCompanyByID($id)
{
 $q = $this->db->query("select sma_companies.*,sma_users.first_name,sma_users.last_name,sma_business_category.category_name,sma_type_of_business.type_business,sma_addresses.line1,sma_addresses.line2 FROM `sma_companies` LEFT JOIN sma_users ON sma_companies.id = sma_users.company_id LEFT JOIN sma_type_of_business ON sma_companies.type_of_business=sma_type_of_business.id LEFT JOIN sma_business_category ON sma_companies.business_category=sma_business_category.id LEFT JOIN sma_addresses ON sma_addresses.company_id=sma_companies.id WHERE sma_companies.status='1' AND sma_companies.id='$id'");
 
 if ($q->num_rows() > 0) {
    return $q->row();
}
return false;
}

public function getCompanyByID1($id)
{
 $q = $this->db->query("select sma_companies.*,sma_users.first_name,sma_users.last_name,sma_business_category.category_name,sma_type_of_business.type_business,sma_addresses.line1,sma_addresses.line2 FROM `sma_companies` LEFT JOIN sma_users ON sma_companies.id = sma_users.company_id LEFT JOIN sma_type_of_business ON sma_companies.type_of_business=sma_type_of_business.id LEFT JOIN sma_business_category ON sma_companies.business_category=sma_business_category.id LEFT JOIN sma_addresses ON sma_addresses.company_id=sma_companies.id WHERE sma_companies.id='$id'");
 
 if ($q->num_rows() > 0) {
    return $q->row();
}
return false;
}
public function customers_activate($id){
   $data = ['status' => '1'];
   $data1 = array('active' => '1');
   $q = $this->db->get_where('users', ['company_id' => $id]);
   if($q->num_rows() > 0){
       $this->db->where('company_id', $id);
       $this->db->update('users', $data1);
   }
   $this->db->where('id', $id);
   return $this->db->update('companies', $data);
   return false;
}
public function customers_deactivate($id){
   $data = array('status' => '0');
   $data1 = array('active' => '0');
   $q = $this->db->get_where('users', ['company_id' => $id]);
   if($q->num_rows() > 0){
       $this->db->where('company_id', $id);
       $this->db->update('users', $data1);
   }
   $this->db->where('id', $id);
   return $this->db->update('companies', $data);
   return false;
}
public function supplier_activate($id){
    $data = ['supplier_status' => '1'];
    $this->db->where('id', $id);
    return $this->db->update('companies', $data);
    return false;
}
public function supplier_deactivate($id){
   $data = ['supplier_status' => '0'];
   $this->db->where('id', $id);
   return $this->db->update('companies', $data);
   return false;
}
public function getCompanyUsers($company_id)
{
    $q = $this->db->get_where('users', ['company_id' => $company_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}
public function getUsers($company_id)
{
    $q = $this->db->get_where('sma_users', ['id' => $company_id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }
        return $data;
    }
    return false;
}

public function getCustomerSales($id)
{
    $this->db->where('customer_id', $id)->from('sales');
    return $this->db->count_all_results();
}

public function getCustomerSuggestions($term, $limit = 10)
{
 $strlen = strlen($term);
 if($strlen >= '3'){
    $this->db->select("id, (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', accound_no, ')') END) as text, (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', accound_no, ')') END) as value, phone", false);
    $this->db->where(" (name LIKE '%" . $term . "%' OR company LIKE '%" . $term ."%' OR accound_no LIKE '%" . $term . "%' OR postal_code LIKE '%" . $term . "%') ");
    $q = $this->db->get_where('companies', ['group_name' => 'customer','status'=>'1'], $limit);

    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
}
}

public function getCustomerSuggestions_api($term, $limit = 100)
{
    $this->db->select("id, (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', accound_no, ')') END) as name,company,email,address,city,state,country,phone,postal_code,accound_no", false);
    $this->db->where(" (name LIKE '%" . $term . "%' OR company LIKE '%" . $term . "%' OR accound_no = '" . $term . "' OR postal_code LIKE '%" . $term . "%') ");
    
    $q = $this->db->get_where('sma_companies', ['group_name' => 'customer','status'=>'1'], $limit);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
}


public function getprodctSuggestions_api($term, $limit = 10)
{
    $data = array(); // Initialize the $data array

    $this->db->select("sma_products.id, sma_products.code as product_code, sma_products.name as product_name, sma_products.details, sma_products.category_id as category_name, sma_products.price, sma_products.cost, sma_products.image, sma_products.product_details, sma_products.tax_rate, sma_products.inner_ean_number, sma_products.outer_ean_number, sma_products.size, sma_products.price_1, sma_products.price_2, sma_products.price_3, sma_products.price_4, sma_products.price_5,sma_products.piece_price1, sma_products.piece_price2, sma_products.piece_price3, sma_products.piece_price4, sma_products.piece_price5, sma_products.split,featured,new_arrival,new_arrival_date, sma_products.is_discount_allow,sma_products.percentage as disc_percentage, sma_products.split_quantity, sma_products.split_price, sma_products.promotion as productDiscountApplicable, sma_products.promo_price as discountedBoxPrice, sma_products.start_date, sma_products.end_date, sma_products.bay, sma_products.rack, sma_products.pack as noOfPieceInBox,sma_tax_rates.rate,quantity as availableBoxQty,split_quantity as availablePieceQty,sma_brands.id as brand_id,sma_brands.name as brand_name");
    $this->db->join('sma_tax_rates', 'sma_tax_rates.id = sma_products.tax_rate', 'left');
    $this->db->join('sma_brands', 'sma_brands.id = sma_products.brand', 'left');     
    $this->db->where("(sma_products.id LIKE '%" . $term . "%' OR sma_products.code LIKE '%" . $term . "%' OR sma_products.name LIKE '%" . $term . "%' OR sma_products.price LIKE '%" . $term . "%')");
    $this->db->where('sma_products.parent_id', 0);
    $this->db->where('sma_products.status', '1');

    //applied by Mrudul
     $this->db->limit(20);
   //  $this->db->where('quantity >',0);
    $q = $this->db->get('sma_products');
    // echo $this->db->last_query();
    // exit(); 


    if ($q->num_rows() > 0) 
    {
        foreach (($q->result()) as $row) 
        {
            $row->inner_ean_number = str_replace(' ', '', $row->inner_ean_number);
            $outer_ean_number = str_replace(' ', '', $row->outer_ean_number);
            $row->outer_ean_number = chop($outer_ean_number);
            $row->availableBoxQty=(int)$row->availableBoxQty;
            $row->availablePieceQty=(int)$row->availablePieceQty;
            //   $row->availableBoxQty=1000;
          // $row->availablePieceQty=100;
            $data[] = $row;
        }

        return $data;
    }
}


public function getprodct_returnSuggestions_api($term, $limit = 10)
{
      $this->db->select("sma_products.id, sma_products.code as product_code, sma_products.name as product_name, sma_products.details, sma_products.category_id as category_name, sma_products.price, sma_products.cost, sma_products.image, sma_products.product_details, sma_products.tax_rate, sma_products.inner_ean_number, sma_products.outer_ean_number, sma_products.size, sma_products.price_1, sma_products.price_2, sma_products.price_3, sma_products.price_4, sma_products.price_5, sma_products.split, sma_products.is_discount_allow,sma_products.percentage as disc_percentage, sma_products.split_quantity, sma_products.split_price, sma_products.promotion as productDiscountApplicable, sma_products.promo_price as discountedBoxPrice, sma_products.start_date, sma_products.end_date, sma_products.bay, sma_products.rack, sma_products.pack as noOfPieceInBox,sma_tax_rates.rate");
    $this->db->join('sma_tax_rates', 'sma_tax_rates.id = sma_products.tax_rate', 'left');
    $this->db->where("(sma_products.id LIKE '%" . $term . "%' OR sma_products.code LIKE '%" . $term . "%' OR sma_products.name LIKE '%" . $term . "%' OR sma_products.price LIKE '%" . $term . "%')");
    $this->db->where('sma_products.parent_id', 0);
    $this->db->where('sma_products.status', '1');
     
    $q = $this->db->get('sma_products');
  
    if ($q->num_rows() > 0) 
    {
        foreach (($q->result()) as $row) 
        {
             $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
              $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
              $row->outer_ean_number= chop($outer_ean_number);
            $data[] = $row;
        }

        return $data;
    }
}


public function getprodctSuggestions($term, $limit = 10)
{
    $this->db->select("id, code as product_code,name as product_name,details,category_id as category_name,price,cost, image,product_details,tax_rate,inner_ean_number,outer_ean_number,size");
    $this->db->where(" (code='$term' OR name LIKE '%" . $term . "%') ");
    $this->db->where(" (outer_ean_number='' OR outer_ean_number = '0' OR outer_ean_number = 'null') ");
    $this->db->where(" (inner_ean_number='' OR inner_ean_number = '0' OR inner_ean_number = 'null') ");
    $q = $this->db->get_where('products',['status' => '1']);

    if ($q->num_rows() > 0) 
    {
        foreach (($q->result()) as $row) 
        {
            $data[] = $row;
        }

        return $data;
    }
}
public function getprodctcategory_id($id)
{
     $this->db->select("sma_products.id,sma_products.code as product_code,sma_products.name as product_name,details,category_id as category_name,price,cost, sma_products.price_1, sma_products.price_2, sma_products.price_3, sma_products.price_4, sma_products.price_5,sma_products.piece_price1, sma_products.piece_price2, sma_products.piece_price3, sma_products.piece_price4, sma_products.piece_price5,  sma_products.is_discount_allow,sma_products.percentage as disc_percentage,featured,new_arrival,new_arrival_date,sma_products.image,product_details,tax_rate,inner_ean_number,outer_ean_number,size,split,split_quantity,split_price,promotion as productDiscountApplicable,promo_price as discountedBoxPrice,start_date,end_date,bay,rack,pack as noOfPieceInBox,sma_tax_rates.rate,quantity as availableBoxQty,split_quantity as availablePieceQty,sma_brands.id as brand_id,sma_brands.name as brand_name");
    $this->db->join('sma_tax_rates', 'sma_tax_rates.id = sma_products.tax_rate', 'left');
     $this->db->join('sma_brands', 'sma_brands.id = sma_products.brand', 'left');

  //  $this->db->where('quantity >',0);
    $q = $this->db->get_where('sma_products',['sma_products.status' => '1','category_id'=>$id]);
    // print_r($this->db->last_query());exit();

    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
            $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
            $row->outer_ean_number= chop($outer_ean_number);
            $row->availableBoxQty=(int)$row->availableBoxQty;
            $row->availablePieceQty=(int)$row->availablePieceQty;
            //  $row->availableBoxQty=1000;
           //$row->availablePieceQty=100;
            $data[] = $row;
        }

        return $data;
    }
}


public function getprodct_returncategory_id($id)
{
    $this->db->select("id, code as product_code,name as product_name,details,category_id as category_name,price,cost, image,product_details,tax_rate,inner_ean_number,outer_ean_number,size,split,split_quantity,split_price,promotion as productDiscountApplicable,promo_price as discountedBoxPrice,start_date,end_date,bay,rack,pack as noOfPieceInBox");
   
    $q = $this->db->get_where('products',['status' => '1','category_id'=>$id]);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $row->inner_ean_number=str_replace(' ', '', $row->inner_ean_number);
              $outer_ean_number= str_replace(' ', '', $row->outer_ean_number);
              $row->outer_ean_number= chop($outer_ean_number);
            $data[] = $row;
        }

        return $data;
    }
}

public function getDepositByID($id)
{
    $q = $this->db->get_where('deposits', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
        return $q->row();
    }
    return false;
}

public function getSupplierPurchases($id)
{
    $this->db->where('supplier_id', $id)->from('purchases');
    return $this->db->count_all_results();
}

public function getSupplierSuggestions($term, $limit = 10)
{
    $this->db->select("id, (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', name, ')') END) as text", false);
    $this->db->where(" (id LIKE '%" . $term . "%' OR name LIKE '%" . $term . "%' OR company LIKE '%" . $term . "%' OR email LIKE '%" . $term . "%' OR phone LIKE '%" . $term . "%' OR vat_no LIKE '%" . $term . "%') ");
    $q = $this->db->get_where('companies', ['group_name' => 'supplier','supplier_status'=>'1'], $limit);
    if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
            $data[] = $row;
        }

        return $data;
    }
}

public function updateAddress($id, $data)
{
    if ($this->db->update('addresses', $data, ['id' => $id])) {
        return true;
    }
    return false;
}

public function updateCompany($id, $data = [],$address_data)
{	
    $this->db->where('id', $id);
    if ($this->db->update('companies', $data)) {
        $this->db->update('addresses', $address_data, ['company_id' => $id]);
        return true;
    }
    return false;
}

public function updateDeposit($id, $data, $cdata)
{
    if ($this->db->update('deposits', $data, ['id' => $id]) && $this->db->update('companies', $cdata, ['id' => $data['company_id']])) {
        return true;
    }
    return false;
}

public function getcustomer_self_orderst($id,$startIndex,$limit,$fromDate,$toDate)
{


$query = "select 
            sma_sales.id AS id,
            sma_sales.date,
            sma_sales.reference_no,
            sma_sales.biller,
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
            sma_sales.customer_id = '$id' 
            AND (sma_sales.date >= '$fromDate 00:00:00' AND sma_sales.date <= '$toDate 23:59:59')  
          ORDER BY 
            sma_sales.id DESC  
          LIMIT 
            $limit OFFSET $startIndex";

return $data = $this->db->query($query)->result();


}

public function add_credit_facility($id,$type)
{
    $people = array();
    $person = array();
    if($type=='day_wise')
    {
        $person['name'] = 'day_wise';
        $person['total_day'] = '30';
        $people[] = $person;
        $person = array();
    }else
    {
     $person['name'] = '';
     $person['total_day'] = '';
     $people[] = $person;
     $person = array(); 
 }

 if($type=='invoice_wise')
 {
   $person['name'] = 'invoice_wise';
   $person['total_invoice'] = '1';
   $people[] = $person;
   $person = array();
}else{
  $person['name'] = '';
  $person['total_invoice'] = '';
  $people[] = $person;
  $person = array();
}
if($type=='amount_wise')
{
    $person['name'] = '';
    $person['total_amount'] = '';
    $people[] = $person;
}else
{
    $person['name'] = '';
    $person['total_amount'] = '';
    $people[] = $person;
    $person = array();
}
$credit_facility=1;
if($type=='day_wise')
{
    $credit_type_name='Credit';
    $credit_facility=1;
}
if($type=='invoice_wise')
{
    $credit_type_name='TC';
    $credit_facility=1;
}
if($type=='amount_wise')
{
   $credit_type_name='Cash';
   $credit_facility=0;
}
$credit_type = json_encode($people);

$data = array( 
    'credit_facility'      => $credit_facility, 
    'credit_type' => $credit_type,'credit_type_name'=>$credit_type_name);

$this->db->where('id', $id);
$this->db->update('sma_companies', $data);
return true;
}

public function get_all_customer_list()
{
 $this->db->select("companies.id,companies.name,companies.company,companies.email,sma_addresses.line1 as addressLine1,sma_addresses.line2 as addressLine2,companies.city,companies.state as county,companies.country,companies.phone,companies.postal_code,companies.accound_no as account_no,companies.is_po,companies.vat_no,companies.business_category,companies.type_of_business,sma_business_category.category_name,sma_type_of_business.type_business,sma_users.username");
 $this->db->join('sma_addresses','sma_addresses.company_id=companies.id','left');
 $this->db->join('sma_business_category','sma_business_category.id=companies.business_category','left');
 $this->db->join('sma_type_of_business','sma_type_of_business.id=companies.type_of_business','left');
 $this->db->join('sma_users', 'companies.id = sma_users.company_id', 'left');
 $this->db->order_by('companies.name', 'ASC');
 $q = $this->db->get_where('sma_companies as companies', ['group_name' => 'customer','status'=>'1']);
 if ($q->num_rows() > 0) {
    foreach (($q->result()) as $row) {
        $data[] = $row;
    }

    return $data;
}
}

}
