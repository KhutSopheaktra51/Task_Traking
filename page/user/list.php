<?php
$page_title = 'Users';
$me         = loggedInUser();
$errors     = [];
$users      = getAllUsers();

// ---- Create ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'create') {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'staff';

    if (!$name)                                                       $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))                   $errors[] = 'Valid email required.';
    if (strlen($pass) < 8)                                            $errors[] = 'Password must be at least 8 characters.';
    if (getRow('SELECT id FROM users WHERE email = ?', [$email]))     $errors[] = 'Email already exists.';

    if (!$errors) {
        createUser($name, $email, $pass, $role);
        setFlash('success', 'User created.');
        redirect('./?page=user/list');
    }
}

// ---- Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update') {
    verifyCsrf();
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $pass = $_POST['password'] ?? '';

    if ($id) {
        updateUser($id, $name, $email, $role, $pass ?: null);
        setFlash('success', 'User updated.');
        redirect('./?page=user/list');
    }
}

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== (int)$me['id']) {
        deleteUser($id);
        setFlash('success', 'User deleted.');
    }
    redirect('./?page=user/list');
}
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Users</h1>
    <button onclick="document.getElementById('create_modal').classList.remove('hidden')"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        + New user
    </button>
</div>

<?php if ($errors): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Joined</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No users.</td></tr>
            <?php endif; ?>
            <?php
            $role_badge = ['admin'=>'bg-purple-100 text-purple-700','manager'=>'bg-blue-100 text-blue-700','staff'=>'bg-gray-100 text-gray-600'];
            foreach ($users as $u):
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center
                                    text-indigo-700 text-xs font-bold">
                            <?= e(getInitials($u['name'])) ?>
                        </div>
                        <span class="font-medium text-gray-800"><?= e($u['name']) ?></span>
                    </div>
                </td>
                <td class="px-5 py-3 text-gray-600"><?= e($u['email']) ?></td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                 <?= $role_badge[$u['role']] ?? '' ?>">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-400 text-xs">
                    <?= date('M j, Y', strtotime($u['created_at'])) ?>
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3 justify-end">
                        <button onclick="openEdit(<?= $u['id'] ?>,'<?= addslashes($u['name']) ?>','<?= addslashes($u['email']) ?>','<?= $u['role'] ?>')"
                                class="text-xs text-indigo-600 hover:underline">Edit</button>
                        <?php if ($u['id'] !== (int)$me['id']): ?>
                        <form method="POST" action="./?page=user/list">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit"
                                    onclick="return confirm('Delete <?= addslashes($u['name']) ?>?')"
                                    class="text-xs text-red-400 hover:text-red-600">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create modal -->
<div id="create_modal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">New user</h2>
        <form method="POST" action="./?page=user/list" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               text-sm font-medium rounded-lg">Create</button>
                <button type="button"
                        onclick="document.getElementById('create_modal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700
                               text-sm font-medium rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit modal -->
<div id="edit_modal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-gray-200 w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">Edit user</h2>
        <form method="POST" action="./?page=user/list" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    New password <span class="text-gray-400">(leave blank to keep)</span>
                </label>
                <input type="password" name="password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="edit_role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                               text-sm font-medium rounded-lg">Save</button>
                <button type="button"
                        onclick="document.getElementById('edit_modal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700
                               text-sm font-medium rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, email, role) {
    document.getElementById('edit_id').value    = id;
    document.getElementById('edit_name').value  = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value  = role;
    document.getElementById('edit_modal').classList.remove('hidden');
}
</script>
<!-- Visit:
```
http://localhost/tasktrack/?page=login -->