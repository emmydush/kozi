<?php
// Reporting & Analytics Extension
class AnalyticsSystem {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['analytics']['settings'];
    }
    
    // Get dashboard statistics
    public function get_dashboard_stats($date_range = '30') {
        try {
            $days = (int)$date_range;
            $start_date = date('Y-m-d', strtotime("-$days days"));
            
            $stats = [];
            
            // User statistics
            $stats['users'] = $this->get_user_stats($start_date);
            
            // Booking statistics
            $stats['bookings'] = $this->get_booking_stats($start_date);
            
            // Revenue statistics
            $stats['revenue'] = $this->get_revenue_stats($start_date);
            
            // Worker statistics
            $stats['workers'] = $this->get_worker_stats($start_date);
            
            // Service statistics
            $stats['services'] = $this->get_service_stats($start_date);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get dashboard stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user statistics
    private function get_user_stats($start_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_users,
                    COUNT(CASE WHEN role = 'worker' THEN 1 END) as total_workers,
                    COUNT(CASE WHEN role = 'employer' THEN 1 END) as total_employers,
                    COUNT(CASE WHEN role = 'worker' AND created_at >= ? THEN 1 END) as new_workers,
                    COUNT(CASE WHEN role = 'employer' AND created_at >= ? THEN 1 END) as new_employers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users
                FROM users
            ");
            $stmt->execute([$start_date, $start_date, $start_date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get user stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get booking statistics
    private function get_booking_stats($start_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_bookings,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_bookings,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount END), 0) as total_booking_value
                FROM bookings
                WHERE created_at >= ? OR created_at IS NULL
            ");
            $stmt->execute([$start_date, $start_date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get booking stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get revenue statistics
    private function get_revenue_stats($start_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN status = 'completed' AND created_at >= ? THEN amount END), 0) as recent_revenue,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
                    COUNT(CASE WHEN status = 'completed' AND created_at >= ? THEN 1 END) as recent_transactions,
                    COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_transaction_value
                FROM transactions
                WHERE created_at >= ? OR created_at IS NULL
            ");
            $stmt->execute([$start_date, $start_date, $start_date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get revenue stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get worker statistics
    private function get_worker_stats($start_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_workers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_workers,
                    COUNT(CASE WHEN status = 'pending_verification' THEN 1 END) as pending_workers,
                    COUNT(CASE WHEN is_featured = TRUE THEN 1 END) as featured_workers,
                    COALESCE(AVG(rating), 0) as avg_rating,
                    COALESCE(AVG(review_count), 0) as avg_reviews,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_workers
                FROM workers
                WHERE created_at >= ? OR created_at IS NULL
            ");
            $stmt->execute([$start_date, $start_date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get worker stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get service statistics
    private function get_service_stats($start_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    type,
                    COUNT(*) as booking_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(AVG(total_amount), 0) as avg_booking_value
                FROM bookings
                WHERE created_at >= ? OR created_at IS NULL
                GROUP BY type
                ORDER BY booking_count DESC
            ");
            $stmt->execute([$start_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get service stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Get revenue over time
    public function get_revenue_over_time($period = 'daily', $days = 30) {
        try {
            $start_date = date('Y-m-d', strtotime("-$days days"));
            
            switch ($period) {
                case 'daily':
                    $format = 'YYYY-MM-DD';
                    $sql = "DATE(created_at)";
                    break;
                case 'weekly':
                    $format = 'YYYY-"W"WW';
                    $sql = "TO_CHAR(created_at, 'YYYY-\"W\"WW')";
                    break;
                case 'monthly':
                    $format = 'YYYY-MM';
                    $sql = "TO_CHAR(created_at, 'YYYY-MM')";
                    break;
                default:
                    $format = 'YYYY-MM-DD';
                    $sql = "DATE(created_at)";
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    {$sql} as period,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as revenue,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as transactions
                FROM transactions
                WHERE created_at >= ? AND status = 'completed'
                GROUP BY period
                ORDER BY period
            ");
            $stmt->execute([$start_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get revenue over time: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user growth over time
    public function get_user_growth($period = 'daily', $days = 30) {
        try {
            $start_date = date('Y-m-d', strtotime("-$days days"));
            
            switch ($period) {
                case 'daily':
                    $sql = "DATE(created_at)";
                    break;
                case 'weekly':
                    $sql = "TO_CHAR(created_at, 'YYYY-\"W\"WW')";
                    break;
                case 'monthly':
                    $sql = "TO_CHAR(created_at, 'YYYY-MM')";
                    break;
                default:
                    $sql = "DATE(created_at)";
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    {$sql} as period,
                    COUNT(*) as new_users,
                    COUNT(CASE WHEN role = 'worker' THEN 1 END) as new_workers,
                    COUNT(CASE WHEN role = 'employer' THEN 1 END) as new_employers
                FROM users
                WHERE created_at >= ?
                GROUP BY period
                ORDER BY period
            ");
            $stmt->execute([$start_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get user growth: " . $e->getMessage());
            return [];
        }
    }
    
    // Get top performers
    public function get_top_performers($type = 'workers', $limit = 10) {
        try {
            switch ($type) {
                case 'workers':
                    $stmt = $this->db->prepare("
                        SELECT 
                            w.id,
                            w.name,
                            w.rating,
                            w.review_count,
                            COUNT(b.id) as total_bookings,
                            COALESCE(SUM(b.total_amount), 0) as total_revenue
                        FROM workers w
                        LEFT JOIN bookings b ON w.id = b.worker_id AND b.status = 'completed'
                        WHERE w.status = 'active'
                        GROUP BY w.id, w.name, w.rating, w.review_count
                        ORDER BY w.rating DESC, total_bookings DESC
                        LIMIT ?
                    ");
                    break;
                case 'employers':
                    $stmt = $this->db->prepare("
                        SELECT 
                            u.id,
                            u.name,
                            COUNT(b.id) as total_bookings,
                            COALESCE(SUM(b.total_amount), 0) as total_spent
                        FROM users u
                        LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'completed'
                        WHERE u.role = 'employer'
                        GROUP BY u.id, u.name
                        ORDER BY total_spent DESC
                        LIMIT ?
                    ");
                    break;
                default:
                    return [];
            }
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get top performers: " . $e->getMessage());
            return [];
        }
    }
    
    // Generate report
    public function generate_report($report_type, $filters = []) {
        try {
            switch ($report_type) {
                case 'revenue':
                    return $this->generate_revenue_report($filters);
                case 'bookings':
                    return $this->generate_bookings_report($filters);
                case 'users':
                    return $this->generate_users_report($filters);
                case 'workers':
                    return $this->generate_workers_report($filters);
                default:
                    return ['error' => 'Invalid report type'];
            }
        } catch (Exception $e) {
            error_log("Failed to generate report: " . $e->getMessage());
            return ['error' => 'Report generation failed'];
        }
    }
    
    // Generate revenue report
    private function generate_revenue_report($filters) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-d');
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(t.created_at) as date,
                    COUNT(*) as transactions,
                    COALESCE(SUM(t.amount), 0) as revenue,
                    pm.type as payment_method,
                    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as successful,
                    COUNT(CASE WHEN t.status = 'failed' THEN 1 END) as failed
                FROM transactions t
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
                WHERE t.created_at BETWEEN ? AND ?
                GROUP BY DATE(t.created_at), pm.type
                ORDER BY date
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to generate revenue report: " . $e->getMessage());
            return [];
        }
    }
    
    // Generate bookings report
    private function generate_bookings_report($filters) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-d');
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(b.created_at) as date,
                    b.service_type,
                    COUNT(*) as bookings,
                    COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled,
                    COALESCE(SUM(b.total_amount), 0) as total_value,
                    COALESCE(AVG(b.total_amount), 0) as avg_value
                FROM bookings b
                WHERE b.created_at BETWEEN ? AND ?
                GROUP BY DATE(b.created_at), b.service_type
                ORDER BY date
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to generate bookings report: " . $e->getMessage());
            return [];
        }
    }
    
    // Generate users report
    private function generate_users_report($filters) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-d');
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(u.created_at) as date,
                    u.role,
                    COUNT(*) as new_users,
                    COUNT(CASE WHEN u.status = 'active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN u.is_verified = TRUE THEN 1 END) as verified_users
                FROM users u
                WHERE u.created_at BETWEEN ? AND ?
                GROUP BY DATE(u.created_at), u.role
                ORDER BY date
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to generate users report: " . $e->getMessage());
            return [];
        }
    }
    
    // Generate workers report
    private function generate_workers_report($filters) {
        $start_date = $filters['start_date'] ?? date('Y-m-01');
        $end_date = $filters['end_date'] ?? date('Y-m-d');
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(w.created_at) as date,
                    w.type,
                    COUNT(*) as new_workers,
                    COUNT(CASE WHEN w.status = 'active' THEN 1 END) as active_workers,
                    COUNT(CASE WHEN w.is_featured = TRUE THEN 1 END) as featured_workers,
                    COALESCE(AVG(w.rating), 0) as avg_rating,
                    COALESCE(AVG(w.review_count), 0) as avg_reviews
                FROM workers w
                WHERE w.created_at BETWEEN ? AND ?
                GROUP BY DATE(w.created_at), w.type
                ORDER BY date
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to generate workers report: " . $e->getMessage());
            return [];
        }
    }
    
    // Export report to CSV
    public function export_to_csv($data, $filename) {
        if (!$this->settings['export_enabled']) {
            return false;
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), 'csv_export');
        $handle = fopen($temp_file, 'w');
        
        if (!empty($data)) {
            // Headers
            fputcsv($handle, array_keys($data[0]));
            
            // Data
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
        }
        
        fclose($handle);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temp_file));
        
        readfile($temp_file);
        unlink($temp_file);
        
        return true;
    }
    
    // Get real-time statistics
    public function get_real_time_stats() {
        if (!$this->settings['real_time_stats']) {
            return [];
        }
        
        try {
            $stats = [];
            
            // Active users (last 5 minutes)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as active_users
                FROM user_sessions
                WHERE last_activity >= NOW() - INTERVAL '5 minutes'
            ");
            $stats['active_users'] = $stmt->fetchColumn();
            
            // Pending bookings
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as pending_bookings
                FROM bookings
                WHERE status = 'pending'
            ");
            $stats['pending_bookings'] = $stmt->fetchColumn();
            
            // Recent transactions
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as recent_transactions
                FROM transactions
                WHERE created_at >= CURRENT_DATE
            ");
            $stats['recent_transactions'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Failed to get real-time stats: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize analytics system
$analytics_system = new AnalyticsSystem();
?>
