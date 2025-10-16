<?php
// admin/orders/export_orders.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied. Admin privileges required.');
}

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Database connection failed');
}

// Validate export request
if (!isset($_GET['export']) || !in_array($_GET['export'], ['csv', 'excel'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid export type');
}

$export_type = $_GET['export'];

// Get filter parameters (same as in list.php)
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

// Build query for orders with optional filters
$query = "SELECT 
            o.id,
            o.order_number,
            o.status,
            o.total_amount,
            o.payment_method,
            o.payment_status,
            o.shipping_cost,
            o.tax_amount,
            o.discount_amount,
            o.coupon_code,
            o.created_at,
            o.updated_at,
            o.estimated_delivery,
            u.first_name,
            u.last_name,
            u.email,
            u.phone
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status) && $status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($dateFrom)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $items_stmt = $pdo->prepare("
            SELECT 
                oi.product_name,
                oi.product_sku,
                oi.product_price,
                oi.quantity,
                oi.subtotal
            FROM order_items oi 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order['id']]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Export orders query error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Unable to fetch orders data for export.');
}

// Set headers based on export type
if ($export_type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 compatibility with Excel
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // CSV headers
    $headers = [
        'Order Number',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'Order Date',
        'Status',
        'Payment Method',
        'Payment Status',
        'Subtotal',
        'Shipping Cost',
        'Tax Amount',
        'Discount Amount',
        'Coupon Code',
        'Total Amount',
        'Estimated Delivery',
        'Products',
        'Last Updated'
    ];
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($orders as $order) {
        // Format products information
        $products = [];
        foreach ($order['items'] as $item) {
            $products[] = $item['product_name'] . ' (Qty: ' . $item['quantity'] . ' @ R' . $item['product_price'] . ')';
        }
        $products_str = implode('; ', $products);
        
        $row = [
            $order['order_number'],
            $order['first_name'] . ' ' . $order['last_name'],
            $order['email'] ?? 'N/A',
            $order['phone'] ?? 'N/A',
            date('Y-m-d H:i:s', strtotime($order['created_at'])),
            ucfirst($order['status']),
            ucfirst(str_replace('_', ' ', $order['payment_method'])),
            ucfirst($order['payment_status']),
            number_format($order['total_amount'] - $order['shipping_cost'] - $order['tax_amount'] + $order['discount_amount'], 2),
            number_format($order['shipping_cost'], 2),
            number_format($order['tax_amount'], 2),
            number_format($order['discount_amount'], 2),
            $order['coupon_code'] ?? 'N/A',
            number_format($order['total_amount'], 2),
            $order['estimated_delivery'] ? date('Y-m-d', strtotime($order['estimated_delivery'])) : 'N/A',
            $products_str,
            date('Y-m-d H:i:s', strtotime($order['updated_at']))
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} elseif ($export_type === 'excel') {
    // For Excel, we'll create an HTML table that Excel can open
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d_H-i-s') . '.xls"');
    
    // Start HTML output
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Orders Export</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <h2>HomewareOnTap - Orders Export</h2>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Customer Phone</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Subtotal</th>
                    <th>Shipping Cost</th>
                    <th>Tax Amount</th>
                    <th>Discount Amount</th>
                    <th>Coupon Code</th>
                    <th>Total Amount</th>
                    <th>Estimated Delivery</th>
                    <th>Products</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>';
    
    // Add data rows
    foreach ($orders as $order) {
        // Format products information
        $products = [];
        foreach ($order['items'] as $item) {
            $products[] = $item['product_name'] . ' (Qty: ' . $item['quantity'] . ' @ R' . $item['product_price'] . ')';
        }
        $products_str = implode('; ', $products);
        
        echo '<tr>
                <td>' . htmlspecialchars($order['order_number']) . '</td>
                <td>' . htmlspecialchars(($order['first_name'] . ' ' . $order['last_name'])) . '</td>
                <td>' . htmlspecialchars($order['email'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($order['phone'] ?? 'N/A') . '</td>
                <td>' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '</td>
                <td>' . ucfirst($order['status']) . '</td>
                <td>' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</td>
                <td>' . ucfirst($order['payment_status']) . '</td>
                <td>R ' . number_format($order['total_amount'] - $order['shipping_cost'] - $order['tax_amount'] + $order['discount_amount'], 2) . '</td>
                <td>R ' . number_format($order['shipping_cost'], 2) . '</td>
                <td>R ' . number_format($order['tax_amount'], 2) . '</td>
                <td>R ' . number_format($order['discount_amount'], 2) . '</td>
                <td>' . htmlspecialchars($order['coupon_code'] ?? 'N/A') . '</td>
                <td>R ' . number_format($order['total_amount'], 2) . '</td>
                <td>' . ($order['estimated_delivery'] ? date('Y-m-d', strtotime($order['estimated_delivery'])) : 'N/A') . '</td>
                <td>' . htmlspecialchars($products_str) . '</td>
                <td>' . date('Y-m-d H:i:s', strtotime($order['updated_at'])) . '</td>
            </tr>';
    }
    
    echo '</tbody>
        </table>
    </body>
    </html>';
}

// Log the export activity
logAdminActivity($_SESSION['user_id'], 'export_orders', "Exported orders as $export_type format");

exit();
?>