<?php
// Mobile App API Extension
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class MobileAPI {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['mobile-api']['settings'];
    }
    
    // Main API router
    public function handle_request() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Parse URL to get endpoint
        $path = parse_url($request_uri, PHP_URL_PATH);
        $path_parts = explode('/', trim($path, '/'));
        
        // Remove 'extensions/mobile-api' from path
        $api_index = array_search('mobile-api', $path_parts);
        if ($api_index !== false) {
            $path_parts = array_slice($path_parts, $api_index + 1);
        }
        
        $endpoint = $path_parts[0] ?? '';
        $id = $path_parts[1] ?? null;
        
        // Rate limiting
        if ($this->settings['rate_limiting'] && !$this->check_rate_limit()) {
            $this->send_response(['error' => 'Rate limit exceeded'], 429);
            return;
        }
        
        // Route to appropriate handler
        switch ($endpoint) {
            case 'auth':
                $this->handle_auth($method, $id);
                break;
            case 'users':
                $this->handle_users($method, $id);
                break;
            case 'workers':
                $this->handle_workers($method, $id);
                break;
            case 'jobs':
                $this->handle_jobs($method, $id);
                break;
            case 'bookings':
                $this->handle_bookings($method, $id);
                break;
            case 'messages':
                $this->handle_messages($method, $id);
                break;
            case 'notifications':
                $this->handle_notifications($method, $id);
                break;
            case 'payments':
                $this->handle_payments($method, $id);
                break;
            case 'reviews':
                $this->handle_reviews($method, $id);
                break;
            default:
                $this->send_response(['error' => 'Endpoint not found'], 404);
        }
    }
    
    // Authentication handlers
    private function handle_auth($method, $action) {
        switch ($method) {
            case 'POST':
                switch ($action) {
                    case 'login':
                        $this->auth_login();
                        break;
                    case 'register':
                        $this->auth_register();
                        break;
                    case 'refresh':
                        $this->auth_refresh();
                        break;
                    case 'logout':
                        $this->auth_logout();
                        break;
                    default:
                        $this->send_response(['error' => 'Invalid auth action'], 400);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    private function auth_login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            $this->send_response(['error' => 'Email and password required'], 400);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($data['password'], $user['password'])) {
                $token = $this->generate_token($user['id']);
                
                // Store token
                $this->store_api_token($user['id'], $token);
                
                $this->send_response([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'phone' => $user['phone'],
                        'profile_image' => $user['profile_image']
                    ],
                    'token' => $token,
                    'expires_in' => $this->settings['token_expiry']
                ]);
            } else {
                $this->send_response(['error' => 'Invalid credentials'], 401);
            }
        } catch (PDOException $e) {
            $this->send_response(['error' => 'Database error'], 500);
        }
    }
    
    private function auth_register() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->send_response(['error' => "Field $field is required"], 400);
                return;
            }
        }
        
        try {
            // Check if email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                $this->send_response(['error' => 'Email already exists'], 409);
                return;
            }
            
            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, role, phone) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashed_password,
                $data['role'],
                $data['phone'] ?? null
            ]);
            
            $user_id = $this->db->lastInsertId();
            $token = $this->generate_token($user_id);
            $this->store_api_token($user_id, $token);
            
            $this->send_response([
                'success' => true,
                'user' => [
                    'id' => $user_id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role']
                ],
                'token' => $token
            ], 201);
        } catch (PDOException $e) {
            $this->send_response(['error' => 'Registration failed'], 500);
        }
    }
    
    // User handlers
    private function handle_users($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_user($id);
                } else {
                    $this->get_current_user();
                }
                break;
            case 'PUT':
                if ($id) {
                    $this->update_user($id);
                } else {
                    $this->update_current_user();
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    private function get_current_user() {
        $user_id = $this->get_current_user_id();
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, role, phone, profile_image, created_at 
                FROM users WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $this->send_response(['user' => $user]);
            } else {
                $this->send_response(['error' => 'User not found'], 404);
            }
        } catch (PDOException $e) {
            $this->send_response(['error' => 'Database error'], 500);
        }
    }
    
    // Worker handlers
    private function handle_workers($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_worker($id);
                } else {
                    $this->get_workers();
                }
                break;
            case 'POST':
                $this->create_worker();
                break;
            case 'PUT':
                if ($id) {
                    $this->update_worker($id);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    private function get_workers() {
        $filters = $_GET;
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        try {
            $sql = "
                SELECT w.*, u.name, u.profile_image, u.phone
                FROM workers w
                JOIN users u ON w.user_id = u.id
                WHERE w.status = 'active'
            ";
            $params = [];
            
            if (isset($filters['type'])) {
                $sql .= " AND w.type = ?";
                $params[] = $filters['type'];
            }
            
            if (isset($filters['location'])) {
                $sql .= " AND w.location ILIKE ?";
                $params[] = '%' . $filters['location'] . '%';
            }
            
            if (isset($filters['min_rating'])) {
                $sql .= " AND w.rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            $sql .= " ORDER BY w.is_featured DESC, w.rating DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $count_sql = str_replace("ORDER BY w.is_featured DESC, w.rating DESC LIMIT ? OFFSET ?", "", $sql);
            $count_sql = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) FROM', $count_sql);
            $count_params = array_slice($params, 0, -2);
            
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute($count_params);
            $total = $count_stmt->fetchColumn();
            
            $this->send_response([
                'workers' => $workers,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (PDOException $e) {
            $this->send_response(['error' => 'Database error'], 500);
        }
    }
    
    // Job handlers
    private function handle_jobs($method, $id) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_job($id);
                } else {
                    $this->get_jobs();
                }
                break;
            case 'POST':
                $this->require_auth();
                $this->create_job();
                break;
            case 'PUT':
                $this->require_auth();
                if ($id) {
                    $this->update_job($id);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    private function get_jobs() {
        $filters = $_GET;
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        try {
            $sql = "
                SELECT j.*, u.name as employer_name, u.profile_image as employer_image
                FROM jobs j
                JOIN users u ON j.employer_id = u.id
                WHERE j.status = 'active'
            ";
            $params = [];
            
            if (isset($filters['type'])) {
                $sql .= " AND j.type = ?";
                $params[] = $filters['type'];
            }
            
            if (isset($filters['location'])) {
                $sql .= " AND j.location ILIKE ?";
                $params[] = '%' . $filters['location'] . '%';
            }
            
            $sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->send_response(['jobs' => $jobs]);
        } catch (PDOException $e) {
            $this->send_response(['error' => 'Database error'], 500);
        }
    }
    
    // Booking handlers
    private function handle_bookings($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_booking($id);
                } else {
                    $this->get_bookings();
                }
                break;
            case 'POST':
                $this->create_booking();
                break;
            case 'PUT':
                if ($id) {
                    $this->update_booking($id);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    // Message handlers
    private function handle_messages($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_conversation($id);
                } else {
                    $this->get_message_threads();
                }
                break;
            case 'POST':
                $this->send_message();
                break;
            case 'PUT':
                if ($id) {
                    $this->mark_message_read($id);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    // Notification handlers
    private function handle_notifications($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                $this->get_notifications();
                break;
            case 'PUT':
                if ($id) {
                    $this->mark_notification_read($id);
                }
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    // Payment handlers
    private function handle_payments($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                $this->get_payment_history();
                break;
            case 'POST':
                $this->process_payment();
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    // Review handlers
    private function handle_reviews($method, $id) {
        $this->require_auth();
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->get_worker_reviews($id);
                } else {
                    $this->get_my_reviews();
                }
                break;
            case 'POST':
                $this->create_review();
                break;
            default:
                $this->send_response(['error' => 'Method not allowed'], 405);
        }
    }
    
    // Helper methods
    private function require_auth() {
        $token = $this->get_bearer_token();
        if (!$token || !$this->validate_token($token)) {
            $this->send_response(['error' => 'Unauthorized'], 401);
            exit;
        }
    }
    
    private function get_bearer_token() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function validate_token($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id FROM api_tokens 
                WHERE token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function get_current_user_id() {
        $token = $this->get_bearer_token();
        
        try {
            $stmt = $this->db->prepare("
                SELECT user_id FROM api_tokens 
                WHERE token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['user_id'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function generate_token($user_id) {
        return bin2hex(random_bytes(32));
    }
    
    private function store_api_token($user_id, $token) {
        try {
            $expires_at = date('Y-m-d H:i:s', time() + $this->settings['token_expiry']);
            
            $stmt = $this->db->prepare("
                INSERT INTO api_tokens (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$user_id, $token, $expires_at]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function check_rate_limit() {
        // Simple rate limiting based on IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $window = 60; // 1 minute
        $limit = 100; // 100 requests per minute
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM rate_limits 
                WHERE ip = ? AND created_at > NOW() - INTERVAL '1 minute'
            ");
            $stmt->execute([$ip]);
            $count = $stmt->fetchColumn();
            
            if ($count >= $limit) {
                return false;
            }
            
            // Log this request
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (ip, endpoint) VALUES (?, ?)
            ");
            $stmt->execute([$ip, $_SERVER['REQUEST_URI']]);
            
            return true;
        } catch (PDOException $e) {
            return true; // Allow on database error
        }
    }
    
    private function send_response($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new MobileAPI();
$api->handle_request();
?>
