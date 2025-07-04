<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends BaseController {

    public function __construct() {
        parent::__construct();
        error_reporting(0);
        $this->load->model('User_model');
        $this->load->helper(['response', 'auth']);
        $this->load->library('Token_lib');
        
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization, X-Requested-With');
        header('Content-Type: application/json');
        
        // Handle OPTIONS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        log_message('debug', 'Incoming login data: ' . json_encode($data));

        $email = isset($data->email) ? $data->email : null;
        $password = isset($data->password) ? $data->password : null;

        if (empty($email) || empty($password)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Email and Password are required']));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid email format']));
            return;
        }

        $user = $this->User_model->get_by_email($email);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Account is inactive. Please contact administrator.']));
                return;
            }

            // Update last_login
            $this->User_model->update($user['user_id'], [
                'last_login' => date('Y-m-d H:i:s')
            ]);

            // Generate JWT token
            $token_payload = [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ];
            $token = $this->token_lib->generate_token($token_payload);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'role' => $user['role'],
                        'user_id' => $user['user_id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'status' => $user['status'],
                        'last_login' => date('Y-m-d H:i:s'),
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => $this->token_lib->get_expiration_time()
                    ]
                ]));
            return;
        }

        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => false, 'message' => 'Invalid email or password']));
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        log_message('debug', 'Incoming register data: ' . json_encode($data));

        $role = isset($data->role) ? strtolower($data->role) : null;
        $full_name = isset($data->full_name) ? $data->full_name : null;
        $email = isset($data->email) ? $data->email : null;
        $password = isset($data->password) ? $data->password : null;
        $program = isset($data->program) ? $data->program : null;
        $contact_num = isset($data->contact_num) ? $data->contact_num : null;
        $address = isset($data->address) ? $data->address : null;
        $errors = [];

        if (empty($role)) {
            $errors[] = 'Role is required.';
        }
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (empty($contact_num)) {
            $errors[] = 'Contact number is required.';
        }
        if (empty($address)) {
            $errors[] = 'Address is required.';
        }

        if (!empty($errors)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => implode(' ', $errors)]));
            return;
        }

        // Check if user already exists
        $existing_user = $this->User_model->get_by_email($email);
        if ($existing_user) {
            $this->output
                ->set_status_header(409)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User with this email already exists!']));
            return;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $user_id = generate_user_id(strtoupper(substr($role, 0, 3)));
        $dataToInsert = [
            'user_id' => $user_id,
            'role' => $role,
            'full_name' => $full_name,
            'email' => $email,
            'password' => $hashed_password,
            'contact_num' => $contact_num,
            'address' => $address,
            'program' => $program,
            'status' => 'active',
            'last_login' => null
        ];

        // Student-specific fields
        if ($role === 'student') {
            if (empty($data->student_num) || empty($data->qr_code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Student number and qr_code are required for student accounts.']));
                return;
            }
            $dataToInsert['student_num'] = $data->student_num;
            $dataToInsert['qr_code'] = $data->qr_code;
            
            // Add section_id only if provided
            if (!empty($data->section_id)) {
                $dataToInsert['section_id'] = $data->section_id;
            }
        }

        if ($this->User_model->insert($dataToInsert)) {
            $this->output
                ->set_status_header(201)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => ucfirst($role) . ' registered successfully!',
                    'data' => ['user_id' => $user_id]
                ]));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => ucfirst($role) . ' registration failed!']));
        }
    }

    // Get all users by role
    public function get_users() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $role = $this->input->get('role'); // admin, teacher, student
        
        if (empty($role)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role parameter is required']));
            return;
        }

        $role = strtolower($role);
        $users = $this->User_model->get_all($role);

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]));
    }

    // Get user by ID
    public function get_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $role = $this->input->get('role'); // admin, teacher, student
        $user_id = $this->input->get('user_id');
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id parameters are required']));
            return;
        }

        $role = strtolower($role);
        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ]));
    }

    // Update user
    public function update_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $role = isset($data->role) ? strtolower($data->role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id are required']));
            return;
        }

        $update_data = [];
        // Common fields
        if (isset($data->full_name)) $update_data['full_name'] = $data->full_name;
        if (isset($data->email)) $update_data['email'] = $data->email;
        if (isset($data->password)) $update_data['password'] = password_hash($data->password, PASSWORD_BCRYPT);
        if (isset($data->program)) $update_data['program'] = $data->program;
        if (isset($data->contact_num)) $update_data['contact_num'] = $data->contact_num;
        if (isset($data->address)) $update_data['address'] = $data->address;
        
        // Status field with validation
        if (isset($data->status)) {
            $new_status = strtolower($data->status);
            if ($new_status !== 'active' && $new_status !== 'inactive') {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Status must be either "active" or "inactive"']));
                return;
            }
            $update_data['status'] = $new_status;
        }
        
        // Student-specific fields
        if ($role === 'student') {
            if (isset($data->student_num)) $update_data['student_num'] = $data->student_num;
            if (isset($data->section_id)) $update_data['section_id'] = $data->section_id;
            if (isset($data->qr_code)) $update_data['qr_code'] = $data->qr_code;
        }

        if (empty($update_data)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'No data provided for update']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $success = $this->User_model->update($user_id, $update_data);
        if ($success) {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => 'User updated successfully']));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to update user']));
        }
    }

    // Delete user
    public function delete_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $role = isset($data->role) ? strtolower($data->role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id are required']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $success = $this->User_model->delete($user_id);
        if ($success) {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => 'User deleted successfully']));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to delete user']));
        }
    }

    // Admin method to change user status
    public function change_user_status() {
        // Require admin authentication
        $user_data = require_admin($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $target_role = isset($data->target_role) ? strtolower($data->target_role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        $new_status = isset($data->status) ? strtolower($data->status) : null;
        
        if (empty($target_role) || empty($user_id) || empty($new_status)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Target role, user_id, and status are required']));
            return;
        }

        if ($new_status !== 'active' && $new_status !== 'inactive') {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Status must be either "active" or "inactive"']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $target_role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $success = $this->User_model->update($user_id, ['status' => $new_status]);
        if ($success) {
            $status_text = $new_status === 'active' ? 'activated' : 'deactivated';
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => ucfirst($target_role) . ' ' . $status_text . ' successfully']));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to change user status']));
        }
    }

    // Token refresh method
    public function refresh_token() {
        $token = $this->token_lib->get_token_from_header();
        
        if (!$token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Token is required']));
            return;
        }
        
        $new_token = $this->token_lib->refresh_token($token);
        
        if (!$new_token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
            return;
        }
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $new_token,
                    'token_type' => 'Bearer',
                    'expires_in' => $this->token_lib->get_expiration_time()
                ]
            ]));
    }

    // Validate token method
    public function validate_token() {
        $token = $this->token_lib->get_token_from_header();
        
        if (!$token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Token is required']));
            return;
        }
        
        $payload = $this->token_lib->validate_token($token);
        
        if (!$payload) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
            return;
        }
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Token is valid',
                'data' => [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => $payload['email'],
                    'full_name' => $payload['full_name']
                ]
            ]));
    }

    // Logout method
    public function logout() {
        // With JWT, logout is typically handled client-side by removing the token
        // However, we can implement a token blacklist if needed for additional security
        // For now, we'll just return a success message
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true, 
                'message' => 'Logout successful. Please remove the token from client storage.'
            ]));
    }

    // Handle OPTIONS preflight requests (CORS)
    public function options() {
        // The BaseController constructor handles CORS and exits for OPTIONS requests.
    }
}