<?php
// Simple web UI to test EmailService (SendGrid)
require_once __DIR__ . '/EmailService.php';

$result = null;
$error = null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $svc = new EmailService();
        $action = $_POST['action'] ?? 'send';

        if ($action === 'send') {
            $to = $_POST['to_email'] ?? '';
            $name = $_POST['to_name'] ?? '';
            $subject = $_POST['subject'] ?? 'Test Email from Paanakan';
            $body_text = $_POST['body_text'] ?? 'This is a test email.';
            $body_html = $_POST['body_html'] ?? null;
            $result = $svc->sendEmail($to, $name, $subject, $body_text, $body_html);
        } elseif ($action === 'appointment') {
            $to = $_POST['to_email'] ?? '';
            $name = $_POST['to_name'] ?? '';
            $appointment = [
                'date' => $_POST['date'] ?? date('Y-m-d'),
                'time' => $_POST['time'] ?? '09:00 AM',
                'type' => $_POST['atype'] ?? 'General Checkup'
            ];
            $result = $svc->sendAppointmentConfirmation($to, $name, $appointment);
        } elseif ($action === 'welcome') {
            $to = $_POST['to_email'] ?? '';
            $name = $_POST['to_name'] ?? '';
            $case = $_POST['case_id'] ?? null;
            $result = $svc->sendWelcomeEmail($to, $name, $case);
        } elseif ($action === 'reset') {
            $to = $_POST['to_email'] ?? '';
            $name = $_POST['to_name'] ?? '';
            $link = $_POST['reset_link'] ?? 'https://example.com/reset';
            $result = $svc->sendPasswordReset($to, $name, $link);
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SendGrid Test UI — Paanakan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h1 class="mb-3">SendGrid Test UI</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger">Error: <?php echo h($error); ?></div>
  <?php endif; ?>

  <?php if ($result !== null): ?>
    <?php if (isset($result['success']) && $result['success']): ?>
      <div class="alert alert-success">Email sent successfully. Status: <?php echo h($result['status_code'] ?? 'unknown'); ?></div>
    <?php else: ?>
      <div class="alert alert-warning">Send failed. <?php echo h(json_encode($result)); ?></div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Send a simple email</h5>
          <form method="post">
            <input type="hidden" name="action" value="send">
            <div class="mb-2">
              <label class="form-label">To (email)</label>
              <input class="form-control" name="to_email" value="<?php echo h($_POST['to_email'] ?? ''); ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">To (name)</label>
              <input class="form-control" name="to_name" value="<?php echo h($_POST['to_name'] ?? 'Test User'); ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Subject</label>
              <input class="form-control" name="subject" value="<?php echo h($_POST['subject'] ?? 'Test Email from Paanakan'); ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Body (text)</label>
              <textarea class="form-control" name="body_text" rows="3"><?php echo h($_POST['body_text'] ?? 'This is a test email sent via SendGrid API.'); ?></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">Body (HTML, optional)</label>
              <textarea class="form-control" name="body_html" rows="3"><?php echo h($_POST['body_html'] ?? '<h1>Test Email</h1><p>This is a test email sent via SendGrid API.</p>'); ?></textarea>
            </div>
            <button class="btn btn-primary">Send</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Send appointment confirmation</h5>
          <form method="post">
            <input type="hidden" name="action" value="appointment">
            <div class="mb-2"><label class="form-label">To (email)</label><input class="form-control" name="to_email" value="test@example.com"></div>
            <div class="mb-2"><label class="form-label">To (name)</label><input class="form-control" name="to_name" value="Jane Doe"></div>
            <div class="mb-2"><label class="form-label">Date</label><input class="form-control" name="date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"></div>
            <div class="mb-2"><label class="form-label">Time</label><input class="form-control" name="time" value="10:00 AM"></div>
            <div class="mb-2"><label class="form-label">Type</label><input class="form-control" name="atype" value="Prenatal Checkup"></div>
            <button class="btn btn-secondary">Send appointment</button>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Send welcome / reset emails</h5>
          <form method="post" class="mb-2">
            <input type="hidden" name="action" value="welcome">
            <div class="mb-2"><label class="form-label">To (email)</label><input class="form-control" name="to_email" value="test@example.com"></div>
            <div class="mb-2"><label class="form-label">To (name)</label><input class="form-control" name="to_name" value="John Smith"></div>
            <div class="mb-2"><label class="form-label">Case ID (optional)</label><input class="form-control" name="case_id" value="C001"></div>
            <button class="btn btn-success">Send welcome</button>
          </form>

          <form method="post">
            <input type="hidden" name="action" value="reset">
            <div class="mb-2"><label class="form-label">To (email)</label><input class="form-control" name="to_email" value="test@example.com"></div>
            <div class="mb-2"><label class="form-label">To (name)</label><input class="form-control" name="to_name" value="John Smith"></div>
            <div class="mb-2"><label class="form-label">Reset link</label><input class="form-control" name="reset_link" value="https://example.com/reset?token=abc123"></div>
            <button class="btn btn-warning">Send password reset</button>
          </form>

        </div>
      </div>
    </div>
  </div>

  <p class="text-muted">Notes: Use a verified sender on your SendGrid account. Replace test recipient emails before sending real messages.</p>
</div>
</body>
</html>
