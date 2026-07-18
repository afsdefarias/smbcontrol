<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Shares</h1>
    <span class="text-sm text-muted font-mono">smb.conf_shares</span>
</div>

<div class="bg-bg1 rounded-sm border border-bg0 p-6">
    <form action="/samba/shares" method="POST" class="space-y-6 max-w-4xl font-mono text-sm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-1">
                <label class="text-muted">Share Name (Samba)</label>
                <input type="text" name="name" placeholder="e.g., public" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Linux Path</label>
                <input type="text" name="path" placeholder="e.g., /data/public" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
        </div>
        
        <div class="border-t border-bg0 my-4"></div>
        
        <h3 class="text-lg font-ui text-fg mb-4">Linux Ownership (chown)</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-1">
                <label class="text-muted">Owner User</label>
                <select name="owner_user" class="px-3 py-2 text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
                    <?php if (isset($systemUsers)): ?>
                        <?php foreach ($systemUsers as $u): ?>
                            <option value="<?= htmlspecialchars($u) ?>" <?= $u === 'root' ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="root">root</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="flex flex-col gap-1">
                <label class="text-muted">Owner Group</label>
                <select name="owner_group" class="px-3 py-2 text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
                    <?php if (isset($systemGroups)): ?>
                        <?php foreach ($systemGroups as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $g === 'root' ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="root">root</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="border-t border-bg0 my-4"></div>
        
        <h3 class="text-lg font-ui text-fg mb-1">Permission Matrix (Samba)</h3>
        <p class="text-xs text-muted mb-4">Select group network access. Linux base permission will be 0777 so Samba can handle locks natively.</p>
        
        <div class="border border-bg0 rounded-sm overflow-hidden bg-bg0/30 max-h-80 overflow-y-auto">
            <table class="w-full text-left font-mono">
                <thead class="bg-bg0 text-muted sticky top-0 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 font-medium border-b border-bg1">Group</th>
                        <th class="px-3 py-2 font-medium border-b border-bg1 text-center">No Access</th>
                        <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-acc2">Read-Only (RO)</th>
                        <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-ok">Read/Write (RW)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bg0">
                    <?php if (isset($systemGroups) && !empty($systemGroups)): ?>
                        <?php foreach ($systemGroups as $g): ?>
                            <tr class="hover:bg-bg0/50 transition">
                                <td class="px-3 py-2 text-fg"><?= htmlspecialchars($g) ?></td>
                                <td class="px-3 py-2 text-center">
                                    <input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="none" checked class="w-3.5 h-3.5 accent-muted">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="read" class="w-3.5 h-3.5 accent-acc2">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="write" class="w-3.5 h-3.5 accent-ok">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-3 py-4 text-center text-muted">No groups found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pt-2">
            <label class="flex items-center cursor-pointer p-3 border border-bg0 rounded-sm bg-bg0/30 hover:bg-bg0/50 transition w-full">
                <input type="checkbox" name="enable_audit" value="yes" checked class="accent-acc w-4 h-4">
                <div class="ml-3 flex flex-col">
                    <span class="text-sm font-bold text-fg">Enable Audit (vfs_full_audit)</span>
                    <span class="text-xs text-muted">Logs all file accesses, creations, and deletions in this folder.</span>
                </div>
            </label>
        </div>
        
        <div class="pt-4">
            <button type="submit" class="px-6 py-2.5 bg-acc text-bg0 font-medium hover:bg-acc/90 rounded-sm transition uppercase tracking-wider text-xs">
                Create Share
            </button>
        </div>
    </form>
</div>

<!-- Panel 2: Existing Shares -->
<div class="mt-8 bg-bg1 rounded-sm border border-bg0 p-6">
    <h2 class="text-xl font-brand font-bold text-fg mb-4 border-b border-bg0 pb-2">Existing Shares</h2>
    
    <?php if (empty($existingShares)): ?>
        <p class="text-muted text-sm font-mono">No custom shares found in shares.conf.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-sm whitespace-nowrap">
                <thead>
                    <tr class="text-muted border-b border-bg0 text-xs uppercase tracking-wider">
                        <th class="py-3 font-normal px-2">Name</th>
                        <th class="py-3 font-normal px-2">Path</th>
                        <th class="py-3 font-normal px-2">Read Only</th>
                        <th class="py-3 font-normal px-2">Valid Users</th>
                        <th class="py-3 font-normal px-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bg0/50">
                    <?php foreach ($existingShares as $sectionName => $fields): ?>
                        <?php
                            $path = '-';
                            $readOnly = '-';
                            $validUsers = '-';
                            
                            foreach ($fields as $field) {
                                if (isset($field['key'])) {
                                    if ($field['key'] === 'path') $path = $field['value'];
                                    if ($field['key'] === 'read only') $readOnly = $field['value'];
                                    if ($field['key'] === 'valid users') $validUsers = $field['value'];
                                }
                            }
                        ?>
                        <tr class="hover:bg-bg0/20 transition-colors">
                            <td class="py-4 px-2 text-acc font-bold">[<?= htmlspecialchars($sectionName) ?>]</td>
                            <td class="py-4 px-2 text-fg font-mono"><?= htmlspecialchars($path) ?></td>
                            <td class="py-4 px-2 text-fg"><?= htmlspecialchars($readOnly) ?></td>
                            <td class="py-4 px-2 text-fg max-w-[200px] truncate" title="<?= htmlspecialchars($validUsers) ?>"><?= htmlspecialchars($validUsers) ?></td>
                            <td class="py-4 px-2 text-right">
                                <button type="button" 
                                        class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs uppercase tracking-wider font-bold mr-2"
                                        onclick="
                                            document.querySelector('input[name=name]').value = '<?= htmlspecialchars(addslashes($sectionName)) ?>';
                                            document.querySelector('input[name=path]').value = '<?= htmlspecialchars(addslashes($path)) ?>';
                                            window.scrollTo({top: 0, behavior: 'smooth'});
                                        ">Edit</button>
                                
                                <form action="/samba/shares" method="POST" class="inline-block" onsubmit="return confirm('WARNING: Are you sure you want to delete this share?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="share_name" value="<?= htmlspecialchars($sectionName) ?>">
                                    <button type="submit" class="px-2 py-1 bg-bg0 text-err border border-bg0 hover:border-err transition-colors rounded-sm text-xs uppercase tracking-wider font-bold">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
