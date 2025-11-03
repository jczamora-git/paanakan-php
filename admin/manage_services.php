<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $service_name = $_POST['service_name'];
                $category = $_POST['category'];
                $price = $_POST['price'];
                
                $stmt = $pdo->prepare("INSERT INTO medical_services (service_name, category, price) VALUES (?, ?, ?)");
                if ($stmt->execute([$service_name, $category, $price])) {
                    $_SESSION['message'] = "Service added successfully.";
                } else {
                    $_SESSION['error'] = "Failed to add service.";
                }
                break;

            case 'edit':
                $service_id = $_POST['service_id'];
                $service_name = $_POST['service_name'];
                $category = $_POST['category'];
                $price = $_POST['price'];
                
                $stmt = $pdo->prepare("UPDATE medical_services SET service_name = ?, category = ?, price = ? WHERE service_id = ?");
                if ($stmt->execute([$service_name, $category, $price, $service_id])) {
                    $_SESSION['message'] = "Service updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update service.";
                }
                break;

            case 'delete':
                $service_id = $_POST['service_id'];
                
                $stmt = $pdo->prepare("DELETE FROM medical_services WHERE service_id = ?");
                if ($stmt->execute([$service_id])) {
                    $_SESSION['message'] = "Service deleted successfully.";
                } else {
                    $_SESSION['error'] = "Failed to delete service.";
                }
                break;
        }
    }
    header("Location: manage_services.php");
    exit();
}

// Fetch all services
$stmt = $pdo->query("SELECT * FROM medical_services ORDER BY category, service_name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories
$categories = array_unique(array_column($services, 'category'));

// Get total count of services
$totalCount = count($services);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2E8B57;
            --primary-light: #3CB371;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --border-color: #eee;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .dashboard-main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 270px;
            transition: all 0.4s ease;
            background-color: #f8f9fa;
        }
        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }
        @media (max-width: 768px) {
            .dashboard-main-content {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar.collapsed ~ .dashboard-main-content {
                margin-left: 0;
                padding-left: 85px;
            }
        }
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table thead th {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
        }
        .table tbody tr:hover {
            background-color: rgba(46, 139, 87, 0.05);
        }
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .form-select, .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 8px 12px;
        }
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }
        .action-icon {
            background: none;
            border: none;
            padding: 0 4px;
            font-size: 1.1rem;
            line-height: 1;
            vertical-align: middle;
            transition: color 0.2s, box-shadow 0.2s;
            box-shadow: none;
            outline: none;
        }
        .action-icon:focus, .action-icon:hover {
            color: #17633c !important;
            background: none;
            box-shadow: none;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Manage Services
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-list me-2"></i>Total Services: <?= $totalCount ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fas fa-plus me-2"></i>Add New Service
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search services by name, category...">
                            <button class="btn btn-outline-success" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="priceFilter" class="form-select">
                            <option value="">All Prices</option>
                            <option value="0-500">₱0 - ₱500</option>
                            <option value="501-1000">₱501 - ₱1000</option>
                            <option value="1001-2000">₱1001 - ₱2000</option>
                            <option value="2001+">₱2001 and above</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="resetFilters" class="btn btn-secondary w-100" type="button">
                            <i class="fas fa-redo-alt me-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Services Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $index => $service): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($service['service_name']) ?></td>
                                    <td><?= htmlspecialchars($service['category']) ?></td>
                                    <td>₱<?= number_format($service['price'], 2) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-info btn-action edit-service" 
                                                    data-id="<?= $service['service_id'] ?>"
                                                    data-name="<?= htmlspecialchars($service['service_name']) ?>"
                                                    data-category="<?= htmlspecialchars($service['category']) ?>"
                                                    data-price="<?= $service['price'] ?>"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-action delete-service"
                                                    data-id="<?= $service['service_id'] ?>"
                                                    data-name="<?= htmlspecialchars($service['service_name']) ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No services found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="manage_services.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" name="service_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="manage_services.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="service_id" id="edit_service_id">
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" name="service_name" id="edit_service_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" id="edit_category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="manage_services.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="service_id" id="delete_service_id">
                        <p>Are you sure you want to delete the service "<span id="delete_service_name"></span>"?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const priceFilter = document.getElementById('priceFilter');
            const resetButton = document.getElementById('resetFilters');
            const table = document.querySelector('.table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedCategory = categoryFilter.value.toLowerCase();
                const selectedPriceRange = priceFilter.value;

                Array.from(rows).forEach(row => {
                    const serviceName = row.cells[1].textContent.toLowerCase();
                    const category = row.cells[2].textContent.toLowerCase();
                    const price = parseFloat(row.cells[3].textContent.replace('₱', '').replace(',', ''));

                    const matchesSearch = serviceName.includes(searchTerm) || category.includes(searchTerm);
                    const matchesCategory = !selectedCategory || category === selectedCategory;
                    
                    let matchesPrice = true;
                    if (selectedPriceRange) {
                        switch(selectedPriceRange) {
                            case '0-500':
                                matchesPrice = price >= 0 && price <= 500;
                                break;
                            case '501-1000':
                                matchesPrice = price > 500 && price <= 1000;
                                break;
                            case '1001-2000':
                                matchesPrice = price > 1000 && price <= 2000;
                                break;
                            case '2001+':
                                matchesPrice = price > 2000;
                                break;
                        }
                    }

                    row.style.display = (matchesSearch && matchesCategory && matchesPrice) ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterTable);
            categoryFilter.addEventListener('change', filterTable);
            priceFilter.addEventListener('change', filterTable);

            resetButton.addEventListener('click', function() {
                searchInput.value = '';
                categoryFilter.value = '';
                priceFilter.value = '';
                Array.from(rows).forEach(row => row.style.display = '');
            });

            // Edit Service
            document.querySelectorAll('.edit-service').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const category = this.dataset.category;
                    const price = this.dataset.price;

                    document.getElementById('edit_service_id').value = id;
                    document.getElementById('edit_service_name').value = name;
                    document.getElementById('edit_category').value = category;
                    document.getElementById('edit_price').value = price;

                    new bootstrap.Modal(document.getElementById('editServiceModal')).show();
                });
            });

            // Delete Service
            document.querySelectorAll('.delete-service').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    document.getElementById('delete_service_id').value = id;
                    document.getElementById('delete_service_name').textContent = name;

                    new bootstrap.Modal(document.getElementById('deleteServiceModal')).show();
                });
            });
        });
    </script>
</body>
</html> 