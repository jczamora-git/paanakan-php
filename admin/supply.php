<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();
if (!$pdo) {
    die("Database connection failed.");
}

// For INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    try {
        $itemName = $_POST['item_name'];
        $category = $_POST['category'];
        $quantity = $_POST['quantity'];
        $unit = $_POST['unit'];
        $supplier = $_POST['supplier'];
        $expiryDate = $_POST['expiry_date'] ?: NULL;
        $userId = $_SESSION['user_id'];

        // Get user details for logging
        $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([':user_id' => $userId]);
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFullName = $userDetails['first_name'] . ' ' . $userDetails['last_name'];
    
        $query = "INSERT INTO inventory (item_name, category, quantity, unit, supplier, expiry_date) 
                    VALUES (:item_name, :category, :quantity, :unit, :supplier, :expiry_date)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':item_name' => $itemName,
            ':category' => $category,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':supplier' => $supplier,
            ':expiry_date' => $expiryDate
        ]);

        // Check if row was inserted
        if ($stmt->rowCount() > 0) {
            // Log the activity with user's full name
            $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([
                ':user_id' => $userId,
                ':action' => "$userFullName added new inventory item: $itemName (Quantity: $quantity $unit)"
            ]);
            
            $_SESSION['message'] = "New inventory item added successfully.";
        } else {
            $_SESSION['error'] = "No rows were affected.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add inventory item: " . $e->getMessage();
    }
    header("Location: supply.php");
    exit();
}

// For UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    try {
        // Get user details for logging
        $userId = $_SESSION['user_id'];
        $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([':user_id' => $userId]);
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFullName = $userDetails['first_name'] . ' ' . $userDetails['last_name'];

        // Get old item details for comparison
        $oldItemQuery = "SELECT item_name, category, quantity, unit, supplier, price, status, expiry_date 
                        FROM inventory WHERE item_id = :item_id";
        $oldItemStmt = $pdo->prepare($oldItemQuery);
        $oldItemStmt->execute([':item_id' => $_POST['item_id']]);
        $oldItem = $oldItemStmt->fetch(PDO::FETCH_ASSOC);

        // Validate required fields
        if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || $_POST['quantity'] < 0) {
            throw new Exception("Quantity must be a valid non-negative number.");
        }

        $itemId = $_POST['item_id'];
        $itemName = $_POST['item_name'];
        $category = $_POST['category'];
        $quantity = $_POST['quantity'];
        $unit = $_POST['unit'];
        $supplier = $_POST['supplier'];
        $price = $_POST['price'];
        $status = $_POST['status'];
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;

        $query = "UPDATE inventory 
                  SET item_name = :item_name, 
                      category = :category, 
                      quantity = :quantity, 
                      unit = :unit, 
                      price = :price, 
                      supplier = :supplier, 
                      status = :status, 
                      expiry_date = :expiry_date 
                  WHERE item_id = :item_id";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':item_id' => $itemId,
            ':item_name' => $itemName,
            ':category' => $category,
            ':quantity' => $quantity,
            ':unit' => $unit,
            ':price' => $price,
            ':supplier' => $supplier,
            ':status' => $status,
            ':expiry_date' => $expiryDate
        ]);

        // Check if row was updated
        if ($stmt->rowCount() > 0) {
            // Build changes log
            $changes = [];
            if ($oldItem['item_name'] !== $itemName) $changes[] = "name from '{$oldItem['item_name']}' to '$itemName'";
            if ($oldItem['category'] !== $category) $changes[] = "category from '{$oldItem['category']}' to '$category'";
            if ($oldItem['quantity'] != $quantity) $changes[] = "quantity from {$oldItem['quantity']} to $quantity";
            if ($oldItem['unit'] !== $unit) $changes[] = "unit from '{$oldItem['unit']}' to '$unit'";
            if ($oldItem['price'] != $price) $changes[] = "price from {$oldItem['price']} to $price";
            if ($oldItem['supplier'] !== $supplier) $changes[] = "supplier from '{$oldItem['supplier']}' to '$supplier'";
            if ($oldItem['status'] !== $status) $changes[] = "status from '{$oldItem['status']}' to '$status'";
            if ($oldItem['expiry_date'] !== $expiryDate) {
                $oldExpiry = $oldItem['expiry_date'] ?: 'N/A';
                $newExpiry = $expiryDate ?: 'N/A';
                $changes[] = "expiry date from '$oldExpiry' to '$newExpiry'";
            }

            // Log the activity with changes
            if (!empty($changes)) {
                $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
                $logStmt = $pdo->prepare($logQuery);
                $logStmt->execute([
                    ':user_id' => $userId,
                    ':action' => "$userFullName updated item '$itemName': " . implode(", ", $changes)
                ]);
            }

            $_SESSION['message'] = "Inventory item updated successfully.";
        } else {
            $_SESSION['error'] = "No changes were made to the item.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update inventory item: " . $e->getMessage();
    }
    header("Location: supply.php");
    exit();
}

