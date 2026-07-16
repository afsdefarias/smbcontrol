<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Users & Groups</h1>
    <span class="text-sm text-muted font-mono">linux_samba_accounts</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Panel 1: Create User -->
    <div class="bg-bg1 border border-bg0 p-6 flex flex-col h-full rounded-sm">
        <h2 class="text-lg font-ui text-fg mb-4 border-b border-bg0 pb-2">Add / Modify User</h2>
        
        <form action="/samba/users" method="POST" class="flex-grow flex flex-col gap-4 font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_user">
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Username</label>
                <input type="text" name="username" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Samba Password <span class="text-xs opacity-70">(Leave empty if only updating groups)</span></label>
                <input type="password" name="password" placeholder="Required for new users" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            
            <div class="flex flex-col gap-2 mt-2">
                <label class="flex items-center space-x-2 cursor-pointer p-2 border border-bg0 rounded-sm bg-bg0/30 hover:bg-bg0/50 transition">
                    <input type="checkbox" name="create_home" value="1" class="accent-acc w-4 h-4">
                    <span class="text-fg text-xs">Create Home Directory (/home/user)</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer p-2 border border-bg0 rounded-sm bg-bg0/30 hover:bg-bg0/50 transition">
                    <input type="checkbox" name="create_user_group" value="1" class="accent-acc w-4 h-4">
                    <span class="text-fg text-xs">Create User Group (same name as user)</span>
                </label>
            </div>
            
            <div class="flex-grow flex flex-col gap-1 mt-2">
                <label class="text-muted">Associate to Groups (Optional)</label>
                <div class="border border-bg0 rounded-sm p-3 overflow-y-auto max-h-48 flex-grow">
                    <?php if (empty($systemGroups)): ?>
                        <p class="text-muted text-xs">No groups found (GID >= 1000).</p>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($systemGroups as $group): ?>
                                <label class="flex items-center space-x-2 cursor-pointer hover:bg-bg0/50 p-1 rounded-sm transition">
                                    <input type="checkbox" name="groups[]" value="<?= htmlspecialchars($group) ?>" class="accent-acc w-3.5 h-3.5">
                                    <span class="text-fg"><?= htmlspecialchars($group) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pt-4 mt-auto">
                <button type="submit" class="w-full px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">Save User</button>
            </div>
        </form>
    </div>
    
    <!-- Panel 2: Create Group -->
    <div class="bg-bg1 border border-bg0 p-6 flex flex-col h-full rounded-sm">
        <h2 class="text-lg font-ui text-fg mb-4 border-b border-bg0 pb-2">Add Group</h2>
        
        <form action="/samba/users" method="POST" class="flex-grow flex flex-col gap-4 font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_group">
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Group Name</label>
                <input type="text" name="groupname" required placeholder="e.g., directors" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc2 transition-colors">
            </div>
            
            <div class="flex-grow flex flex-col gap-1 mt-2">
                <label class="text-muted">Add Users to this Group (Optional)</label>
                <div class="border border-bg0 rounded-sm p-3 overflow-y-auto max-h-64 flex-grow">
                    <?php if (empty($systemUsers)): ?>
                        <p class="text-muted text-xs">No users found (UID >= 1000).</p>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($systemUsers as $usr): ?>
                                <label class="flex items-center space-x-2 cursor-pointer hover:bg-bg0/50 p-1 rounded-sm transition">
                                    <input type="checkbox" name="users[]" value="<?= htmlspecialchars($usr) ?>" class="accent-acc2 w-3.5 h-3.5">
                                    <span class="text-fg"><?= htmlspecialchars($usr) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pt-4 mt-auto">
                <button type="submit" class="w-full px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs">Create Group</button>
            </div>
        </form>
    </div>
</div>

<!-- Panel 3: Existing Users -->
<div class="mt-6 bg-bg1 border border-bg0 p-6 rounded-sm">
    <h2 class="text-lg font-ui text-fg mb-4 border-b border-bg0 pb-2">Existing Samba Users</h2>
    
    <?php if ($sambaUsers === null): ?>
        <div class="bg-warn/10 border-l-4 border-warn p-4 text-sm font-mono text-warn">
            <strong>Action Required:</strong> The <code>www-data</code> user does not have permission to read Samba users. <br>
            Please add <code>/usr/bin/pdbedit</code> to your sudoers file with NOPASSWD to enable user listing and management.
        </div>
    <?php elseif (empty($sambaUsers)): ?>
        <p class="text-muted text-sm font-mono">No Samba users found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-sm">
                <thead>
                    <tr class="text-muted border-b border-bg0">
                        <th class="pb-2 font-normal">Username</th>
                        <th class="pb-2 font-normal">Groups</th>
                        <th class="pb-2 font-normal">Status</th>
                        <th class="pb-2 font-normal text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bg0/50">
                    <?php foreach ($sambaUsers as $user): ?>
                        <tr class="hover:bg-bg0/20 transition-colors">
                            <td class="py-3 text-fg"><?= htmlspecialchars($user['username']) ?></td>
                            <td class="py-3 text-fg opacity-70 text-xs"><?= htmlspecialchars($user['groups'] ?? '-') ?></td>
                            <td class="py-3">
                                <?php if ($user['disabled']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-err/10 text-err border border-err/20">Disabled</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-acc/10 text-acc border border-acc/20">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <button type="button" 
                                        class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs mr-2"
                                        onclick="
                                            document.querySelector('input[name=username]').value = '<?= htmlspecialchars(addslashes($user['username'])) ?>';
                                            window.scrollTo({top: 0, behavior: 'smooth'});
                                            document.querySelector('input[name=password]').focus();
                                        ">Edit</button>
                                
                                <?php if ($user['disabled']): ?>
                                    <form action="/samba/users" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="enable_user">
                                        <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                        <button type="submit" class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs">Enable</button>
                                    </form>
                                <?php else: ?>
                                    <form action="/samba/users" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="disable_user">
                                        <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                        <button type="submit" class="px-2 py-1 bg-bg0 text-fg border border-bg0 hover:border-err hover:text-err transition-colors rounded-sm text-xs">Disable</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form action="/samba/users" method="POST" class="inline-block" onsubmit="return confirm('WARNING: This will permanently delete the user from Samba and the Linux system. Are you sure?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                    <button type="submit" class="px-2 py-1 bg-err/10 text-err border border-err/20 hover:bg-err hover:text-bg0 transition-colors rounded-sm text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
