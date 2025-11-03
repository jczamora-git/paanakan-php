<?php
// Returns the HTML for the appointments table (table + modals) so clients can refresh the table via AJAX
require_once __DIR__ . '/../connections/connections.php';

$pdo = connection();

date_default_timezone_set('Asia/Manila'); 
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d', strtotime($startDate . ' +6 days'));
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$due_only = isset($_GET['due_only']) && $_GET['due_only'] == '1';
$today = date('Y-m-d');

$filter_appointment_id = isset($_GET['appointment_id']) && is_numeric($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
if ($filter_appointment_id && $search === '') {
    $search = (string) $filter_appointment_id;
}
$ignoreDateRange = $filter_appointment_id > 0;

if ($due_only) {
    $startDate = '';
    $endDate = '';
}

$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_conditions = ["a.status = 'Pending'"];
$params = [];

if (!empty($search)) {
    $searchParam = "%$search%";
    $searchConditions = [
        "CONCAT(p.first_name, ' ', p.last_name) LIKE :search",
        "p.contact_number LIKE :search",
        "p.case_id LIKE :search"
    ];
    $params[':search'] = $searchParam;
    if (ctype_digit($search)) {
        $searchConditions[] = "a.appointment_id = :search_exact";
        $params[':search_exact'] = (int) $search;
    }
    $where_conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
}

if (!empty($type_filter)) {
    $where_conditions[] = "a.appointment_type = :type";
    $params[':type'] = $type_filter;
}

if ($due_only) {
    $where_conditions[] = "DATE(a.scheduled_date) < :today";
    $params[':today'] = $today;
}

if (!empty($filter_appointment_id)) {
    $where_conditions[] = "a.appointment_id = :appointment_id";
    $params[':appointment_id'] = $filter_appointment_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Build query similar to main page
if ($ignoreDateRange) {
    $totalQuery = $pdo->prepare(
        "SELECT COUNT(*) 
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause"
    );
    $totalQuery->execute($params);
    $totalRecords = $totalQuery->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    $appointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE $where_clause
    ORDER BY a.scheduled_date ASC
    LIMIT :limit OFFSET :offset";

} else {
    if ($due_only) {
        $totalQuery = $pdo->prepare(
            "SELECT COUNT(*) 
            FROM Appointments a
            JOIN Patients p ON a.patient_id = p.patient_id
            WHERE $where_clause"
        );
        $totalQuery->execute($params);
        $totalRecords = $totalQuery->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        $appointmentsQuery = "
        SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause
        ORDER BY a.scheduled_date ASC
        LIMIT :limit OFFSET :offset";
    } else {
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
        $totalQuery = $pdo->prepare(
            "SELECT COUNT(*) 
            FROM Appointments a
            JOIN Patients p ON a.patient_id = p.patient_id
            WHERE $where_clause
            AND DATE(a.scheduled_date) BETWEEN :startDate AND :endDate"
        );
        $totalQuery->execute($params);
        $totalRecords = $totalQuery->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        $appointmentsQuery = "
        SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause
        AND DATE(a.scheduled_date) BETWEEN :startDate AND :endDate
        ORDER BY a.scheduled_date ASC
        LIMIT :limit OFFSET :offset";
    }
}

$stmt = $pdo->prepare($appointmentsQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output the table container HTML (same structure used in online_appointments.php)
?>
<div class="table-container">
    <h5 class="mb-3">
        <?php if ($due_only): ?>
            Pending Due Appointments
        <?php elseif ($ignoreDateRange): ?>
            Pending Appointments (Filtered)
        <?php else: ?>
            Pending Appointments (<?php echo date('M j', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)
        <?php endif; ?>
    </h5>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Patient Name</th>
                <th>Contact</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <tr id="appointment-<?= $appointment['appointment_id'] ?>">
                        <td><?= date("M j, Y", strtotime($appointment['scheduled_date'])) ?></td>
                        <td><?= date("g:i A", strtotime($appointment['scheduled_date'])) ?></td>
                        <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                        <td><?= htmlspecialchars($appointment['contact_number']) ?></td>
                        <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                        <td>
                            <span class="status-badge status-pending">Pending</span>
                        </td>
                        <td>
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#approveModal<?= $appointment['appointment_id'] ?>" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disapproveModal<?= $appointment['appointment_id'] ?>" title="Disapprove">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-labelledby="approveModalLabel<?= $appointment['appointment_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="approveModalLabel<?= $appointment['appointment_id'] ?>">Approve Appointment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to approve this appointment for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong> on <strong><?= date("M j, Y g:i A", strtotime($appointment['scheduled_date'])) ?></strong>?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-success confirm-approve" data-id="<?= $appointment['appointment_id'] ?>">Approve</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Disapprove Modal -->
                    <div class="modal fade" id="disapproveModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-labelledby="disapproveModalLabel<?= $appointment['appointment_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="disapproveModalLabel<?= $appointment['appointment_id'] ?>">Disapprove Appointment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to disapprove this overdue appointment for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong> scheduled on <strong><?= date("M j, Y g:i A", strtotime($appointment['scheduled_date'])) ?></strong>?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger confirm-disapprove" data-id="<?= $appointment['appointment_id'] ?>">Disapprove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-info-circle me-2"></i>No pending appointments found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : '' ?><?= $ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php
exit;
