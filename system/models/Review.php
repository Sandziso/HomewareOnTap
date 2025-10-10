<?php
/**
 * Review Model - Handles product review management
 */
class Review {
    private $db;
    private $table = 'reviews';

    // Review statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get review by ID
     */
    public function getById($id) {
        $query = "SELECT r.*, p.name as product_name, u.first_name, u.last_name
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.id = :id AND r.deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }

    /**
     * Get reviews by product ID
     */
    public function getByProductId($productId, $status = null, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.email
                  FROM {$this->table} r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.product_id = :product_id AND r.deleted_at IS NULL";
        
        $params = [':product_id' => $productId];
        
        if ($status) {
            $query .= " AND r.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    /**
     * Get reviews by user ID
     */
    public function getByUserId($userId, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, p.name as product_name, p.image as product_image
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  WHERE r.user_id = :user_id AND r.deleted_at IS NULL
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit);
        $this->db->bind(':offset', $offset);
        
        return $this->db->resultSet();
    }

    /**
     * Get all reviews with optional filters
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $query = "SELECT r.*, p.name as product_name, u.first_name, u.last_name, u.email
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.deleted_at IS NULL";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['product_id'])) {
            $query .= " AND r.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['rating'])) {
            $query .= " AND r.rating = :rating";
            $params[':rating'] = $filters['rating'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(r.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(r.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR r.title LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add sorting
        $sortField = !empty($filters['sort_by']) ? $filters['sort_by'] : 'r.created_at';
        $sortOrder = !empty($filters['sort_order']) ? $filters['sort_order'] : 'DESC';
        $query .= " ORDER BY {$sortField} {$sortOrder}";
        
        // Add pagination
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    /**
     * Create a new review
     */
    public function create($data) {
        // Check if user has already reviewed this product
        $existingReview = $this->getUserProductReview($data['user_id'], $data['product_id']);
        
        if ($existingReview) {
            return [
                'success' => false,
                'error' => 'You have already reviewed this product',
                'review_id' => $existingReview->id
            ];
        }
        
        $query = "INSERT INTO {$this->table} 
                  (product_id, user_id, order_id, rating, title, comment, status, created_at, updated_at) 
                  VALUES 
                  (:product_id, :user_id, :order_id, :rating, :title, :comment, :status, NOW(), NOW())";
        
        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':product_id', $data['product_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':order_id', $data['order_id'] ?? null);
        $this->db->bind(':rating', $data['rating']);
        $this->db->bind(':title', $data['title'] ?? null);
        $this->db->bind(':comment', $data['comment'] ?? null);
        $this->db->bind(':status', $data['status'] ?? self::STATUS_PENDING);
        
        // Execute and return the new review ID if successful
        if ($this->db->execute()) {
            $reviewId = $this->db->lastInsertId();
            
            // Update product rating stats
            $this->updateProductRating($data['product_id']);
            
            return [
                'success' => true,
                'review_id' => $reviewId
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create review'
        ];
    }

    /**
     * Get user's review for a specific product
     */
    public function getUserProductReview($userId, $productId) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE user_id = :user_id AND product_id = :product_id AND deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        
        return $this->db->single();
    }

    /**
     * Update review details
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET updated_at = NOW()";
        
        $allowedFields = ['rating', 'title', 'comment', 'status'];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $query .= ", $key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        $query .= " WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->execute();
        
        // Update product rating stats if rating changed
        if ($result && isset($data['rating'])) {
            $review = $this->getById($id);
            if ($review) {
                $this->updateProductRating($review->product_id);
            }
        }
        
        return $result;
    }

    /**
     * Update review status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        
        return $this->db->execute();
    }

    /**
     * Soft delete a review
     */
    public function delete($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        $result = $this->db->execute();
        
        // Update product rating stats after deletion
        if ($result) {
            $review = $this->getById($id);
            if ($review) {
                $this->updateProductRating($review->product_id);
            }
        }
        
        return $result;
    }

    /**
     * Update product rating statistics
     */
    public function updateProductRating($productId) {
        $query = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                  FROM {$this->table}
                  WHERE product_id = :product_id 
                  AND status = :status
                  AND deleted_at IS NULL";
        
        $this->db->query($query);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':status', self::STATUS_APPROVED);
        
        $stats = $this->db->single();
        
        // Update product table with new rating data
        $updateQuery = "UPDATE products SET 
                        rating = :rating,
                        review_count = :review_count,
                        rating_breakdown = :rating_breakdown,
                        updated_at = NOW()
                        WHERE id = :product_id";
        
        $this->db->query($updateQuery);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':rating', $stats->average_rating ? round($stats->average_rating, 1) : 0);
        $this->db->bind(':review_count', $stats->total_reviews);
        
        $ratingBreakdown = [
            '5' => $stats->five_star,
            '4' => $stats->four_star,
            '3' => $stats->three_star,
            '2' => $stats->two_star,
            '1' => $stats->one_star
        ];
        
        $this->db->bind(':rating_breakdown', json_encode($ratingBreakdown));
        
        return $this->db->execute();
    }

