<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Ensure only admin can access this page
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        
        // Update currency settings
        $stmt->execute([$_POST['currency_code'], 'currency_code']);
        $stmt->execute([$_POST['currency_symbol'], 'currency_symbol']);
        $stmt->execute([$_POST['currency_position'], 'currency_position']);
        $stmt->execute([$_POST['decimal_places'], 'decimal_places']);
        
        $success = 'Settings updated successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = "System Settings";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <h5 class="card-title mb-4">Currency Settings</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="currency_code" class="form-label">Currency Code</label>
                                <input type="text" class="form-control" id="currency_code" name="currency_code" 
                                       value="<?php echo htmlspecialchars($settings['currency_code'] ?? 'TZS'); ?>" required>
                                <small class="text-muted">e.g., TZS, USD, EUR</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                       value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'TZS'); ?>" required>
                                <small class="text-muted">e.g., TZS, $, €</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="currency_position" class="form-label">Currency Position</label>
                                <select class="form-select" id="currency_position" name="currency_position">
                                    <option value="before" <?php echo ($settings['currency_position'] ?? 'before') === 'before' ? 'selected' : ''; ?>>Before amount</option>
                                    <option value="after" <?php echo ($settings['currency_position'] ?? 'before') === 'after' ? 'selected' : ''; ?>>After amount</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="decimal_places" class="form-label">Decimal Places</label>
                                <input type="number" class="form-control" id="decimal_places" name="decimal_places" 
                                       value="<?php echo htmlspecialchars($settings['decimal_places'] ?? '2'); ?>" 
                                       min="0" max="4" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 