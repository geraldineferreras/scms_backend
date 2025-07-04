<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BaseController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        // Set CORS headers for all API responses
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization, X-Requested-With, X-API-Key, Cache-Control, Pragma, Origin, Accept');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json');
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    protected function send_response($data, $status_code = 200) {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
    protected function send_success($data = null, $message = 'Success', $status_code = 200) {
        $response = [
            'status' => true,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->send_response($response, $status_code);
    }
    protected function send_error($message = 'Error', $status_code = 400, $data = null) {
        $response = [
            'status' => false,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->send_response($response, $status_code);
    }
    protected function get_json_input() {
        $input = file_get_contents('php://input');
        $data = json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_error('Invalid JSON format', 400);
            return null;
        }
        return $data;
    }
    protected function validate_required_fields($data, $required_fields) {
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($data->$field) || empty($data->$field)) {
                $missing_fields[] = $field;
            }
        }
        if (!empty($missing_fields)) {
            $this->send_error('Missing required fields: ' . implode(', ', $missing_fields), 400);
            return false;
        }
        return true;
    }
} 