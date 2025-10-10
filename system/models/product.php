<?php
/**
 * Product Model - Handles all product-related data operations
 */
class Product {
    private $db;
    private $table = 'products';

    // Product statuses
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_DRAFT = 2;
    const STATUS_OUT_OF_STOCK = 3;

    // Stock statuses
    const STOCK_IN_STOCK = 1;
    const STOCK_LOW_STOCK = 2;
    const STOCK_OUT_OF_STOCK = 0;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get product by ID
     */
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM {$this->table} p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = :id AND p.deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }

    /**
     * Get product by SKU
     */
    public function getBySku($sku) {
        $query = "SELECT * FROM {$this->table} WHERE sku = :sku AND deleted_at IS NULL";
        $this->db->query($query);
        $this->db->bind(':sku', $sku);
        
        return $this->db->single();
    }

    /**
     * Get all products with optional filters
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM {$this->table} p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.deleted_at IS NULL";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['min_price'])) {
            $query .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $query .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add sorting
        $sortField = !empty($filters['sort_by']) ? $filters['sort_by'] : 'p.created_at';
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
     * Get featured products
     */
    public function getFeatured($limit = 10) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM {$this->table} p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.featured = 1 AND p.status = :status AND p.deleted_at IS NULL 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $this->db->query($query);
        $this->db->bind(':status', self::STATUS_ACTIVE);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Create a new product
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (sku, name, description, price, sale_price, category_id, 
                   stock_quantity, min_stock_level, image, gallery_images, 
                   specifications, weight, dimensions, featured, status, created_at, updated_at) 
                  VALUES 
                  (:sku, :name, :description, :price, :sale_price, :category_id, 
                   :stock_quantity, :min_stock_level, :image, :gallery_images, 
                   :specifications, :weight, :dimensions, :featured, :status, NOW(), NOW())";
        
        $this->db->query($query);
        
        // Bind parameters
        $this->db->bind(':sku', $data['sku']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':sale_price', $data['sale_price'] ?? null);
        $this->db->bind(':category_id', $data['category_id']);
        $this->db->bind(':stock_quantity', $data['stock_quantity'] ?? 0);
        $this->db->bind(':min_stock_level', $data['min_stock_level'] ?? 5);
        $this->db->bind(':image', $data['image'] ?? null);
        $this->db->bind(':gallery_images', isset($data['gallery_images']) ? json_encode($data['gallery_images']) : null);
        $this->db->bind(':specifications', isset($data['specifications']) ? json_encode($data['specifications']) : null);
        $this->db->bind(':weight', $data['weight'] ?? null);
        $this->db->bind(':dimensions', $data['dimensions'] ?? null);
        $this->db->bind(':featured', $data['featured'] ?? 0);
        $this->db->bind(':status', $data['status'] ?? self::STATUS_ACTIVE);
        
        // Execute and return the new product ID if successful
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update product details
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET updated_at = NOW()";
        
        $allowedFields = [
            'sku', 'name', 'description', 'price', 'sale_price', 'category_id',
            'stock_quantity', 'min_stock_level', 'image', 'gallery_images',
            'specifications', 'weight', 'dimensions', 'featured', 'status'
        ];
        
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                // Handle JSON fields
                if ($key === 'gallery_images' || $key === 'specifications') {
                    $value = json_encode($value);
                }
                
                $query .= ", $key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        $query .= " WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->execute();
    }

    /**
     * Update product stock quantity
     */
    public function updateStock($id, $quantity) {
        $query = "UPDATE {$this->table} 
                  SET stock_quantity = :quantity, updated_at = NOW() 
                  WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':quantity', $quantity);
        
        return $this->db->execute();
    }

    /**
     * Decrement product stock quantity
     */
    public function decrementStock($id, $quantity = 1) {
        $query = "UPDATE {$this->table} 
                  SET stock_quantity = stock_quantity - :quantity, updated_at = NOW() 
                  WHERE id = :id AND deleted_at IS NULL AND stock_quantity >= :quantity";
        
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':quantity', $quantity);
        
        return $this->db->execute();
    }

    /**
     * Increment product stock quantity
     */
    public function incrementStock($id, $quantity = 1) {
        $query = "UPDATE {$this->table} 
                  SET stock_quantity = stock_quantity + :quantity, updated_at = NOW() 
                  WHERE id = :id AND deleted_at IS NULL";
        
        $this->db->query($query);
        $this->db->bind(':id', $id);
        $this->db->bind(':quantity', $quantity);
        
        return $this->db->execute();
    }

    /**
     * Soft delete a product
     */
    public function delete($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    /**
     * Check if SKU exists (excluding a specific product ID)
     */
    public function skuExists($sku, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} 
                  WHERE sku = :sku AND deleted_at IS NULL";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $this->db->query($query);
        $this->db->bind(':sku', $sku);
        
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    /**
     * Get product count by filters
     */
    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} p WHERE p.deleted_at IS NULL";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['min_price'])) {
            $query .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $query .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result->count;
    }

    /**
     * Get low stock products
     */
    public function getLowStock($limit = 20) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM {$this->table} p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.stock_quantity <= p.min_stock_level 
                  AND p.deleted_at IS NULL 
                  ORDER BY p.stock_quantity ASC 
                  LIMIT :limit";
        
        $this->db->query($query);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Get products by category
     */
    public function getByCategory($categoryId, $limit = 20, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM {$this->table} p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = :category_id 
                  AND p.status = :status 
                  AND p.deleted_at IS NULL 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind(':category_id', $categoryId);
        $this->db->bind(':status', self::STATUS_ACTIVE);
        $this->db->bind(':limit', $limit);
        $this->db->bind(':offset', $offset);
        
        return $this->db->resultSet();
    }

    /**
     * Update product status based on stock level
     */
    public function updateStockStatus($id) {
        $product = $this->getById($id);
        
        if (!$product) {
            return false;
        }
        
        $newStatus = $product->status;
        
        if ($product->stock_quantity <= 0) {
            $newStatus = self::STATUS_OUT_OF_STOCK;
        } elseif ($product->status == self::STATUS_OUT_OF_STOCK && $product->stock_quantity > 0) {
            $newStatus = self::STATUS_ACTIVE;
        }
        
        if ($newStatus != $product->status) {
            $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $id);
            $this->db->bind(':status', $newStatus);
            
            return $this->db->execute();
        }
        
        return true;
    }
}
?>