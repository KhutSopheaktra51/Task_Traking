<?php
$page_title = 'Login';
$error = '';

if (loggedInUser()) {
    redirect('./?page=dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = getRow('SELECT * FROM users WHERE email = ?', [$email]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        redirect('./?page=dashboard');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<div class="w-full max-w-sm mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-indigo-600">TaskTrack</h1>
        <p class="text-gray-500 text-sm mt-1">Task Manager & Attendance Tracker</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-8 py-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Sign in</h2>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="./?page=login" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium
                           py-2 px-4 rounded-lg text-sm transition-colors">
                Sign in
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-gray-100 text-xs text-gray-400 text-center space-y-0.5">
            <p>Demo: admin@tasktrack.test / password</p>
        </div>
    </div>
</div>