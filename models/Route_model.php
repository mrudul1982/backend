<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Route_model extends CI_Model {
	public function __construct() {
		parent::__construct();
	}
	public function add_driver($data) {
		if ($this->db->insert('driver', $data)) {
			return true;
		}
		return false;
	}
	public function delete_driver($id) {
		if ($this->db->delete('driver', ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function getAllmanifest() {
		$this->db->where('manifest_id is NOT NULL', NULL, FALSE);
		$this->db->where('intransit', 'N');
		$this->db->order_by('id', 'desc');
		$q = $this->db->get('manifest');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}
	public function edit_driver($id) {
		$q = $this->db->get_where('driver', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}
	public function updateDriver($id, $data = []) {
		if ($this->db->update('driver', $data, ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function getAlldriver() {
	  return $this->db->query("select sma_users.id,sma_users.first_name,sma_users.last_name, sma_groups.id as role_id,sma_groups.name from sma_role_assign left join sma_users on sma_role_assign.user_id=sma_users.id left join sma_groups on sma_groups.id=sma_role_assign.role_id WHERE sma_groups.id='16' and sma_users.id!=''
UNION
SELECT sma_users.id,sma_users.first_name,sma_users.last_name, sma_groups.id as role_id,sma_groups.name  FROM `sma_users` left join sma_groups on sma_groups.id= sma_users.group_id WHERE group_id='16' and sma_users.id!=''")->result_array();
	//	$this->db->where_in('group_id', '16','');
// 		$this->db->where_in('group_id', ['16','8','14','15']);
// 		$q = $this->db->get('users');
// 		if ($q->num_rows() > 0) {
// 			foreach (($q->result()) as $row) {
// 				$data[] = $row;
// 			}
// 			return $data;
// 		}
// 		return false;
	}
	public function getAllvehicle() {
		$q = $this->db->get('vehicle');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}
	public function add_vehicle($data) {
		if ($this->db->insert('vehicle', $data)) {
			return true;
		}
		return false;
	}
	public function delete_vehicle($id) {
		if ($this->db->delete('vehicle', ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function edit_vehicle($id) {
		$q = $this->db->get_where('vehicle', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}
	public function updatevehicle($id, $data = []) {
		if ($this->db->update('vehicle', $data, ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function getAllcustomer() {
		$this->db->group_by('postal_code');
		$q = $this->db->get_where('companies', ['group_name' => 'customer', 'postal_code !=' => NULL, 'postal_code !=' => '']);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}
	public function delete_route($id) {
		if ($this->db->delete('routes', ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function edit($id) {
		$q = $this->db->get_where('routes', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}
	public function update($id, $data = []) {
		if ($this->db->update('routes', $data, ['id' => $id])) {
			return true;
		}
		return false;
	}
	public function vehicle_activate($id) {
		$data = ['status' => '1'];
		$this->db->where('id', $id);
		return $this->db->update('vehicle', $data);
		return false;
	}
	public function vehicle_deactivate($id) {
		$data = ['status' => '0'];
		$this->db->where('id', $id);
		return $this->db->update('vehicle', $data);
		return false;
	}

	public function driver_activate($id) {
		$data = ['status' => '1'];
		$this->db->where('id', $id);
		return $this->db->update('driver', $data);
		return false;
	}
	public function driver_deactivate($id) {
		$data = ['status' => '0'];
		$this->db->where('id', $id);
		return $this->db->update('driver', $data);
		return false;
	}
	public function route_activate($id) {
		$data = ['status' => '1'];
		$this->db->where('id', $id);
		return $this->db->update('routes', $data);
		return false;
	}
	public function route_deactivate($id) {
		$data = ['status' => '0'];
		$this->db->where('id', $id);
		return $this->db->update('routes', $data);
		return false;
	}
}
