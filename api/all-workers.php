<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT w.*, u.name as user_name, u.email, u.phone, u.profile_image as user_profile_image,
                   COUNT(r.id) as review_count, AVG(r.rating) as avg_rating 
            FROM workers w 
            LEFT JOIN users u ON w.user_id = u.id 
            LEFT JOIN reviews r ON w.id = r.worker_id 
            WHERE w.status = 'active' ";
    
    // Apply filters
    $filters = [];
    $params = [];
    $types = '';
    
    if (!empty($_GET['type'])) {
        $filters[] = "w.type = ?";
        $params[] = $_GET['type'];
        $types .= 's';
    }
    
    if (!empty($_GET['location'])) {
        $filters[] = "w.location LIKE ?";
        $params[] = '%' . $_GET['location'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['min_rating'])) {
        $filters[] = "AVG(r.rating) >= ?";
        $params[] = (int)$_GET['min_rating'];
        $types .= 'i';
    }
    
    if (!empty($_GET['min_experience'])) {
        $filters[] = "w.experience_years >= ?";
        $params[] = (int)$_GET['min_experience'];
        $types .= 'i';
    }
    
    if (!empty($_GET['search'])) {
        $filters[] = "(w.name LIKE ? OR w.description LIKE ? OR w.skills LIKE ?)";
        $search = '%' . $_GET['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }
    
    $sql .= " GROUP BY w.id ";
    
    // Apply sorting
    $sort_columns = [
        'relevance' => 'w.created_at DESC',
        'rating' => 'avg_rating DESC',
        'experience' => 'w.experience_years DESC',
        'newest' => 'w.created_at DESC'
    ];
    
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
    $sql .= " ORDER BY " . ($sort_columns[$sort] ?? $sort_columns['relevance']);
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $workers = [];
    while ($row = $result->fetch_assoc()) {
        $row['formatted_rate'] = format_currency($row['hourly_rate']);
        $row['avg_rating'] = $row['avg_rating'] ?: 0;
        $row['review_count'] = $row['review_count'] ?: 0;
        
        // Use user profile image if available, otherwise use worker profile image or fallback
        $row['profile_image'] = $row['user_profile_image'] ?: $row['profile_image'] ?: 'https://picsum.photos/seed/' . $row['id'] . '/400/300.jpg';
        
        // Use user name if available, otherwise use worker name
        $row['name'] = $row['user_name'] ?: $row['name'] ?: 'Unknown Worker';
        
        // Use user contact info
        $row['email'] = $row['email'] ?: 'Not provided';
        $row['phone'] = $row['phone'] ?: 'Not provided';
        
        $workers[] = $row;
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM workers w WHERE w.status = 'active'";
    $count_params = [];
    $count_types = '';
    
    if (!empty($filters)) {
        $count_sql .= " AND " . implode(" AND ", $filters);
        // Use only the filters that apply to workers table (exclude review-related filters)
        $worker_filters = array_filter($filters, function($filter) {
            return !strpos($filter, 'AVG(r.rating)');
        });
        if (!empty($worker_filters)) {
            // Extract parameters for worker filters only
            $temp_params = $params;
            $temp_types = $types;
            // Remove the last 4 characters for each review-related parameter
            if (strpos($types, 'i') !== false && !empty($_GET['min_rating'])) {
                array_pop($temp_params);
                $temp_types = substr($temp_types, 0, -1);
            }
            $count_params = $temp_params;
            $count_types = $temp_types;
        }
    }
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true, 
        'data' => $workers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>