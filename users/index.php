<?php
// users/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
requireRole('admin');

$errors = [];
$teams = dbRows('SELECT id, name FROM teams ORDER BY name');

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';
    $team_id = (int) ($_POST['team_id'] ?? 0) ?: null;

    if (!$name)
        $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Valid email required.';
    if (strlen($pass) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if (dbRow('SELECT id FROM users WHERE email = ?', [$email]))
        $errors[] = 'Email already exists.';

    if (!$errors) {
        dbRun(
            'INSERT INTO users (name, email, password, role, team_id) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $team_id]
        );
        flash('success', 'User created successfully.');
        redirect('/tasktrack/users/index.php');
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $team_id = (int) ($_POST['team_id'] ?? 0) ?: null;
    $pass = $_POST['password'] ?? '';

    if (!$errors) {
        if ($pass) {
            dbRun(
                'UPDATE users SET name=?, email=?, role=?, team_id=?, password=? WHERE id=?',
                [$name, $email, $role, $team_id, password_hash($pass, PASSWORD_BCRYPT), $id]
            );
        } else {
            dbRun(
                'UPDATE users SET name=?, email=?, role=?, team_id=? WHERE id=?',
                [$name, $email, $role, $team_id, $id]
            );
        }
        flash('success', 'User updated.');
        redirect('/tasktrack/users/index.php');
    }
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id && $id !== (int) auth()['id']) {
        dbRun('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'User deleted.');
    }
    redirect('/tasktrack/users/index.php');
}

$result = paginate(
    'SELECT u.*, t.name AS team_name FROM users u LEFT JOIN teams t ON u.team_id = t.id ORDER BY u.name',
    []
);

$pageTitle = 'Users';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Users</h1>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        + New user
    </button>
</div>

<?php if ($errors): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
        <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Team</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Joined</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php
            $roleBadge = [
                'admin' => 'bg-purple-100 text-purple-700',
                'manager' => 'bg-blue-100 text-blue-700',
                'staff' => 'bg-gray-100 text-gray-600',
            ];
            foreach ($result['rows'] as $u):
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-semibold">
                                <?= e(initials($u['name'])) ?>
                            </div>
                            <span class="font-medium text-gray-800"><?= e($u['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600"><?= e($u['email']) ?></td>
                    <td class="px-5 py-3">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $roleBadge[$u['role']] ?? '' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500"><?= e($u['team_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-400 text-xs"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3 justify-end">
                            <button onclick="openEdit(
                            <?= $u['id'] ?>,
                            '<?= addslashes($u['name']) ?>',
                            '<?= addslashes($u['email']) ?>',
                            '<?= $u['role'] ?>',
                            '<?= $u['team_id'] ?>'
                        )" class="text-xs text-indigo-600 hover:underline">Edit</button>

                            <?php if ($u['id'] !== (int) auth()['id']): ?>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete <?= addslashes($u['name']) ?>?')"
                                        class="text-xs text-red-400 hover:text-red-600">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['rows'])): ?>
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= paginationLinks($result) ?>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">New user</h2>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                    <select name="team_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">No team</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Create
                </button>
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">Edit user</h2>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="editName" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="editEmail" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New password <span
                        class="text-gray-400">(leave blank to keep)</span></label>
                <input type="password" name="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="editRole"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                    <select name="team_id" id="editTeam"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">No team</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Save changes
                </button>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEdit(id, name, email, role, teamId) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('editTeam').value = teamId || '';
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>