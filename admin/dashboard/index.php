<?php
// Database connection
include "../../connection/connect.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle AJAX POSTs for forms and GET for products fetch
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

            throw new Exception('Invalid action');
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

// Handle AJAX GET for products fetch (for refreshing product list)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'products') {
    header('Content-Type: application/json');

    $stmt = $conn->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.date_added DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for JSON (encode images in base64)
    $data = [];
    foreach ($products as $product) {
        $data[] = [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['name']),
            'description' => htmlspecialchars($product['description']),
            'price' => number_format($product['price'], 2),
            'category' => htmlspecialchars($product['category']),
            'image' => !empty($product['image']) ? base64_encode($product['image']) : '',
        ];
    }
    echo json_encode(['status' => 'success', 'products' => $data]);
    exit;
}

// Fetch stats for dashboard counters
$stats = [];
foreach (['users', 'products', 'orders', 'categories'] as $table) {
    $stmt = $conn->query("SELECT COUNT(*) AS count FROM $table");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[$table] = $row['count'];
}

// Fetch categories (for product add modal)
$stmt = $conn->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body {
        background: linear-gradient(to right, #121212, #3a3a3a);
        color: #f0f0f0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 40px;
    }
    .card {
        background-color: #1e1e1e;
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.4);
        transition: box-shadow 0.3s ease;
    }
    .card-header, .card-body {
        background-color: #2c2c2c;
    }
    .card:hover > .product-img {
      transform: scale(1.05);
      box-shadow: 0 4px 10px rgba(13, 202, 240, 0.6);
    }
    .card-body {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 160px;
    }
    .card-body h6 {
      margin-bottom: 5px;
      font-weight: 600;
      color: #0dcaf0;
    }
    .card-body small {
      color: #8ab8c9;
    }
    .card-body span {
      font-weight: bold;
      font-size: 1.1rem;
      color: #3cd070;
    }
    .card-body p {
      flex-grow: 1;
      font-size: 0.85rem;
      color: #b0b0b0;
      margin-top: 10px;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .form-control, .btn {
        border-radius: 30px;
    }
    .btn-primary {
        background: linear-gradient(to right, #0dcaf0, #198754);
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(to right, #198754, #0dcaf0);
    }
    .form-control {
        background-color: #3a3a3a;
        border: 1px solid #555;
        color: #f0f0f0;
    }
    .form-control::placeholder {
        color: #aaa;
    }
    .stat-box {
        text-align: center;
        padding: 20px;
    }
    .stat-box h4 {
        font-size: 2rem;
        color: #0dcaf0;
    }
    .stat-box p {
        font-size: 1rem;
        color: #ccc;
    }
    .product-img {
      height: 180px;
      object-fit: cover;
      border-radius: 10px;
      transition: transform 0.3s ease;
    }
    /* Modal styles override for dark theme */
    .modal-content {
      background-color: #2c2c2c;
      color: #f0f0f0;
      border-radius: 15px;
      border: none;
    }
    .modal-header {
      border-bottom: none;
    }
    .close {
      color: #f0f0f0;
      opacity: 1;
      font-size: 1.5rem;
    }
    /* Loader spinner */
    .loader {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 0;
      color: #0dcaf0;
    }
  </style>
</head>
<body>
<div class="container-fluid">
    <div class="row text-center mb-4">
        <div class="col-md-3 stat-box"><h4><?= $stats['users'] ?></h4><p>Users</p></div>
        <div class="col-md-3 stat-box"><h4><?= $stats['products'] ?></h4><p>Products</p></div>
        <div class="col-md-3 stat-box"><h4><?= $stats['orders'] ?></h4><p>Orders</p></div>
        <div class="col-md-3 stat-box"><h4><?= $stats['categories'] ?></h4><p>Categories</p></div>
    </div>

    <!-- Products Table Container -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Products</h5></div>
        <div id="productsContainer">
          <!-- Loader initially -->
          <div class="loader">
            <div class="spinner-border text-info" role="status"><span class="sr-only">Loading...</span></div>
          </div>
        </div>
    </div>

    <!-- Buttons to Open Modals -->
    <div class="mb-4 text-right">
      <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addCategoryModal">Add Category</button>
      <button class="btn btn-primary" data-toggle="modal" data-target="#addProductModal">Add Product</button>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="categoryForm" class="modal-content">
          <input type="hidden" name="action" value="add_category">
          <div class="modal-header">
            <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <input type="text" name="name" class="form-control" placeholder="Category Name" required>
            </div>
            <div class="form-group">
              <input type="text" name="description" class="form-control" placeholder="Description">
            </div>
          </div>
          <div class="modal-footer">
            <button id="categorySubmitBtn" class="btn btn-primary" type="submit">Add</button>
            <div id="categoryLoading" class="spinner-border text-info" role="status" style="display:none; width: 1.5rem; height: 1.5rem;">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form id="productForm" enctype="multipart/form-data" class="modal-content">
          <input type="hidden" name="action" value="add_product">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-row mb-2">
              <div class="col">
                <input type="text" name="name" class="form-control" placeholder="Product Name" required>
              </div>
              <div class="col">
                <input type="text" name="description" class="form-control" placeholder="Description">
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col">
                <input type="number" name="price" class="form-control" placeholder="Price" step="0.01" min="0" required>
              </div>
              <div class="col">
                <select name="category_id" class="form-control" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col">
                <input type="number" name="stock" class="form-control" placeholder="Stock" min="0" required>
              </div>
            </div>
            <div class="form-group">
              <input id="imageInput" type="file" accept="image/*" class="form-control-file" required>
            </div>
          </div>
          <div class="modal-footer">
            <button id="productSubmitBtn" class="btn btn-primary" type="submit">Add Product</button>
            <div id="productLoading" class="spinner-border text-info" role="status" style="display:none; width: 1.5rem; height: 1.5rem;">
              <span class="sr-only">Loading...</span>
            </div>
          </div>
        </form>
      </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    const productsContainer = $('#productsContainer');

    // Function to create product cards HTML from product array
    function renderProducts(products) {
        if (products.length === 0) {
            return `<p class="text-center text-muted">No products found.</p>`;
        }
        return products.map(p => `
            <div class="card mb-3">
                <div class="card-body d-flex">
                    <img src="data:image/jpeg;base64,${p.image}" alt="${p.name}" class="product-img mr-3" style="width: 180px; height: 180px;">
                    <div class="flex-grow-1">
                        <h6>${p.name}</h6>
                        <small>${p.category}</small>
                        <p>${p.description}</p>
                        <span>$${p.price}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Fetch products and update container
    function fetchProducts() {
        productsContainer.html(`
          <div class="loader">
            <div class="spinner-border text-info" role="status"><span class="sr-only">Loading...</span></div>
          </div>
        `);
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
    setInterval(fetchProducts, 10000);

    // Add Category form submission via AJAX
    $('#categoryForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        $('#categorySubmitBtn').hide();
        $('#categoryLoading').show();

        $.post('<?= $_SERVER['PHP_SELF'] ?>', form.serialize(), function(res) {
            if (res.status === 'success') {
                form[0].reset();
                $('#addCategoryModal').modal('hide');
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json').fail(() => alert('Network error.')).always(() => {
            $('#categoryLoading').hide();
            $('#categorySubmitBtn').show();
        });
    });

    // Add Product form submission via AJAX with base64 image
    $('#productForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const fileInput = document.getElementById('imageInput');
        const file = fileInput.files[0];
        if (!file) {
            alert('Please select an image.');
            return;
        }

        $('#productSubmitBtn').hide();
        $('#productLoading').show();

        const reader = new FileReader();
        reader.onload = function(event) {
            // event.target.result is dataURL: "data:image/png;base64,..."
            const base64Data = event.target.result.split(',')[1];
            const imageType = file.type;

            const data = form.serializeArray();
            data.push({name: 'image_base64', value: base64Data});
            data.push({name: 'image_type', value: imageType});

            $.post('<?= $_SERVER['PHP_SELF'] ?>', $.param(data), function(res) {
                if (res.status === 'success') {
                    form[0].reset();
                    $('#addProductModal').modal('hide');
                    fetchProducts(); // Refresh products immediately
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json').fail(() => alert('Network error.')).always(() => {
                $('#productLoading').hide();
                $('#productSubmitBtn').show();
            });
        };
        reader.readAsDataURL(file);
    });

    // Clear forms & hide loader when modal closed
    $('#addCategoryModal, #addProductModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#categoryLoading, #productLoading').hide();
        $('#categorySubmitBtn, #productSubmitBtn').show();
    });
});
</script>
</body>
</html>
