<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Profile</h1>
    <span class="text-sm text-muted font-mono">web_panel_access</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- Panel 1: My Profile -->
    <div class="bg-bg1 border border-bg0 p-6 flex flex-col h-full rounded-sm">
        <h2 class="text-lg font-ui text-fg mb-2 border-b border-bg0 pb-2">My Profile</h2>
        <p class="text-xs text-muted mb-6 font-mono">Change your login name or password. You will be logged out after saving.</p>
        
        <form action="/profile" method="POST" class="flex-grow flex flex-col gap-4 font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Username (Login)</label>
                <input type="text" name="username" value="<?= htmlspecialchars($currentUsername ?? '') ?>" required class="px-3 py-2 text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">New Password</label>
                <input type="password" name="password" required placeholder="Enter new password" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            
            <div class="pt-4 mt-auto">
                <button type="submit" class="w-full px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    Save & Logout
                </button>
            </div>
        </form>
    </div>
    
    <!-- Panel 2: Create Access -->
    <div class="bg-bg1 border border-bg0 p-6 flex flex-col h-full rounded-sm">
        <h2 class="text-lg font-ui text-fg mb-2 border-b border-bg0 pb-2">Create New Access</h2>
        <p class="text-xs text-muted mb-6 font-mono">Create a new login for other IT analysts. They will have full panel access.</p>
        
        <form action="/profile" method="POST" class="flex-grow flex flex-col gap-4 font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_admin">
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">New Username</label>
                <input type="text" name="new_username" required placeholder="e.g., john.doe" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc2 transition-colors">
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Password</label>
                <input type="password" name="new_password" required placeholder="Enter password" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc2 transition-colors">
            </div>
            
            <div class="pt-4 mt-auto">
                <button type="submit" class="w-full px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    Create Admin
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Panel 3: Admins List -->
<div class="bg-bg1 border border-bg0 p-6 rounded-sm">
    <h2 class="text-lg font-ui text-fg mb-4 border-b border-bg0 pb-2">Panel Administrators</h2>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left font-mono text-xs">
            <thead class="text-muted uppercase tracking-wider border-b border-bg0">
                <tr>
                    <th class="px-4 py-3 font-medium">ID</th>
                    <th class="px-4 py-3 font-medium">Username</th>
                    <th class="px-4 py-3 font-medium">Created At</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bg0">
                <?php if (!empty($allAdmins)): ?>
                    <?php foreach ($allAdmins as $admin): ?>
                        <tr class="hover:bg-bg0/50 transition-colors">
                            <td class="px-4 py-3 text-muted">#<?= htmlspecialchars($admin['id']) ?></td>
                            <td class="px-4 py-3 text-fg">
                                <?= htmlspecialchars($admin['username']) ?>
                                <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                    <span class="ml-2 px-1.5 py-0.5 border border-acc text-acc rounded-full text-[10px] uppercase tracking-widest bg-acc/10">You</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-muted"><?= htmlspecialchars($admin['created_at']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                    <form action="/profile" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to permanently delete this administrator?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                        <button type="submit" class="px-3 py-1 bg-err/10 text-err border border-err/20 hover:bg-err hover:text-bg0 transition-colors rounded-sm text-xs">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted text-[10px] uppercase italic">Cannot delete self</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-4 py-6 text-center text-muted">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
