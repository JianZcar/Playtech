<?php
// Database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../connection/connect.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_cart') {
        header('Content-Type: application/json');
        
        try {
            // You'll need to get the user_id from session or authentication
            $userId = $_SESSION['user_id'];
            
            $productId = $_POST['product_id'];
            $quantity = $_POST['quantity'] ?? 1;
            
            // Check if product exists
            $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                throw new Exception('Product not found');
            }
            

            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, date_added) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $productId, $quantity]);
        
            echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
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
            background: var(--dark-bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
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


        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
                padding: 15px;
            }
            
					/* Add to cart styles */
					.add-to-cart-btn {
							padding: 5px 10px;
							font-size: 0.85rem;
							transition: all 0.2s ease;
					}

					.quantity-input {
							max-width: 40px;
							text-align: center;
							background-color: var(--card-bg);
							color: var(--text);
							border-color: rgba(255,255,255,0.1);
					}

					.input-group-sm > .btn {
							padding: 0.25rem 0.5rem;
							font-size: 0.75rem;
					}

					.btn-outline-secondary {
							border-color: rgba(255,255,255,0.1);
							color: var(--muted);
					}

					.btn-outline-secondary:hover {
							background-color: rgba(255,255,255,0.05);
							color: var(--text);
					}

					.btn-success {
							background-color: var(--secondary);
							border: none;
					}

					.btn-success:hover {
							background-color: #157347;
					}
        }
    </style>
</head>
<body>
<?php include "../includes/header.php"; ?>
<div class="container-fluid">
    <!-- Products Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products</h5>
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
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){
		let addtocart = null;
    $(document).on('click', '.product-card', function() {
        const productId = $(this).data('id');
        currentProductId = productId;
        
        const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
        modal.show();
        
        // Load product details
        $.get(`<?= $_SERVER['PHP_SELF'] ?>?action=details&product_id=${productId}`, function(data) {
            if (data.status === 'success') {
                addtocart = productId;
                const p = data.product;
                const stats = data.stats;
                
								// In your product details modal content, add:
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
																</div>
														</div>
														
														<!-- Add to Cart in Details Modal -->
														<div class="d-flex align-items-center mt-4">
																<div class="input-group input-group-sm me-2" style="width: 100px;">
																    <button class="btn btn-outline-secondary minus-btn" type="button">-</button>
																    <input type="number" class="form-control text-center quantity-input" value="1" min="1" max="100">
																    <button class="btn btn-outline-secondary plus-btn" type="button">+</button>
																</div>
																<button class="btn btn-sm btn-primary add-to-cart-btn" data-product-id="${p.id}">Add to Cart</button>
														</div>
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
        addtocart = null;
    });
    
    // Add these event handlers to your $(function(){ ... });

		// Quantity controls
		$(document).on('click', '.plus-btn', function() {
				const input = $(this).siblings('.quantity-input');
				input.val(parseInt(input.val()) + 1);
		});

		$(document).on('click', '.minus-btn', function() {
				const input = $(this).siblings('.quantity-input');
				const value = parseInt(input.val());
				if (value > 1) {
				    input.val(value - 1);
				}
		});

		// Add to cart button
		$(document).on('click', '.add-to-cart-btn', function(e) {
				e.stopPropagation(); // Prevent triggering the product details modal
				const productId = addtocart;
				const quantity = $('#productDetailsContent').find('.quantity-input').val();
				console.log(quantity)
				
				$.post('', {
				    action: 'add_to_cart',
				    product_id: productId,
				    quantity: quantity
				}, function(response) {
				    if (response.status === 'success') {
				        // Show success feedback
				        const btn = $(e.target);
				        btn.html('<i class="bi bi-check"></i> Added');
				        btn.removeClass('btn-primary').addClass('btn-success');
				        
				        // Reset after 2 seconds
				        setTimeout(() => {
				            btn.html('Add to Cart');
				            btn.removeClass('btn-success').addClass('btn-primary');
				        }, 2000);
				    } else {
				        alert('Error: ' + response.message);
				    }
				}, 'json').fail((jqXHR, textStatus, errorThrown) => {
				    alert('Request failed:\n' + JSON.stringify({
				        status: textStatus,
				        error: errorThrown,
				        responseText: jqXHR.responseText
				    }, null, 2));
				});
		});
    

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
});
</script>
</body>
</html>
