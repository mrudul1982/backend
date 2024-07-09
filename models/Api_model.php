<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api_model extends CI_Model
{
    private $Settings;

    public function __construct()
    {
        parent::__construct();
        $this->Settings = $this->getSettings();
        $this->load->config('rest');

    }

    protected function getSettings()
    {
        $q = $this->db->get('sma_settings');
        if ($q !== false && $q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function addApiKey($data)
    {
        return $this->db->insert('api_keys', $data);
    }

    public function deleteApiKey($id)
    {
        return $this->db->delete('api_keys', ['id' => $id]);
    }

    public function generateKey()
    {
        return $this->_generate_key();
    }

    public function getApiKey($value, $field = 'key')
    {
        return  $this->db->get_where('api_keys', [$field => $value])->row();
    }

    public function getApiKeys()
    {
        return $this->db->get('api_keys')->result();
    }

    public function getUser($value, $field = 'id')
    {
        $q = $this->db->get_where('users', [$field => $value]);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function updateUserApiKey($user_id, $data)
    {
        return $this->db->update('api_keys', $data, ['user_id' => $user_id]);
    }

    private function _delete_key($key)
    {
        return $this->db
        ->where($this->config->item('rest_key_column'), $key)
        ->delete($this->config->item('rest_keys_table'));
    }

    private function _generate_key()
    {
        do {
            $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

            if ($salt === false) {
                $salt = hash('sha256', time() . mt_rand());
            }

            $new_key = substr($salt, 0, $this->config->item('rest_key_length'));
        } while ($this->_key_exists($new_key));

        return $new_key;
    }

    private function _get_key($key)
    {
        return $this->db
        ->where($this->config->item('rest_key_column'), $key)
        ->get($this->config->item('rest_keys_table'))
        ->row();
    }

    private function _insert_key($key, $data)
    {
        $data[$this->config->item('rest_key_column')] = $key;
        $data['date_created']                         = function_exists('now') ? now() : time();

        return $this->db
        ->set($data)
        ->insert($this->config->item('rest_keys_table'));
    }

    private function _key_exists($key)
    {
        return $this->db
        ->where($this->config->item('rest_key_column'), $key)
        ->count_all_results($this->config->item('rest_keys_table')) > 0;
    }

    private function _update_key($key, $data)
    {
        return $this->db
        ->where($this->config->item('rest_key_column'), $key)
        ->update($this->config->item('rest_keys_table'), $data);
    }

    public function get_login($username,$password)
    {  
        $this->load->admin_model('auth_model');
        $this->db->select('id,username,email,active,first_name,last_name,company,phone,avatar,gender,address,postcode,city,state,country,contact_person_name,group_id');
        $this->db->where('username',$username);
        $this->db->from('sma_users');
        return $this->db->get()->row_array();
    }


    public function get_customer_list($pattern)
    {
     $this->db->select('username,password,email,active,first_name,last_name,company,phone,avatar,gender,address,postcode,city,state,country,contact_person_name');
     $this->db->where('username',$pattern);
     $this->db->from('sma_users');
     return $this->db->get()->result_array();
 }


}
