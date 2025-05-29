<?php
session_start();
if (!isset($_SESSION['is_admin'])) {
    header("Location: ../../dashboard");
    exit;
}
include "../../connection/connect.php";

// Handle AJAX requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $date_created = date('Y-m-d');

            if (!$name) {
                throw new Exception("Name is required");
            }

            $sql = "INSERT INTO categories (name, description, date_created) VALUES (:name, :description, :date_created)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':date_created' => $date_created,
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Category created']);
            exit();
        }

        if ($action === 'update') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);

            if (!$name) {
                throw new Exception("Name is required");
            }

            $sql = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':id' => $id,
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Category updated']);
            exit();
        }

        if ($action === 'delete') {
            $id = intval($_POST['id']);

            $sql = "DELETE FROM categories WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'Category deleted']);
            exit();
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// Fetch all categories for display
$sql = "SELECT * FROM categories ORDER BY id DESC";
$stmt = $conn->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Category Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background: linear-gradient(to right, #121212, #3a3a3a);
      color: #eee;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 30px;
    }
    .table thead th {
      color: #0dcaf0;
    }
    .btn-primary {
      background: linear-gradient(to right, #0dcaf0, #198754);
      border: none;
    }
    .btn-primary:hover {
      background: linear-gradient(to right, #198754, #0dcaf0);
    }
    .modal-content {
      background-color: #2c2c2c;
      color: #eee;
      border: 1px solid #444;
    }
    .form-control {
      background-color: #3a3a3a;
      color: #eee;
      border: 1px solid #555;
      border-radius: 30px;
      padding: 10px 16px;
      font-size: 15px;
    }
    .form-control::placeholder {
      color: #aaa;
    }
    .is-invalid {
      border-color: #dc3545 !important;
    }
    .invalid-feedback {
      color: #dc3545;
      font-size: 0.85em;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<?php include "../includes/header.php"; ?>
<div class="container">
  <h2 class="mb-4">Category Management</h2>

  <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#categoryModal" id="btnAddCategory">Add New Category</button>

  <table class="table table-dark table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Date Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="categoryTableBody">
      <?php foreach ($categories as $cat): ?>
        <tr data-id="<?= htmlspecialchars($cat['id']) ?>">
          <td><?= htmlspecialchars($cat['id']) ?></td>
          <td class="cat-name"><?= htmlspecialchars($cat['name']) ?></td>
          <td class="cat-desc"><?= htmlspecialchars($cat['description']) ?></td>
          <td><?= htmlspecialchars($cat['date_created']) ?></td>
          <td>
            <button class="btn btn-sm btn-info btn-edit">Edit</button>
            <button class="btn btn-sm btn-danger btn-delete">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="categoryForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Category</h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="categoryId" name="id" />
          <input type="hidden" name="action" id="formAction" value="create" />
          <div class="form-group">
            <label for="categoryName">Name</label>
            <input
              type="text"
              class="form-control"
              id="categoryName"
              name="name"
              placeholder="Category name"
              required
            />
            <div class="invalid-feedback"></div>
          </div>
          <div class="form-group">
            <label for="categoryDescription">Description</label>
            <textarea
              class="form-control"
              id="categoryDescription"
              name="description"
              rows="3"
              placeholder="Category description"
            ></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4" id="responseMessage" style="font-size: 1.1rem;"></div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
  // Open Add Category modal
  $('#btnAddCategory').click(function() {
    resetForm();
    $('#modalTitle').text('Add Category');
    $('#formAction').val('create');
    $('#categoryModal').modal('show');
  });

  // Open Edit modal and populate form
  $('.btn-edit').click(function() {
    resetForm();
    const row = $(this).closest('tr');
    const id = row.data('id');
    const name = row.find('.cat-name').text();
    const description = row.find('.cat-desc').text();

    $('#categoryId').val(id);
    $('#categoryName').val(name);
    $('#categoryDescription').val(description);
    $('#formAction').val('update');
    $('#modalTitle').text('Edit Category');
    $('#categoryModal').modal('show');
  });

  // Delete category
  $('.btn-delete').click(function() {
    if (!confirm('Are you sure you want to delete this category?')) return;

    const row = $(this).closest('tr');
    const id = row.data('id');

    $.post('', { action: 'delete', id }, function(response) {
      if (response.status === 'success') {
        showResponse(response.message, 'green');
        row.remove();
      } else {
        showResponse(response.message, 'red');
      }
    }, 'json').fail(function() {
      showResponse('Failed to delete category', 'red');
    });
  });

  // Form validation helper
  function validateForm() {
    let valid = true;
    const nameInput = $('#categoryName');
    if (!nameInput.val().trim()) {
      nameInput.addClass('is-invalid');
      nameInput.next('.invalid-feedback').text('Name is required');
      valid = false;
    } else {
      nameInput.removeClass('is-invalid');
      nameInput.next('.invalid-feedback').text('');
    }
    return valid;
  }

  // Reset form and validation
  function resetForm() {
    $('#categoryForm')[0].reset();
    $('#categoryId').val('');
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');
  }

  // Handle form submit
  $('#categoryForm').submit(function(e) {
    e.preventDefault();
    if (!validateForm()) return;

    const formData = $(this).serialize();

    $.post('', formData, function(response) {
      if (response.status === 'success') {
        showResponse(response.message, 'green');
        $('#categoryModal').modal('hide');
        // Reload page or update table dynamically
        location.reload();
      } else {
        showResponse(response.message, 'red');
      }
    }, 'json').fail(function() {
      showResponse('An error occurred', 'red');
    });
  });

  // Show response modal
  function showResponse(message, color) {
    const $modal = $('#responseModal');
    const $msg = $('#responseMessage');
    $msg.text(message).css('color', color);
    $modal.modal('show');
    setTimeout(() => $modal.modal('hide'), 3000);
  }
});
</script>

</body>
</html>

