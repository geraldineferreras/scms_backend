<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        // Simple CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
        
        // Handle OPTIONS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    public function index() {
        echo json_encode([
            'status' => true,
            'message' => 'Test controller is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function login() {
        echo json_encode([
            'status' => true,
            'message' => 'Test login endpoint is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} 