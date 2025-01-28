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

// Handle adding new supply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $itemName = $_POST['item_name'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];

    $query = "INSERT INTO medicine_supplies (item_name, category, unit, price) 
              VALUES (:item_name, :category, :unit, :price)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':item_name' => $itemName,
        ':category' => $category,
        ':unit' => $unit,
        ':price' => $price
    ]);
    $_SESSION['message'] = "New supply added successfully.";
    header("Location: supply.php");
    exit();
}

// Handle updating an existing supply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $itemId = $_POST['item_id'];
    $itemName = $_POST['item_name'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];

    $query = "UPDATE medicine_supplies SET item_name = :item_name, category = :category, unit = :unit, price = :price
              WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':id' => $itemId,
        ':item_name' => $itemName,
        ':category' => $category,
        ':unit' => $unit,
        ':price' => $price
    ]);
    $_SESSION['message'] = "Supply updated successfully.";
    header("Location: supply.php");
    exit();
}

// Handle deleting a supply
if (isset($_GET['delete_id'])) {
    $itemId = $_GET['delete_id'];
    $query = "DELETE FROM medicine_supplies WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $itemId]);
    $_SESSION['message'] = "Supply deleted successfully.";
    header("Location: supply.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch supplies with pagination
$query = "SELECT * FROM medicine_supplies ORDER BY date_added DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total supplies
$totalQuery = "SELECT COUNT(*) AS total FROM medicine_supplies";
$totalResult = $pdo->query($totalQuery)->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Supplies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../PSC_banner.png" alt="Paanakan Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><span class="material-icons">dashboard</span><span class="link-text">Dashboard</span></a></li>
                <li><a href="manage_appointments.php"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
                <li><a href="manage_health_records.php"><span class="material-icons">folder</span><span class="link-text">Health Records</span></a></li>
                <li><a href="transactions.php"><span class="material-icons">local_hospital</span><span class="link-text">Medical Services</span></a></li>
                <li><a href="patient.php"><span class="material-icons">person</span><span class="link-text">Patients</span></a></li>
                <li><a href="supply.php"><span class="material-icons">inventory_2</span><span class="link-text">Supplies</span></a></li>
                <li><a href="billing.php"><span class="material-icons">receipt</span><span class="link-text">Billing</span></a></li>
                <li><a href="reports.php"><span class="material-icons">assessment</span><span class="link-text">Reports</span></a></li>
                <li><a href="manage_users.php"><span class="material-icons">people</span><span class="link-text">Users</span></a></li>
                <li><a href="logs.php"><span class="material-icons">history</span><span class="link-text">Logs</span></a></li>
                <li><a href="../logout.php"><span class="material-icons">logout</span><span class="link-text">Logout</span></a></li>
            </ul>
        </aside>

        <main class="dashboard-main-content">
            <div class="container mt-5">
                <h2 class="mb-4">Medicine Supplies</h2>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSupplyModal">Add Supply</button>

                <!-- Live Search -->
                <div class="mb-3">
                    <input type="text" id="search" class="form-control" placeholder="Search supplies by name or category...">
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="supplyTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplies as $supply): ?>
                                <tr>
                                    <td><?= htmlspecialchars($supply['id']) ?></td>
                                    <td><?= htmlspecialchars($supply['item_name']) ?></td>
                                    <td><?= htmlspecialchars($supply['category']) ?></td>
                                    <td><?= htmlspecialchars($supply['unit']) ?></td>
                                    <td><?= number_format($supply['price'], 2) ?></td>
                                    <td><?= (new DateTime($supply['date_added']))->format('F j, Y') ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSupplyModal<?= $supply['id'] ?>">Edit</button>
                                        <a href="supply.php?delete_id=<?= $supply['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalRecords > 10): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addSupplyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="supply.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Supply</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="item_name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" name="category">
                        </div>
                        <div class="mb-3">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" name="unit">
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" name="add_item">Add Supply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#search').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#supplyTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
