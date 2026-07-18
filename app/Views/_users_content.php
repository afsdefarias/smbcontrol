<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Users & Groups</h1>
    <span class="text-sm text-muted font-mono">linux_samba_accounts</span>
</div>

<div class="flex gap-4">
    <button onclick="openModal('userWizardModal')" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Create User
    </button>
    <button onclick="openModal('groupWizardModal')" class="px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Create Group
    </button>
</div>

<!-- User Wizard Modal -->
<div id="userWizardModal" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <form action="/samba/users" method="POST" class="flex flex-col h-full font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_user">
            
            <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
                <h2 class="text-lg font-ui text-fg font-bold">User Creation Wizard</h2>
                <button type="button" onclick="closeModal('userWizardModal')" class="text-muted hover:text-err transition">&times;</button>
            </div>
            
            <div class="flex border-b border-bg0 bg-bg0/30">
                <button type="button" class="tab-btn active" data-tab="u-tab-info" onclick="switchTab('userWizardModal', 'u-tab-info')">Basic Info</button>
                <button type="button" class="tab-btn" data-tab="u-tab-groups" onclick="switchTab('userWizardModal', 'u-tab-groups')">Groups</button>
            </div>
            
            <div id="u-tab-info" class="tab-pane active flex flex-col gap-4 min-h-[250px]">
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
            </div>
            
            <div id="u-tab-groups" class="tab-pane hidden flex-col gap-4 min-h-[250px]">
                <label class="text-muted">Associate to Groups (Optional)</label>
                <?php if (empty($systemGroups)): ?>
                    <div class="border border-bg0 rounded-sm p-3 flex-grow">
                        <p class="text-muted text-xs">No groups found (GID >= 1000).</p>
                    </div>
                <?php else: ?>
                    <div class="multi-select-container relative flex-grow" data-placeholder="Search groups..." data-accent="acc">
                        <select multiple name="groups[]" class="hidden">
                            <?php foreach ($systemGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t border-bg0 bg-bg0/50 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeModal('userWizardModal')" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">Save User</button>
            </div>
        </form>
    </div>
</div>

<!-- Group Wizard Modal -->
<div id="groupWizardModal" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <form action="/samba/users" method="POST" class="flex flex-col h-full font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_group">
            
            <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
                <h2 class="text-lg font-ui text-fg font-bold">Group Creation Wizard</h2>
                <button type="button" onclick="closeModal('groupWizardModal')" class="text-muted hover:text-err transition">&times;</button>
            </div>
            
            <div class="flex border-b border-bg0 bg-bg0/30">
                <button type="button" class="tab-btn active" data-tab="g-tab-info" onclick="switchTab('groupWizardModal', 'g-tab-info')">Basic Info</button>
                <button type="button" class="tab-btn" data-tab="g-tab-members" onclick="switchTab('groupWizardModal', 'g-tab-members')">Members</button>
            </div>
            
            <div id="g-tab-info" class="tab-pane active flex flex-col gap-4 min-h-[250px]">
                <div class="flex flex-col gap-1">
                    <label class="text-muted">Group Name</label>
                    <input type="text" name="groupname" required placeholder="e.g., directors" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc2 transition-colors">
                </div>
            </div>
            
            <div id="g-tab-members" class="tab-pane hidden flex-col gap-4 min-h-[250px]">
                <label class="text-muted">Add Users to this Group (Optional)</label>
                <?php if (empty($systemUsers)): ?>
                    <div class="border border-bg0 rounded-sm p-3 flex-grow">
                        <p class="text-muted text-xs">No users found (UID >= 1000).</p>
                    </div>
                <?php else: ?>
                    <div class="multi-select-container relative flex-grow" data-placeholder="Search users..." data-accent="acc2">
                        <select multiple name="users[]" class="hidden">
                            <?php foreach ($systemUsers as $usr): ?>
                                <option value="<?= htmlspecialchars($usr) ?>"><?= htmlspecialchars($usr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t border-bg0 bg-bg0/50 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeModal('groupWizardModal')" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs">Create Group</button>
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
                                            document.querySelector('#userWizardModal input[name=username]').value = '<?= htmlspecialchars(addslashes($user['username'])) ?>';
                                            document.querySelector('#userWizardModal input[name=username]').readOnly = true;
                                            document.querySelector('#userWizardModal input[name=password]').placeholder = 'Leave blank to keep unchanged';
                                            openModal('userWizardModal');
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

<script>
class MultiSelect {
    constructor(container) {
        this.container = container;
        this.select = container.querySelector('select');
        if (!this.select) return;
        this.options = Array.from(this.select.options);
        this.placeholder = container.dataset.placeholder || 'Search...';
        this.accent = container.dataset.accent || 'acc';
        this.selectedValues = new Set();
        
        this.init();
    }
    
    init() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = `border border-bg0 rounded-sm bg-bg0/30 flex flex-wrap gap-1 p-2 min-h-[42px] cursor-text relative transition-colors focus-within:border-${this.accent} h-full content-start`;
        
        this.chipContainer = document.createElement('div');
        this.chipContainer.className = 'flex flex-wrap gap-1.5 items-center w-full';
        
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = this.placeholder;
        this.input.className = 'flex-grow bg-transparent border-none outline-none text-fg text-sm placeholder:text-muted/50 min-w-[120px] p-1 font-mono';
        
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'absolute z-10 w-full left-0 top-full mt-1 bg-bg1 border border-bg0 rounded-sm shadow-xl max-h-48 overflow-y-auto hidden font-mono';
        
        this.chipContainer.appendChild(this.input);
        this.wrapper.appendChild(this.chipContainer);
        this.container.appendChild(this.wrapper);
        this.container.appendChild(this.dropdown);
        
        this.wrapper.addEventListener('click', () => {
            this.input.focus();
            this.showDropdown();
        });
        
        this.input.addEventListener('input', () => this.filterOptions());
        this.input.addEventListener('focus', () => this.showDropdown());
        
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.hideDropdown();
            }
        });
        
        this.options.forEach(opt => {
            if (opt.selected) this.selectedValues.add(opt.value);
        });
        this.renderChips();
        this.renderDropdown();
    }
    
    showDropdown() {
        this.dropdown.classList.remove('hidden');
        this.filterOptions();
    }
    
    hideDropdown() {
        this.dropdown.classList.add('hidden');
        this.input.value = '';
        this.filterOptions();
    }
    
    filterOptions() {
        const query = this.input.value.toLowerCase();
        Array.from(this.dropdown.children).forEach(item => {
            if (!item.dataset.value) return;
            const val = item.dataset.value.toLowerCase();
            if (val.includes(query) && !this.selectedValues.has(item.dataset.value)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    renderDropdown() {
        this.dropdown.innerHTML = '';
        let hasVisible = false;
        this.options.forEach(opt => {
            if (!this.selectedValues.has(opt.value)) {
                const item = document.createElement('div');
                item.className = 'px-3 py-2 cursor-pointer text-sm text-fg hover:bg-bg0/50 transition-colors';
                item.dataset.value = opt.value;
                item.textContent = opt.text;
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectOption(opt.value);
                });
                this.dropdown.appendChild(item);
                hasVisible = true;
            }
        });
        if (!hasVisible) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-sm text-muted italic';
            empty.textContent = 'No more options';
            this.dropdown.appendChild(empty);
        }
    }
    
    selectOption(value) {
        this.selectedValues.add(value);
        Array.from(this.select.options).forEach(opt => {
            if (opt.value === value) opt.selected = true;
        });
        this.input.value = '';
        this.renderChips();
        this.renderDropdown();
        this.input.focus();
    }
    
    deselectOption(value) {
        this.selectedValues.delete(value);
        Array.from(this.select.options).forEach(opt => {
            if (opt.value === value) opt.selected = false;
        });
        this.renderChips();
        this.renderDropdown();
    }
    
    renderChips() {
        Array.from(this.chipContainer.querySelectorAll('.multi-chip')).forEach(c => c.remove());
        
        this.selectedValues.forEach(val => {
            const opt = this.options.find(o => o.value === val);
            if (!opt) return;
            
            const chip = document.createElement('div');
            chip.className = `multi-chip flex items-center gap-1 px-2 py-0.5 bg-${this.accent}/10 border border-${this.accent}/20 text-${this.accent} rounded-sm text-xs font-medium`;
            
            const text = document.createElement('span');
            text.textContent = opt.text;
            
            const close = document.createElement('button');
            close.type = 'button';
            close.innerHTML = '&times;';
            close.className = `hover:text-bg0 hover:bg-${this.accent} rounded-sm w-4 h-4 flex items-center justify-center transition-colors ml-1 leading-none font-bold`;
            close.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deselectOption(val);
            });
            
            chip.appendChild(text);
            chip.appendChild(close);
            this.chipContainer.insertBefore(chip, this.input);
        });
        
        this.input.placeholder = this.selectedValues.size > 0 ? '' : this.placeholder;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.multi-select-container').forEach(el => {
        new MultiSelect(el);
    });
});
</script>
