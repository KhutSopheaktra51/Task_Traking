<?php
// auth/register.php
require_once __DIR__ . '/../includes/helpers.php';
if (auth())
    redirect('/tasktrack/dashboard/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if (!$name)
        $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Valid email is required.';
    if (strlen($pass) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $confirm)
        $errors[] = 'Passwords do not match.';
    if (dbRow('SELECT id FROM users WHERE email = ?', [$email]))
        $errors[] = 'Email already registered.';

    if (!$errors) {
        $id = dbRun(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [$name, $email, password_hash($pass, PASSWORD_BCRYPT), 'staff']
        );
        $_SESSION['user'] = dbRow('SELECT * FROM users WHERE id = ?', [$id]);
        redirect('/tasktrack/dashboard/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <title>Register — TaskTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-full flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-600">TaskTrack</h1>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-8 py-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Create your account</h2>
            <?php if ($errors): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                    <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                    Create account
                </button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-500">
                Already registered? <a href="/tasktrack/auth/login.php" class="text-indigo-600 hover:underline">Sign
                    in</a>
            </p>
        </div>
    </div>
</body>

</html>