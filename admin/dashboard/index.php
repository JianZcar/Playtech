<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #121212, #3a3a3a);
            color: #f0f0f0;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #1e1e1e;
            position: fixed;
            padding: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .stats-card {
            background: #2c2c2c;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }

        .product-card {
            background: #2c2c2c;
            border-radius: 10px;
            margin: 15px 0;
            padding: 15px;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .loading {
            display: none;
            position: absolute;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-primary mb-4">Admin Panel</h3>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="#dashboard" class="nav-link text-light">Dashboard</a></li>
            <li class="nav-item"><a href="#products" class="nav-link text-light">Products</a></li>
            <li class="nav-item"><a href="#orders" class="nav-link text-light">Orders</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Order Stats -->
        <div class="row" id="orderStats">
            <div class="col-md-3">
                <div class="stats-card">
                    <h5>Total Orders</h5>
                    <h2 id="totalOrders">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5>Pending</h5>
                    <h2 id="pendingOrders">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5>Delivering</h5>
                    <h2 id="deliveringOrders">0</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h5>Delivered</h5>
                    <h2 id="deliveredOrders">0</h2>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="mt-5">
            <div class="d-flex justify-content-between mb-4">
                <h3>Products</h3>
                <div>
                    <select id="categoryFilter" class="form-control bg-dark text-light" style="display: inline-block; width: auto;">
                        <option value="">All Categories</option>
                    </select>
                    <button class="btn btn-primary ml-2" onclick="showAddProductModal()">Add Product</button>
                </div>
            </div>
            
            <div id="productsGrid" class="row">
                <!-- Products will be loaded here -->
                <div class="loading">Loading products...</div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="productForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" class="form-control bg-darker" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control bg-darker" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" class="form-control bg-darker" name="price" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select class="form-control bg-darker" name="category_id" id="modalCategory" required></select>
                        </div>
                        <div class="form-group">
                            <label>Stock</label>
                            <input type="number" class="form-control bg-darker" name="stock" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" class="form-control-file" name="image" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Save Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initial load
        $(document).ready(function() {
            loadOrderStats();
            loadCategories();
            loadProducts();
        });

        async function loadOrderStats() {
            showLoading('#orderStats');
            try {
                const response = await fetch('/api/admin/orders/stats');
                const data = await response.json();
                
                document.getElementById('totalOrders').textContent = data.total;
                document.getElementById('pendingOrders').textContent = data.pending;
                document.getElementById('deliveringOrders').textContent = data.delivering;
                document.getElementById('deliveredOrders').textContent = data.delivered;
            } catch (error) {
                showModalMessage('Error loading stats', 'red');
            }
            hideLoading('#orderStats');
        }

        async function loadCategories() {
            try {
                const response = await fetch('/api/categories');
                const categories = await response.json();
                
                const filter = document.getElementById('categoryFilter');
                const modalSelect = document.getElementById('modalCategory');
                
                categories.forEach(cat => {
                    const option = `<option value="${cat.id}">${cat.name}</option>`;
                    filter.innerHTML += option;
                    modalSelect.innerHTML += option;
                });
            } catch (error) {
                showModalMessage('Error loading categories', 'red');
            }
        }

        async function loadProducts(categoryId = '') {
            showLoading('#productsGrid');
            try {
                const url = `/api/admin/products${categoryId ? `?category_id=${categoryId}` : ''}`;
                const response = await fetch(url);
                const products = await response.json();
                
                const grid = document.getElementById('productsGrid');
                grid.innerHTML = products.map(product => `
                    <div class="col-md-4">
                        <div class="product-card">
                            <img src="${product.image_url}" class="img-fluid mb-2" alt="${product.name}">
                            <h5>${product.name}</h5>
                            <p>$${product.price} | Stock: ${product.stock}</p>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm btn-info" onclick="editProduct(${product.id})">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                showModalMessage('Error loading products', 'red');
            }
            hideLoading('#productsGrid');
        }

        async function submitProductForm(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('productForm'));
            
            try {
                const response = await fetch('/api/admin/products', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    $('#productModal').modal('hide');
                    loadProducts();
                    showModalMessage('Product added successfully', 'green');
                } else {
                    showModalMessage('Error adding product', 'red');
                }
            } catch (error) {
                showModalMessage('Network error', 'red');
            }
        }

        function showAddProductModal() {
            document.getElementById('productForm').reset();
            $('#productModal').modal('show');
        }

        function showLoading(selector) {
            const container = document.querySelector(selector);
            container.querySelector('.loading').style.display = 'block';
        }

        function hideLoading(selector) {
            const container = document.querySelector(selector);
            container.querySelector('.loading').style.display = 'none';
        }

        // Event listeners
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            loadProducts(e.target.value);
        });

        document.getElementById('productForm').addEventListener('submit', submitProductForm);
    </script>
</body>
</html>
