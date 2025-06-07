<?php
require_once '../include/functions.php';

// If already logged in as admin, redirect to admin dashboard
if (isAdmin()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password';
        }
    }
}

include '../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="text-center mb-4">Admin Login</h2>
                
                <?php if ($error): ?>
                    <?php echo displayError($error); ?>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input placeholder="Enter your username" type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Please enter your username
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input placeholder="Enter your password" type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Please enter your password
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="../index.php" class="text-decoration-none">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?> 