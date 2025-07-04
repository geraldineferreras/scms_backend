<?php
class User_model extends CI_Model {
    public function get_by_email($email) {
        return $this->db->get_where('users', ['email' => $email])->row_array();
    }

    public function insert($data) {
        return $this->db->insert('users', $data);
    }

    public function get_all($role = null) {
        if ($role) {
            $this->db->where('role', $role);
        }
        return $this->db->get('users')->result_array();
    }

    public function get_by_id($user_id) {
        return $this->db->get_where('users', ['user_id' => $user_id])->row_array();
    }

    public function update($user_id, $data) {
        $this->db->where('user_id', $user_id);
        return $this->db->update('users', $data);
    }

    public function delete($user_id) {
        $this->db->where('user_id', $user_id);
        return $this->db->delete('users');
    }
} 