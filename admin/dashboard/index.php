<?php
// Database connection
session_start();
if (!isset($_SESSION['is_admin'])) {
    header("Location: ../../dashboard");
    exit;
}
include "../../connection/connect.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Handle AJAX POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add_category') {
                $stmt = $conn->prepare("INSERT INTO categories (name, description, date_created) VALUES (?, ?, CURDATE())");
                $stmt->execute([$_POST['name'], $_POST['description']]);
                echo json_encode(['status' => 'success', 'message' => 'Category added']);
                exit;
            }

            if ($_POST['action'] === 'add_product') {
                if (!isset($_POST['image_base64'], $_POST['image_type'])) {
                    throw new Exception('Missing image data');
                }

                $imageData = base64_decode($_POST['image_base64']);

                if ($imageData === false) {
                    throw new Exception('Invalid base64 image');
                }

                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, stock, image, date_added) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $_POST['category_id'],
                    $_POST['stock'],
                    $imageData
                ]);

                echo json_encode(['status' => 'success', 'message' => 'Product added']);
                exit;
            }
            if ($_POST['action'] === 'delete_product') {
                try {
                    $productId = $_POST['product_id'];
                    
                    // First check if the product exists
                    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
                    $stmt->execute([$productId]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Product not found');
                    }
                    
                    // Begin transaction for multiple operations
                    $conn->beginTransaction();
                    
                    try {
                        // Delete from cart first (due to foreign key constraint)
                        $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
                        $stmt->execute([$productId]);
                        
                        // Delete from order_items
                        $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
                        $stmt->execute([$productId]);
                        
                        // Delete inventory logs
                        $stmt = $conn->prepare("DELETE FROM inventory_logs WHERE product_id = ?");
                        $stmt->execute([$productId]);
                        
                        // Finally delete the product
                        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                        $stmt->execute([$productId]);
                        
                        $conn->commit();
                        
                        echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully']);
                        exit;
                    } catch (Exception $e) {
                        $conn->rollBack();
                        throw $e;
                    }
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    exit;
                }
            }

					if ($_POST['action'] === 'edit_product') {
							try {
									$productId = $_POST['product_id'];
									
									// First validate the product exists
									$stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
									$stmt->execute([$productId]);
									$currentProduct = $stmt->fetch();
									
									if (!$currentProduct) {
										  throw new Exception('Product not found');
									}

									// Handle image if provided
									$imageUpdate = '';
									$imageParams = [];
									if (isset($_POST['image_base64'])) {
										  $imageData = base64_decode($_POST['image_base64']);
										  if ($imageData === false) {
										      throw new Exception('Invalid base64 image');
										  }
										  $imageUpdate = ', image = ?';
										  $imageParams = [$imageData];
									}
									
									// Calculate stock changes
									$newStock = (int)$_POST['stock'];
									$stockAdjustment = (int)$_POST['stock_adjustment'];
									$currentStock = (int)$currentProduct['stock'];
									
									// Determine if we're doing a direct update or adjustment
									if ($stockAdjustment !== 0) {
										  // Using adjustment - calculate new stock and log
										  $newStock = $currentStock + $stockAdjustment;
										  $shouldLog = true;
									} else {
										  // Direct stock update - no logging unless stock actually changed
										  $shouldLog = ($newStock != $currentStock);
										  $stockAdjustment = $newStock - $currentStock;
									}
									
									// Update product
									$stmt = $conn->prepare("
										  UPDATE products 
										  SET name = ?, description = ?, price = ?, category_id = ?, stock = ? $imageUpdate
										  WHERE id = ?
									");
									
									$params = array_merge(
										  [$_POST['name'], $_POST['description'], $_POST['price'], $_POST['category_id'], $newStock],
										  $imageParams,
										  [$productId]
									);
									
									$stmt->execute($params);
									
									// Log inventory adjustment if needed
									if ($shouldLog && $stockAdjustment != 0) {
										  $action = $stockAdjustment > 0 ? 0 : 1; // 0 = add, 1 = remove
										  $stmt = $conn->prepare("
										      INSERT INTO inventory_logs 
										      (product_id, action, quantity, admin_id, date_logged) 
										      VALUES (?, ?, ?, ?, NOW())
										  ");
										  $stmt->execute([
										      $productId,
										      $action,
										      abs($stockAdjustment),
										      $_SESSION['user_id'] ?? 1
										  ]);
									}
									
									echo json_encode(['status' => 'success', 'message' => 'Product updated']);
									exit;
							} catch (Exception $e) {
									echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
									exit;
							}
					}

            throw new Exception('Invalid action');
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'products') {
    header('Content-Type: application/json');

    $stmt = $conn->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.date_added DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for JSON (encode images in base64)
    $data = [];
    foreach ($products as $product) {
        $data[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'], 2,
            'category' => $product['category'],
            'image' => !empty($product['image']) ? base64_encode($product['image']) : '',
        ];
    }
    echo json_encode(['status' => 'success', 'products' => $data]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['product_id'])) {
    header('Content-Type: application/json');
    
    try {
        $productId = $_GET['product_id'];
        
        // Fetch product details
        $stmt = $conn->prepare("
            SELECT p.*, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Fetch sales stats
        $stmt = $conn->prepare("
            SELECT 
                SUM(oi.quantity) AS total_sold,
                SUM(oi.quantity * oi.price) AS total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = ? AND o.status IN (2,3)  -- Only shipped/delivered orders
        ");
        $stmt->execute([$productId]);
        $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Fetch current cart quantity
        $stmt = $conn->prepare("SELECT SUM(quantity) AS in_carts FROM cart WHERE product_id = ?");
        $stmt->execute([$productId]);
        $cartData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Fetch inventory logs
        $stmt = $conn->prepare("
            SELECT il.*, a.name AS admin_name
            FROM inventory_logs il
            LEFT JOIN admins a ON il.admin_id = a.id
            WHERE il.product_id = ?
            ORDER BY il.date_logged DESC
            LIMIT 5
        ");
        $stmt->execute([$productId]);
        $inventoryLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare response
        $response = [
            'status' => 'success',
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'category' => $product['category_name'],
                'stock' => $product['stock'],
                'image' => !empty($product['image']) ? base64_encode($product['image']) : '',
            ],
            'stats' => [
                'total_sold' => $salesData['total_sold'] ?: 0,
                'total_revenue' => number_format($salesData['total_revenue'] ?: 0, 2),
                'in_carts' => $cartData['in_carts'] ?: 0
            ],
            'inventory_logs' => array_map(function($log) {
                return [
                    'action' => match($log['action']) {
                        0 => 'Add',
                        1 => 'Remove',
                        2 => 'Adjust',
                        default => 'Unknown'
                    },
                    'quantity' => $log['quantity'],
                    'admin' => $log['admin_name'] ?: 'System',
                    'date' => date('M d, Y h:i A', strtotime($log['date_logged']))
                ];
            }, $inventoryLogs)
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch stats
$stats = [];
foreach (['users', 'products', 'orders', 'categories'] as $table) {
    $stmt = $conn->query("SELECT COUNT(*) AS count FROM $table");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[$table] = $row['count'];
}

// Fetch categories
$stmt = $conn->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --card-surface: #2c2c2c;
            --primary: #0dcaf0;
            --secondary: #198754;
            --text: #f0f0f0;
            --muted: #8ab8c9;
            --success: #3cd070;
        }
        
        body {
            background: linear-gradient(to right, #121212, #3a3a3a);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 30px;
            min-height: 100vh;
        }

        h5 {
            color: var(--text);
        }
        
        /* Minimalist cards */
        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .card-container {
            background-color: var(--card-bg);
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 16px 20px;
            font-weight: 600;
        }
        
        .stat-box {
            text-align: center;
            padding: 24px 15px;
        }
        
        .stat-box h4 {
            font-size: 2rem;
            font-weight: 300;
            color: var(--primary);
            margin-bottom: 0;
        }
        
        .stat-box p {
            font-size: 0.9rem;
            color: var(--muted);
            margin-top: 8px;
            letter-spacing: 0.5px;
        }
        
        /* Product cards */
        .product-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .product-card:hover {
            border-color: rgba(13, 202, 240, 0.2);
        }
        
        .product-img {
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-img {
            transform: scale(1.03);
        }
        
        .card-body {
            padding: 16px;
        }
        
        .card-body h6 {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .card-body small {
            color: var(--muted);
            font-size: 0.85rem;
        }
        
        .card-body span {
            font-weight: 500;
            font-size: 1.1rem;
            color: var(--success);
            display: block;
            margin-top: 8px;
        }
        
        .card-body p {
            font-size: 0.85rem;
            color: #b0b0b0;
            margin-top: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
        }
        
        /* Form elements */
        .form-control, .btn {
            border-radius: 6px;
        }
        
        .form-control {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text);
            padding: 10px 15px;
        }
        
        .form-control:focus {
            background-color: rgba(255,255,255,0.07);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.15);
        }
        
        .form-control::placeholder {
            color: #777;
        }
        
        .btn {
            padding: 10px 24px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #0ab0d8;
            transform: translateY(-2px);
        }
        
        /* Modal styles */
        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 16px 20px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .close {
            color: var(--text);
            opacity: 0.7;
        }
        
        .close:hover {
            opacity: 1;
        }
        
        /* Layout improvements */
        .container-fluid {
            max-width: 1400px;
        }
        
        .section-title {
            font-weight: 500;
            color: var(--text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        /* Loader */
        .loader {
            display: flex;
            justify-content: center;
            padding: 40px 0;
        }
        
        /* Utils */
        .text-right {
            text-align: right;
        }
        
        .mb-4 {
            margin-bottom: 1.8rem !important;
        }
        
        .mt-5 {
            margin-top: 3rem !important;
        }
        
        /* Minimalist grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
            padding: 20px;
        }

        /* Edit modal specific styles */
        #currentImageContainer {
            border: 1px dashed rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 6px;
            background: rgba(255,255,255,0.03);
            text-align: center;
        }

        #currentProductImage {
            max-width: 100%;
            margin: 0 auto;
        }

        .stock-adjustment-info {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
                padding: 15px;
            }
            
            .stat-box {
                padding: 16px 10px;
            }
            
            .stat-box h4 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<?php include "../includes/header.php"; ?>
<body>
<div class="container-fluid">
    <!-- Stats Section -->
    <div class="row mb-4">
        <div class="col-md-3 stat-box">
            <h4><?= $stats['users'] ?></h4>
            <p>Users</p>
        </div>
        <div class="col-md-3 stat-box">
            <h4><?= $stats['products'] ?></h4>
            <p>Products</p>
        </div>
        <div class="col-md-3 stat-box">
            <h4><?= $stats['orders'] ?></h4>
            <p>Orders</p>
        </div>
        <div class="col-md-3 stat-box">
            <h4><?= $stats['categories'] ?></h4>
            <p>Categories</p>
        </div>
    </div>

    <!-- Products Section -->
    <div class="card-container mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products</h5>
            <div>
                <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-tag"></i> Add Category
                </button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle"></i> Add Product
                </button>
            </div>
        </div>
        <div id="productsContainer" class="products-grid">
            <!-- Product cards will be loaded here -->
            <div class="loader">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productDetailsContent">
                    <!-- Content will be loaded by AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editProductBtn">Edit</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" class="modal-content">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Electronics" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Short description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProductForm" class="modal-content">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="editProductId">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" id="editProductName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="editProductCategory" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editProductDescription" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" step="0.01" min="0" name="price" id="editProductPrice" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock</label>
                                <input type="number" min="0" step="1" name="stock" id="editProductStock" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Adjust Stock (optional)</label>
                                <input type="number" name="stock_adjustment" id="editProductStockAdjustment" class="form-control" placeholder="+/- quantity">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                            <div class="mt-2" id="currentImageContainer">
                                <img src="" id="currentProductImage" style="max-height: 150px; display: none;" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger me-auto" id="deleteProductBtn"><i class="bi bi-trash"></i>Delete</button>
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="productForm" class="modal-content">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Product Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" placeholder="Product description" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" step="1" min="0" name="price" class="form-control" placeholder="29.99" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock</label>
                                <input type="number" min="0" step="1" name="stock" class="form-control" placeholder="50" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){
    $('#openAddProduct').on('click', function () {
        $('#addProductModal').modal('show');
    });

    $('#openAddCategory').on('click', function () {  
        $('#addCategoryModal').modal('show');
    });
    // Add these variables at the top of your script
    let productToDelete = null;

    // Add this to your existing JavaScript (after the edit functionality)
    function setupDeleteButton() {
        // Handle delete button click
        $(document).on('click', '#deleteProductBtn', function() {
            $('#productDetailsModal').modal('hide');
            $('#deleteConfirmModal').modal('show');
        });
        
        // Handle confirm delete button
        $('#confirmDeleteBtn').click(function() {
            if (productToDelete) {
                $.post('', {
                    action: 'delete_product',
                    product_id: productToDelete
                }, function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        fetchProducts(); // Refresh the product list
                    } else {
                        alert('Error: ' + response.message);
                    }
                    $('#deleteConfirmModal').modal('hide');
                    productToDelete = null;
                }, 'json');
            }
        });
    }
    setupDeleteButton();
    $(document).on('click', '.product-card', function() {
        const productId = $(this).data('id');
        currentProductId = productId;
        
        const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
        modal.show();
        
        // Load product details
        $.get(`<?= $_SERVER['PHP_SELF'] ?>?action=details&product_id=${productId}`, function(data) {
            if (data.status === 'success') {
                productToDelete = productId;
                const p = data.product;
                const stats = data.stats;
                
                let logsHtml = '';
                data.inventory_logs.forEach(log => {
                    logsHtml += `<tr>
                        <td>${log.action}</td>
                        <td>${log.quantity}</td>
                        <td>${log.admin}</td>
                        <td>${log.date}</td>
                    </tr>`;
                });
                
                const html = `
                    <div class="row">
                        <div class="col-md-4">
                            <img src="data:image/jpeg;base64,${p.image}" alt="${p.name}" class="img-fluid rounded">
                        </div>
                        <div class="col-md-8">
                            <h3>${p.name}</h3>
                            <p>${p.category}</p>
                            <p>${p.description}</p>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>₱${p.price}</h5>
                                    <p>Stock: ${p.stock}</p>
                                </div>
                                <div>
                                    <p>Total Sold: ${stats.total_sold}</p>
                                    <p>Revenue: ₱${stats.total_revenue}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5>Inventory Logs</h5>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Qty</th>
                                        <th>Admin</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logsHtml}
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Quick Stats</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Currently in carts
                                    <span class="badge bg-primary rounded-pill">${stats.in_carts}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Available stock
                                    <span class="badge ${p.stock > 10 ? 'bg-success' : 'bg-warning'} rounded-pill">${p.stock}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total revenue
                                    <span class="badge bg-info rounded-pill">₱${stats.total_revenue}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                `;
                $('#productDetailsContent').html(html);
            } else {
                $('#productDetailsContent').html(`<p class="text-danger">${data.message}</p>`);
            }
        });
    });

    $('#productDetailsModal').on('hidden.bs.modal', function () {
        $('#productDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
        currentProductId = null;
    });
    $('#editProductBtn').click(function() {
        if (currentProductId) {
            // First fetch the product details to populate the form
            $.get(`<?= $_SERVER['PHP_SELF'] ?>?action=details&product_id=${currentProductId}`, function(data) {
                if (data.status === 'success') {
                    const p = data.product;
                    
                    // Populate the edit form
                    $('#editProductId').val(p.id);
                    $('#editProductName').val(p.name);
                    $('#editProductDescription').val(p.description);
                    $('#editProductPrice').val(p.price.replace(/[^0-9.]/g, ''));
                    $('#editProductStock').val(p.stock);
                    $('#editProductCategory').val(p.category_id);
                    
                    // Show current image
                    if (p.image) {
                        $('#currentProductImage').attr('src', `data:image/jpeg;base64,${p.image}`).show();
                    }
                    
                    // Close details modal and open edit modal
                    $('#productDetailsModal').modal('hide');
                    $('#editProductModal').modal('show');
                } else {
                    alert('Failed to load product details: ' + data.message);
                }
            });
        }
    });

		$("#editProductForm").submit(function(e){
				e.preventDefault();

				var form = this;
				var productId = form.product_id.value;
				
				// Prepare base data
				var postData = {
				    action: 'edit_product',
				    product_id: productId,
				    name: form.name.value,
				    description: form.description.value,
				    price: form.price.value,
				    category_id: form.category_id.value,
				    stock: form.stock.value,  // Always send current stock value
				    stock_adjustment: form.stock_adjustment.value || 0  // Send adjustment if any
				};
				
				// Handle image if changed
				var file = form.image.files[0];
				if (file) {
				    var reader = new FileReader();
				    reader.onload = function(evt) {
				        var base64 = evt.target.result.split(',')[1];
				        postData.image_base64 = base64;
				        postData.image_type = file.type;
				        submitEditForm(postData);
				    };
				    reader.readAsDataURL(file);
				} else {
				    submitEditForm(postData);
				}
		});

	function submitEditForm(data) {
		  $.post('', data, function(response){
		      if(response.status === 'success'){
		          alert(response.message);
		          $('#editProductModal').modal('hide');
		          fetchProducts(); // Refresh the products list
		      } else {
		          alert("Error: " + response.message);
		      }
		  }, 'json');
	}

    const productsContainer = $('#productsContainer');

    // Function to create product cards HTML from product array
    function renderProducts(products) {
        if (products.length === 0) {
            return `<p class="text-center text-muted">No products found.</p>`;
        }
        return products.map(p => `
           <div class="product-card card" data-id="${p.id}">
                <img src="data:image/jpeg;base64,${p.image}" alt="${p.name}" class="product-img card-img-top">
                <div class="card-body">
                    <h6>${p.name}</h6>
                    <small>${p.category}</small>
                    <p>${p.description}</p>
                    <span>₱${p.price}</span>
                </div>
            </div>
        `).join('');
    }

    // Fetch products and update container
    function fetchProducts() {
        $.get('<?= $_SERVER['PHP_SELF'] ?>?fetch=products', function(res) {
            if (res.status === 'success') {
                productsContainer.html(renderProducts(res.products));
            } else {
                productsContainer.html('<p class="text-danger text-center">Failed to load products.</p>');
            }
        }, 'json').fail(() => {
            productsContainer.html('<p class="text-danger text-center">Failed to load products (network error).</p>');
        });
    }

    // Initial fetch
    fetchProducts();

    // Refresh every 10 seconds
    setInterval(fetchProducts, 60000);
    // Add Category form submit
    $("#categoryForm").submit(function(e){
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('', formData, function(data){
            if(data.status === 'success'){
                alert(data.message);
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        }, 'json');
    });

    // Add Product form submit with image as base64
    $("#productForm").submit(function(e){
        e.preventDefault();

        var form = this;
        var file = form.image.files[0];
        if (!file) {
            alert("Please select an image.");
            return;
        }

        var reader = new FileReader();
        reader.onload = function(evt) {
            var base64 = evt.target.result.split(',')[1]; // remove "data:image/xxx;base64,"
            var postData = {
                action: 'add_product',
                name: form.name.value,
                description: form.description.value,
                price: form.price.value,
                stock: form.stock.value,
                category_id: form.category_id.value,
                image_base64: base64,
                image_type: file.type
            };

            $.post('', postData, function(data){
                if(data.status === 'success'){
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            }, 'json');
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>
