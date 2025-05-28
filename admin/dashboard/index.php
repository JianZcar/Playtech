<?php
// Database connection
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

            throw new Exception('Invalid action');
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

// Fetch stats
$stats = [];
foreach (['users', 'products', 'orders', 'categories'] as $table) {
    $stmt = $conn->query("SELECT COUNT(*) AS count FROM $table");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[$table] = $row['count'];
}

// Fetch products
$stmt = $conn->query("SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$stmt = $conn->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
    }
    .card-header, .card-body {
        background-color: #2c2c2c;
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
        height: 100px;
        object-fit: cover;
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

    <!-- Products Table -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Products</h5></div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card text-center">
                            <img src="data:image/jpeg;base64,<?= base64_encode($product['image']) ?>" class="card-img-top product-img" alt="Product Image"/>
                            <div class="card-body">
                                <h6><?= htmlspecialchars($product['name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($product['category']) ?></small><br/>
                                <span>₱<?= number_format($product['price'], 2) ?></span>
                                <p class="text-muted small mt-2"><?= htmlspecialchars($product['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Category -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Add Category</h5></div>
        <div class="card-body">
            <form id="categoryForm">
                <input type="hidden" name="action" value="add_category">
                <div class="form-row">
                    <div class="col">
                        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                    </div>
                    <div class="col">
                        <input type="text" name="description" class="form-control" placeholder="Description">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Product -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Add Product</h5></div>
        <div class="card-body">
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <div class="form-row mb-2">
                    <div class="col"><input type="text" name="name" class="form-control" placeholder="Product Name" required></div>
                    <div class="col"><input type="text" name="description" class="form-control" placeholder="Description"></div>
                </div>
                <div class="form-row mb-2">
                    <div class="col"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required></div>
                    <div class="col"><input type="number" name="stock" class="form-control" placeholder="Stock" required></div>
                    <div class="col">
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col"><input type="file" name="image" class="form-control-file" required></div>
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$('#categoryForm').submit(function(e) {
    e.preventDefault();
    $.post('', $(this).serialize(), function(res) {
        alert(res.message);
        if(res.status === 'success') location.reload();
    }, 'json');
});

$("#productForm").on("submit", function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  const file = $("input[name='image']")[0].files[0];

  if (!file) return alert("Select an image!");

  const reader = new FileReader();
  reader.onload = function() {
    const base64 = reader.result.split(',')[1];
    const imageType = file.type;

    formData.append("action", "add_product");
    formData.append("image_base64", base64);
    formData.append("image_type", imageType);

    // Remove original file input so it won't conflict
    formData.delete("image");

    $.ajax({
      type: "POST",
      url: "./",
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        alert(response.message);
      },
      error: function() {
        alert("Upload failed.");
      }
    });
  };

  reader.readAsDataURL(file);
});
</script>
</body>
</html>
