<?php
$page_title = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = getRow('SELECT * FROM users WHERE email = ?', [$email]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        redirect('./?page=dashboard');
    } else {
        $errors[] = 'Wrong email or password.';
    }
}
?>

<div class="flex items-center justify-center min-h-screen -mt-16">
    <div class="w-full max-w-sm mx-auto">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-600">TaskTrack</h1>
            <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 px-8 py-8">

            <?php if ($errors): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
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

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white
                               font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                    Sign in
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-500">
                No account?
                <a href="./?page=register" class="text-indigo-600 hover:underline">Register</a>
            </p>

            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 text-center">
                <p>Demo: admin@tasktrack.test / password</p>
                <p>Created By: Khut Sopheaktra</p>
            </div>

        </div>
    </div>
</div>