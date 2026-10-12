
<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

} catch (PDOException $e) {
    error_log('Login database query failed.');
    http_response_code(500);
    exit('Something went wrong. Please try again later.');
}

if (
    !$user ||
    !password_verify($password, $user['password'])
) {
    header('Location: index.php?error=1');
    exit;
}

if ($user['status'] !== 'active') {
    header('Location: index.php?error=2');
    exit;
}

$allowedRoles = [
    'admin',
    'doctor',
    'receptionist',
    'accountant'
];

if (!in_array($user['role'], $allowedRoles, true)) {
    error_log('Login rejected: invalid user role.');
    header('Location: index.php?error=1');
    exit;
}

// Regenerate the session ID after successful authentication.
session_regenerate_id(true);

$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['role'];

redirect_by_role($user['role']);
