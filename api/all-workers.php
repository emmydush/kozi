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
    
    if (!empty($_GET['type'])) {
        $filters[] = "w.type = :type";
        $params[':type'] = $_GET['type'];
    }
    
    if (!empty($_GET['location'])) {
        $filters[] = "w.location ILIKE :location";
        $params[':location'] = '%' . $_GET['location'] . '%';
    }
    
    if (!empty($_GET['min_rating'])) {
        $filters[] = "AVG(r.rating) >= :min_rating";
        $params[':min_rating'] = (int)$_GET['min_rating'];
    }
    
    if (!empty($_GET['min_experience'])) {
        $filters[] = "w.experience_years >= :min_experience";
        $params[':min_experience'] = (int)$_GET['min_experience'];
    }
    
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $filters[] = "(w.name ILIKE :search1 OR w.description ILIKE :search2 OR w.skills ILIKE :search3)";
        $params[':search1'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
    }
    
    if (!empty($filters)) {
        $sql .= " AND " . implode(" AND ", $filters);
    }
    
    $sql .= " GROUP BY w.id, u.id ";
    
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
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $workers = [];
    foreach ($result as $row) {
        $row['formatted_rate'] = format_currency($row['hourly_rate'] ?? 0);
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
    
    // Apply the same filters for count
    if (!empty($filters)) {
        // Create a simplified count query without the GROUP BY
        $count_sql_with_filters = "SELECT COUNT(DISTINCT w.id) as total FROM workers w 
                                    LEFT JOIN users u ON w.user_id = u.id 
                                    LEFT JOIN reviews r ON w.id = r.worker_id 
                                    WHERE w.status = 'active' AND " . implode(" AND ", $filters);
        $count_stmt = $conn->prepare($count_sql_with_filters);
        $count_stmt->execute($params);
    } else {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute();
    }
    
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total = $count_result['total'] ?? 0;
    
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
    error_log("Workers API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