// Handle deleting an inventory item
if (isset($_GET['delete_id'])) {
    try {
        $itemId = $_GET['delete_id'];
        $userId = $_SESSION['user_id'];

        // Get user details for logging
        $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([':user_id' => $userId]);
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFullName = $userDetails['first_name'] . ' ' . $userDetails['last_name'];

        // Get item details before deletion for logging
        $itemQuery = "SELECT item_name, quantity, unit FROM inventory WHERE item_id = :item_id";
        $itemStmt = $pdo->prepare($itemQuery);
        $itemStmt->execute([':item_id' => $itemId]);
        $itemDetails = $itemStmt->fetch(PDO::FETCH_ASSOC);

        // Delete the item
        $query = "DELETE FROM inventory WHERE item_id = :item_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':item_id' => $itemId]);

        if ($stmt->rowCount() > 0) {
            // Log the deletion activity
            $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([
                ':user_id' => $userId,
                ':action' => "$userFullName deleted item '{$itemDetails['item_name']}' (Quantity: {$itemDetails['quantity']} {$itemDetails['unit']})"
            ]);

            $_SESSION['message'] = "Inventory item deleted successfully.";
        } else {
            $_SESSION['error'] = "Item not found or already deleted.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to delete inventory item: " . $e->getMessage();
    }
    header("Location: supply.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'item_name';

// Build the WHERE clause based on search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    switch ($search_by) {
        case 'supplier':
            $where_conditions[] = "supplier LIKE :search";
            break;
        case 'category':
            $where_conditions[] = "category LIKE :search";
            break;
        default: // item_name
            $where_conditions[] = "item_name LIKE :search";
    }
    $params[':search'] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch inventory with search, filters and pagination
$query = "SELECT * FROM inventory $where_clause ORDER BY item_id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total inventory items with filters
$totalQuery = "SELECT COUNT(*) AS total FROM inventory $where_clause";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($params as $key => $value) {
    $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalResult = $totalStmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit);

// Handle stock addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    try {
        $itemId = $_POST['item_id'];
        $quantityToAdd = $_POST['quantity'];
        $userId = $_SESSION['user_id'];

        // Get user details for logging
        $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([':user_id' => $userId]);
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFullName = $userDetails['first_name'] . ' ' . $userDetails['last_name'];

        // Get item details for logging
        $itemQuery = "SELECT item_name, unit FROM inventory WHERE item_id = :item_id";
        $itemStmt = $pdo->prepare($itemQuery);
        $itemStmt->execute([':item_id' => $itemId]);
        $itemDetails = $itemStmt->fetch(PDO::FETCH_ASSOC);

        // Validate quantity
        if (!is_numeric($quantityToAdd) || $quantityToAdd <= 0) {
            throw new Exception("Quantity must be a valid positive number.");
        }

        // Update inventory quantity
        $updateQuery = "UPDATE inventory SET quantity = quantity + :quantity WHERE item_id = :item_id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            ':quantity' => $quantityToAdd,
            ':item_id' => $itemId
        ]);

        // Insert stock record
        $stockQuery = "INSERT INTO stocks (item_id, user_id, quantity, date, actions) 
                       VALUES (:item_id, :user_id, :quantity, NOW(), 'restock')";
        $stmt = $pdo->prepare($stockQuery);
        $stmt->execute([
            ':item_id' => $itemId,
            ':user_id' => $userId,
            ':quantity' => $quantityToAdd
        ]);

        // Log the activity with user's full name
        $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $userId,
            ':action' => "$userFullName added stock for {$itemDetails['item_name']}: +$quantityToAdd {$itemDetails['unit']}"
        ]);

        $_SESSION['message'] = "Stock added successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add stock: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: supply.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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

        /* Adjust margin when sidebar is collapsed */
        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }

        /* Responsive adjustments */
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .status-instock {
            background-color: #d4edda;
            color: #155724;
        }

        .status-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-out {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pagination {
            margin-top: 20px;
        }

        .page-link {
            color: var(--primary-color);
            border-color: var(--border-color);
        }

        .page-link:hover {
            color: var(--primary-light);
            background-color: var(--secondary-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-outline-success {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-success:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
                            <i class="fas fa-boxes me-2"></i>Inventory Management
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-cubes me-2"></i>Total Items: <?= $totalRecords ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                            <i class="fas fa-plus me-2"></i>Add New Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form id="searchForm" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search by Item, Supplier, or Category..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="categoryFilter" name="category" class="form-select">
                            <option value="">All Categories</option>
                            <option value="Medical Equipment" <?= $category_filter === 'Medical Equipment' ? 'selected' : '' ?>>Medical Equipment</option>
                            <option value="Medical Supplies" <?= $category_filter === 'Medical Supplies' ? 'selected' : '' ?>>Medical Supplies</option>
                            <option value="Medications" <?= $category_filter === 'Medications' ? 'selected' : '' ?>>Medications</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="statusFilter" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="In Stock" <?= $status_filter === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                            <option value="Low Stock" <?= $status_filter === 'Low Stock' ? 'selected' : '' ?>>Low Stock</option>
                            <option value="Out of Stock" <?= $status_filter === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="searchByFilter" name="search_by" class="form-select">
                            <option value="item_name" <?= $search_by === 'item_name' ? 'selected' : '' ?>>Search by Item</option>
                            <option value="supplier" <?= $search_by === 'supplier' ? 'selected' : '' ?>>Search by Supplier</option>
                            <option value="category" <?= $search_by === 'category' ? 'selected' : '' ?>>Search by Category</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="supply.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo-alt me-2"></i>Reset
                        </a>
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

            <!-- Inventory Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inventory)): ?>
                            <?php foreach ($inventory as $index => $item): ?>
                                <tr>
                                    <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td><?= htmlspecialchars($item['supplier']) ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($item['status']);
                                            $badgeClass = '';
                                            if (stripos($status, 'in stock') !== false) {
                                                $badgeClass = 'status-badge status-instock';
                                            } elseif (stripos($status, 'low') !== false) {
                                                $badgeClass = 'status-badge status-low';
                                            } else {
                                                $badgeClass = 'status-badge status-out';
                                            }
                                        ?>
                                        <span class="<?= $badgeClass ?>"><?= $status ?></span>
                                    </td>
                                    <td><?= $item['expiry_date'] ? htmlspecialchars($item['expiry_date']) : 'N/A' ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#addStockModal<?= $item['item_id'] ?>" title="Add Stock">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button class="btn btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editInventoryModal<?= $item['item_id'] ?>" title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="supply.php?delete_id=<?= $item['item_id'] ?>" class="btn btn-danger btn-action" onclick="return confirm('Are you sure you want to delete this item?')" title="Delete Item">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No inventory items found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php
                        // Build query string for pagination links
                        $queryParams = [];
                        if (!empty($search)) $queryParams['search'] = $search;
                        if (!empty($category_filter)) $queryParams['category'] = $category_filter;
                        if (!empty($status_filter)) $queryParams['status'] = $status_filter;
                        if (!empty($search_by)) $queryParams['search_by'] = $search_by;
                        
                        // Function to generate pagination URL
                        function getPageUrl($pageNum, $queryParams) {
                            $queryParams['page'] = $pageNum;
                            return '?' . http_build_query($queryParams);
                        }
                        ?>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPageUrl(1, $queryParams) ?>" aria-label="First">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPageUrl(max(1, $page - 1), $queryParams) ?>" aria-label="Previous">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= getPageUrl($i, $queryParams) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPageUrl(min($totalPages, $page + 1), $queryParams) ?>" aria-label="Next">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPageUrl($totalPages, $queryParams) ?>" aria-label="Last">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Inventory Modal -->
    <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="supply.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Inventory Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="Medical Equipment">Medical Equipment</option>
                                <option value="Medical Supplies">Medical Supplies</option>
                                <option value="Medications">Medications</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" name="unit" id="unitAdd" autocomplete="off" required>
                            <ul id="unitSuggestions" class="list-group" style="position: absolute; width: 100%; max-height: 150px; overflow-y: auto;"></ul>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" name="supplier" id="supplierAdd" autocomplete="off" required>
                            <ul id="supplierSuggestions" class="list-group" style="position: absolute; width: 100%; max-height: 150px; overflow-y: auto;"></ul>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" name="add_item">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Inventory Modal -->
    <?php foreach ($inventory as $item): ?>
        <div class="modal fade" id="editInventoryModal<?= $item['item_id'] ?>" tabindex="-1" aria-labelledby="editInventoryModalLabel<?= $item['item_id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="supply.php" method="POST">
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editInventoryModalLabel<?= $item['item_id'] ?>">Edit Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Item Name -->
                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                            </div>
                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" required>
                                    <option value="Medical Equipment" <?= $item['category'] == 'Medical Equipment' ? 'selected' : '' ?>>Medical Equipment</option>
                                    <option value="Medical Supplies" <?= $item['category'] == 'Medical Supplies' ? 'selected' : '' ?>>Medical Supplies</option>
                                    <option value="Medications" <?= $item['category'] == 'Medications' ? 'selected' : '' ?>>Medications</option>
                                </select>
                            </div>
                            <!-- Quantity -->
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" required>
                            </div>
                            <!-- Unit -->
                            <div class="mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" id="unit<?= $item['item_id'] ?>"  value="<?= htmlspecialchars($item['unit']) ?>"  autocomplete="off" required>
                                <ul id="unitSuggestions<?= $item['item_id'] ?>" class="list-group" style="position: absolute; width: 100%; max-height: 150px; overflow-y: auto;"></ul>
                            </div>
                            <!-- Price -->
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="text" class="form-control" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>
                            </div>
                            <!-- Supplier -->
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" name="supplier" id="supplier<?= $item['item_id'] ?>" value="<?= htmlspecialchars($item['supplier']) ?>"  autocomplete="off" required>
                                <ul id="supplierSuggestions<?= $item['item_id'] ?>" class="list-group" style="position: absolute; width: 100%; max-height: 150px; overflow-y: auto;"></ul>
                            </div>
                            <!-- Expiry Date -->
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" value="<?= htmlspecialchars($item['expiry_date']) ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" name="update_item">Update Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php foreach ($inventory as $item): ?>
        <div class="modal fade" id="addStockModal<?= $item['item_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="supply.php" method="POST">
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Stock - <?= htmlspecialchars($item['item_name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Quantity to Add</label>
                                <input type="number" class="form-control" name="quantity" required min="1">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" name="add_stock">Add Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');
            const searchByFilter = document.getElementById('searchByFilter');
            
            let searchTimeout;

            function handleSearch() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchForm.submit();
                }, 500); // 500ms delay for search input
            }

            // Add event listeners
            searchInput.addEventListener('input', handleSearch);
            categoryFilter.addEventListener('change', () => searchForm.submit());
            statusFilter.addEventListener('change', () => searchForm.submit());
            searchByFilter.addEventListener('change', () => searchForm.submit());
        });

        // Keep the existing jQuery code for suggestions
        $(document).ready(function () {
            // Function to handle suggestion fetching for both Add & Edit modals
            function fetchSuggestions(inputSelector, suggestionListPrefix, url, type) {
                $(document).on('input', inputSelector, function () {
                    var searchTerm = $(this).val();
                    var modal = $(this).closest('.modal');
                    var isEditModal = modal.attr('id')?.includes('editInventoryModal');
                    var itemId = isEditModal ? modal.attr('id').replace('editInventoryModal', '') : '';
                    var suggestionList = '#' + suggestionListPrefix + (isEditModal ? itemId : ''); // Dynamic suggestion list ID

                    if (searchTerm.length > 0) {
                        $.ajax({
                            url: url,  // PHP file to fetch suggestions
                            method: 'GET',
                            data: { term: searchTerm },  // Send the search term
                            success: function (data) {
                                var results = JSON.parse(data);
                                var suggestions = $(suggestionList);
                                suggestions.empty(); // Clear previous suggestions

                                // Display the fetched suggestions
                                results.forEach(function (result) {
                                    var value = type === 'unit' ? result.unit : result.supplier; // Fix for supplier issue
                                    suggestions.append('<li class="list-group-item list-group-item-action">' + value + '</li>');
                                });

                                // Show suggestions list
                                suggestions.show();

                                // Handle click event for suggestion selection
                                $(suggestionList + ' li').on('click', function () {
                                    $(inputSelector).val($(this).text());  // Set the input field
                                    suggestions.hide();  // Hide suggestions after selection
                                });
                            }
                        });
                    } else {
                        $(suggestionList).empty().hide();  // Hide suggestions if input is empty
                    }
                });
            }

            // Apply to both Add & Edit Modals
            fetchSuggestions('input[name="unit"]', 'unitSuggestions', 'fetch_units.php', 'unit'); // Units
            fetchSuggestions('input[name="supplier"]', 'supplierSuggestions', 'fetch_suppliers.php', 'supplier'); // Suppliers

            // Ensure that existing values in Edit Modal fields are not removed
            $('.modal').on('shown.bs.modal', function () {
                $(this).find('input[name="unit"]').each(function () {
                    var unitValue = $(this).val().trim();
                    if (unitValue.length > 0) {
                        $('#unitSuggestions' + $(this).closest('.modal').attr('id').replace('editInventoryModal', '')).hide();
                    }
                });

                $(this).find('input[name="supplier"]').each(function () {
                    var supplierValue = $(this).val().trim();
                    if (supplierValue.length > 0) {
                        $('#supplierSuggestions' + $(this).closest('.modal').attr('id').replace('editInventoryModal', '')).hide();
                    }
                });
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.form-control').length) {
                    $('.list-group').hide();
                }
            });
        });
    </script>
</body>

</html>