    /**
     * Get product rating summary
     */
    public function getProductRatingSummary($productId) {
        $query = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                  FROM {$this->table}
                  WHERE product_id = :product_id 
                  AND status = :status
                  AND deleted_at IS NULL";
        
        $this->db->query($query);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':status', self::STATUS_APPROVED);
        
        $stats = $this->db->single();
        
        if ($stats->total_reviews > 0) {
            $stats->average_rating = round($stats->average_rating, 1);
            $stats->five_star_percent = round(($stats->five_star / $stats->total_reviews) * 100);
            $stats->four_star_percent = round(($stats->four_star / $stats->total_reviews) * 100);
            $stats->three_star_percent = round(($stats->three_star / $stats->total_reviews) * 100);
            $stats->two_star_percent = round(($stats->two_star / $stats->total_reviews) * 100);
            $stats->one_star_percent = round(($stats->one_star / $stats->total_reviews) * 100);
        }
        
        return $stats;
    }

    /**
     * Get review count by filters
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} r WHERE r.deleted_at IS NULL";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['product_id'])) {
            $query .= " AND r.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['rating'])) {
            $query .= " AND r.rating = :rating";
            $params[':rating'] = $filters['rating'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(r.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(r.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result->count;
    }

    /**
     * Get pending review count
     */
    public function getPendingCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = :status AND deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':status', self::STATUS_PENDING);
        
        $result = $this->db->single();
        return $result->count;
    }

    /**
     * Check if user can review a product (has purchased it)
     */
    public function canUserReviewProduct($userId, $productId) {
        $query = "SELECT oi.id 
                  FROM order_items oi
                  LEFT JOIN orders o ON oi.order_id = o.id
                  WHERE o.user_id = :user_id 
                  AND oi.product_id = :product_id
                  AND o.payment_status = :payment_status
                  LIMIT 1";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':payment_status', Order::PAYMENT_PAID);
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    /**
     * Get recent reviews
     */
    public function getRecent($limit = 10) {
        $query = "SELECT r.*, p.name as product_name, u.first_name, u.last_name
                  FROM {$this->table} r
                  LEFT JOIN products p ON r.product_id = p.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.status = :status AND r.deleted_at IS NULL
                  ORDER BY r.created_at DESC
                  LIMIT :limit";
        
        $this->db->query($query);
        $this->db->bind(':status', self::STATUS_APPROVED);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Get helpful votes for a review
     */
    public function getHelpfulVotes($reviewId) {
        $query = "SELECT 
                    SUM(CASE WHEN helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
                    SUM(CASE WHEN helpful = 0 THEN 1 ELSE 0 END) as not_helpful_count
                  FROM review_votes 
                  WHERE review_id = :review_id";
        
        $this->db->query($query);
        $this->db->bind(':review_id', $reviewId);
        
        return $this->db->single();
    }

    /**
     * Add helpful vote to a review
     */
    public function addHelpfulVote($reviewId, $userId, $helpful = true) {
        // Check if user has already voted
        $query = "SELECT id FROM review_votes WHERE review_id = :review_id AND user_id = :user_id";
        $this->db->query($query);
        $this->db->bind(':review_id', $reviewId);
        $this->db->bind(':user_id', $userId);
        
        $existingVote = $this->db->single();
        
        if ($existingVote) {
            // Update existing vote
            $query = "UPDATE review_votes SET helpful = :helpful, updated_at = NOW() WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $existingVote->id);
            $this->db->bind(':helpful', $helpful ? 1 : 0);
        } else {
            // Create new vote
            $query = "INSERT INTO review_votes (review_id, user_id, helpful, created_at, updated_at)
                      VALUES (:review_id, :user_id, :helpful, NOW(), NOW())";
            $this->db->query($query);
            $this->db->bind(':review_id', $reviewId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':helpful', $helpful ? 1 : 0);
        }
        
        return $this->db->execute();
    }
}
?>