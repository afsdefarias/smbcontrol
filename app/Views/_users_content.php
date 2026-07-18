<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg"><?= smb_t('SMB Users and Groups', 'Usuários e grupos SMB') ?></h1>
    <span class="text-sm text-muted font-mono"><?= smb_t('Linux users + Samba password database', 'Usuários Linux + banco de senhas Samba') ?></span>
</div>

<div class="flex gap-4">
    <button onclick="openModal('userWizardModal')" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <?= smb_t('Create SMB User', 'Criar usuário SMB') ?>
    </button>
    <button onclick="openGroupManager()" class="px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <?= smb_t('Manage Groups', 'Gerenciar grupos') ?>
    </button>
</div>

<!-- User Wizard Modal -->
<div id="userWizardModal" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <form action="/samba/users" method="POST" class="flex flex-col h-full font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create_user">
            
            <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
                <h2 class="text-lg font-ui text-fg font-bold"><?= smb_t('Create or update Samba user', 'Criar ou atualizar usuário Samba') ?></h2>
                <button type="button" onclick="closeModal('userWizardModal')" class="text-muted hover:text-err transition">&times;</button>
            </div>
            
            <div class="flex border-b border-bg0 bg-bg0/30">
                <button type="button" class="tab-btn active" data-tab="u-tab-info" onclick="switchTab('userWizardModal', 'u-tab-info')"><?= smb_t('Account and password', 'Conta e senha') ?></button>
                <button type="button" class="tab-btn" data-tab="u-tab-groups" onclick="switchTab('userWizardModal', 'u-tab-groups')"><?= smb_t('Groups', 'Grupos') ?></button>
            </div>
            
            <div id="u-tab-info" class="tab-pane active flex flex-col gap-4 min-h-[250px]">
                <div class="flex flex-col gap-1">
                    <label class="text-muted"><?= smb_t('Linux/Samba username', 'Usuário Linux/Samba') ?></label>
                    <input type="text" name="username" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-muted"><?= smb_t('Samba password', 'Senha Samba') ?> <span class="text-xs opacity-70"><?= smb_t('(required when creating, optional when editing)', '(obrigatória ao criar, opcional ao editar)') ?></span></label>
                    <input type="password" name="password" placeholder="<?= htmlspecialchars(smb_t('Password used to access SMB shares', 'Senha usada para acessar compartilhamentos SMB')) ?>" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                </div>
	                <div class="flex flex-col gap-2 mt-2">
	                    <label class="flex items-center space-x-2 cursor-pointer p-2 border border-bg0 rounded-sm bg-bg0/30 hover:bg-bg0/50 transition">
	                        <input type="checkbox" name="create_home" value="1" class="check-square">
	                        <span class="text-fg text-xs"><?= smb_t('Create Linux home directory (/home/user)', 'Criar pasta home no Linux (/home/usuário)') ?></span>
	                    </label>
	                    <label class="flex items-center space-x-2 cursor-pointer p-2 border border-bg0 rounded-sm bg-bg0/30 hover:bg-bg0/50 transition">
	                        <input type="checkbox" name="create_user_group" value="1" class="check-square">
	                        <span class="text-fg text-xs"><?= smb_t('Create primary group with the same name as the user', 'Criar grupo principal com o mesmo nome do usuário') ?></span>
	                    </label>
                </div>
            </div>
            
            <div id="u-tab-groups" class="tab-pane hidden flex-col gap-4 min-h-[250px]">
                <label class="text-muted"><?= smb_t('Add to selected Linux groups (optional)', 'Adicionar aos grupos Linux selecionados (opcional)') ?></label>
                <?php if (empty($systemGroups)): ?>
                    <div class="border border-bg0 rounded-sm p-3 flex-grow">
                        <p class="text-muted text-xs"><?= smb_t('No local groups found (GID >= 1000).', 'Nenhum grupo local encontrado (GID >= 1000).') ?></p>
                    </div>
                <?php else: ?>
                    <div class="multi-select-container relative flex-grow" data-placeholder="<?= htmlspecialchars(smb_t('Search groups...', 'Buscar grupos...')) ?>" data-accent="acc">
                        <select multiple name="groups[]" class="hidden">
                            <?php foreach ($systemGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t border-bg0 bg-bg0/50 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeModal('userWizardModal')" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs"><?= smb_t('Cancel', 'Cancelar') ?></button>
                <button type="submit" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs"><?= smb_t('Save User', 'Salvar usuário') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Group Manager Modal -->
<div id="groupWizardModal" class="modal-overlay">
    <div class="modal-content max-w-6xl" onclick="event.stopPropagation()">
        <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
            <h2 class="text-lg font-ui text-fg font-bold"><?= smb_t('Manage groups', 'Gerenciar grupos') ?></h2>
            <button type="button" onclick="closeModal('groupWizardModal')" class="text-muted hover:text-err transition">&times;</button>
        </div>

        <div class="p-5 grid grid-cols-1 xl:grid-cols-[360px_1fr] gap-5 font-mono text-sm overflow-y-auto">
            <form id="groupForm" action="/samba/users" method="POST" class="bg-bg0/30 border border-bg0 rounded-sm p-4 flex flex-col gap-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="action" value="create_group">

                <div class="flex items-center justify-between gap-3 border-b border-bg0 pb-3">
                    <h3 id="groupFormTitle" class="text-fg font-ui font-bold"><?= smb_t('Create department group', 'Criar grupo de setor') ?></h3>
                    <button type="button" onclick="resetGroupForm()" class="px-2 py-1 bg-bg0 text-muted hover:text-acc border border-bg0 rounded-sm text-xs uppercase tracking-wider">
                        <?= smb_t('New', 'Novo') ?>
                    </button>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-muted"><?= smb_t('Group name', 'Nome do grupo') ?></label>
                    <input type="text" name="groupname" required placeholder="<?= htmlspecialchars(smb_t('e.g. finance', 'ex.: financeiro')) ?>" class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc2 transition-colors">
                </div>

                <div class="flex flex-col gap-2 min-h-[230px]">
                    <label class="text-muted"><?= smb_t('Members', 'Membros') ?></label>
                    <?php if (empty($systemUsers)): ?>
                        <div class="border border-bg0 rounded-sm p-3 flex-grow">
                            <p class="text-muted text-xs"><?= smb_t('No local users found (UID >= 1000).', 'Nenhum usuário local encontrado (UID >= 1000).') ?></p>
                        </div>
                    <?php else: ?>
                        <div id="groupMembersSelect" class="multi-select-container relative flex-grow" data-placeholder="<?= htmlspecialchars(smb_t('Search users...', 'Buscar usuários...')) ?>" data-accent="acc2">
                            <select multiple name="users[]" class="hidden">
                                <?php foreach ($systemUsers as $usr): ?>
                                    <option value="<?= htmlspecialchars($usr) ?>"><?= htmlspecialchars($usr) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="px-4 py-2 bg-acc2 text-bg0 font-medium hover:bg-acc2/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    <?= smb_t('Save Group', 'Salvar grupo') ?>
                </button>
            </form>

            <div class="border border-bg0 rounded-sm overflow-hidden bg-bg0/20">
                <table class="w-full text-left text-xs">
                    <thead class="text-muted border-b border-bg0 bg-bg0/50 uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2 font-medium"><?= smb_t('Group', 'Grupo') ?></th>
                            <th class="px-3 py-2 font-medium"><?= smb_t('Members', 'Membros') ?></th>
                            <th class="px-3 py-2 font-medium"><?= smb_t('Share access', 'Acesso aos shares') ?></th>
                            <th class="px-3 py-2 font-medium text-right"><?= smb_t('Actions', 'Ações') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bg0/60">
                        <?php if (empty($groupRecords ?? [])): ?>
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-muted italic"><?= smb_t('No groups found.', 'Nenhum grupo encontrado.') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groupRecords as $group): ?>
                                <?php
                                    $members = $group['members'] ?? [];
                                    $accessRows = $groupShareAccess[$group['name']] ?? [];
                                ?>
                                <tr class="hover:bg-bg0/40 transition-colors">
                                    <td class="px-3 py-3 text-fg">
                                        <div class="font-bold"><?= htmlspecialchars($group['name']) ?></div>
                                        <?php if (!$group['manageable']): ?>
                                            <div class="mt-1 text-[10px] text-muted uppercase tracking-wider"><?= smb_t('System group', 'Grupo do sistema') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-muted max-w-[220px]">
                                        <?= !empty($members) ? htmlspecialchars(implode(', ', $members)) : '-' ?>
                                    </td>
                                    <td class="px-3 py-3">
                                        <?php if (empty($accessRows)): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <div class="flex flex-wrap gap-1.5">
                                                <?php foreach ($accessRows as $access): ?>
                                                    <span class="px-2 py-0.5 border <?= $access['permission'] === 'write' ? 'border-ok/30 text-ok bg-ok/10' : 'border-acc2/30 text-acc2 bg-acc2/10' ?> rounded-sm">
                                                        <?= htmlspecialchars($access['share']) ?>: <?= $access['permission'] === 'write' ? smb_t('Read/Write', 'Leitura/Escrita') : smb_t('Read only', 'Somente leitura') ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap">
                                        <?php if ($group['manageable']): ?>
                                            <button type="button" class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs mr-2" onclick='editGroup(<?= json_encode($group['name']) ?>, <?= json_encode($members) ?>)'>
                                                <?= smb_t('Edit', 'Editar') ?>
                                            </button>
                                            <form action="/samba/users" method="POST" class="inline-block" onsubmit="return confirm('<?= htmlspecialchars(smb_t('Delete this group? Users will not be deleted.', 'Excluir este grupo? Os usuários não serão excluídos.')) ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="action" value="delete_group">
                                                <input type="hidden" name="groupname" value="<?= htmlspecialchars($group['name']) ?>">
                                                <button type="submit" class="px-2 py-1 bg-err/10 text-err border border-err/20 hover:bg-err hover:text-bg0 transition-colors rounded-sm text-xs">
                                                    <?= smb_t('Delete', 'Excluir') ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted text-[10px] uppercase"><?= smb_t('Protected', 'Protegido') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Panel 3: Existing Users -->
<div class="mt-6 bg-bg1 border border-bg0 p-6 rounded-sm">
    <h2 class="text-lg font-ui text-fg mb-4 border-b border-bg0 pb-2"><?= smb_t('Samba users', 'Usuários cadastrados no Samba') ?></h2>
    
    <?php if ($sambaUsers === null): ?>
        <div class="bg-warn/10 border-l-4 border-warn p-4 text-sm font-mono text-warn">
            <strong><?= smb_t('Action required:', 'Ação necessária:') ?></strong> <?= smb_t('the', 'o usuário') ?> <code>www-data</code> <?= smb_t('user cannot read Samba users.', 'não tem permissão para ler usuários Samba.') ?><br>
            <?= smb_t('Add', 'Adicione') ?> <code>/usr/bin/pdbedit</code> <?= smb_t('to sudoers with NOPASSWD to list and manage users.', 'ao sudoers com NOPASSWD para listar e gerenciar usuários.') ?>
        </div>
    <?php elseif (empty($sambaUsers)): ?>
        <p class="text-muted text-sm font-mono"><?= smb_t('No Samba users found.', 'Nenhum usuário Samba encontrado.') ?></p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-sm">
	                <thead>
	                    <tr class="text-muted border-b border-bg0">
	                        <th class="pb-2 font-normal"><?= smb_t('User', 'Usuário') ?></th>
	                        <th class="pb-2 font-normal"><?= smb_t('Group', 'Grupo') ?></th>
	                        <th class="pb-2 font-normal"><?= smb_t('Samba Status', 'Status Samba') ?></th>
	                        <th class="pb-2 font-normal"><?= smb_t('Activities', 'Atividades') ?></th>
	                        <th class="pb-2 font-normal"><?= smb_t('Last Online', 'Última vez online') ?></th>
	                        <th class="pb-2 font-normal text-right"><?= smb_t('Actions', 'Ações') ?></th>
	                    </tr>
	                </thead>
                <tbody class="divide-y divide-bg0/50">
                    <?php foreach ($sambaUsers as $user): ?>
	                        <tr class="hover:bg-bg0/20 transition-colors">
	                            <td class="py-3 text-fg"><?= htmlspecialchars($user['username']) ?></td>
	                            <td class="py-3 text-fg opacity-70 text-xs"><?= htmlspecialchars($user['groups'] ?? '-') ?></td>
	                            <td class="py-3">
	                                <?php if ($user['disabled']): ?>
	                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-err/10 text-err border border-err/20"><?= smb_t('Disabled', 'Desativado') ?></span>
	                                <?php else: ?>
	                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-acc/10 text-acc border border-acc/20"><?= smb_t('Active', 'Ativo') ?></span>
	                                <?php endif; ?>
	                            </td>
	                            <td class="py-3 text-fg">
	                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-bg0/60 border border-bg0">
	                                    <?= (int)($user['activities'] ?? 0) ?>
	                                </span>
	                            </td>
	                            <td class="py-3 text-muted text-xs">
	                                <?= !empty($user['last_seen']) ? htmlspecialchars($user['last_seen']) : smb_t('Never', 'Nunca') ?>
	                            </td>
	                            <td class="py-3 text-right space-x-2">
                                <button type="button" 
                                        class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs mr-2"
                                        onclick="
                                            document.querySelector('#userWizardModal input[name=username]').value = '<?= htmlspecialchars(addslashes($user['username'])) ?>';
                                            document.querySelector('#userWizardModal input[name=username]').readOnly = true;
                                            document.querySelector('#userWizardModal input[name=password]').placeholder = 'Leave blank to keep unchanged';
                                            openModal('userWizardModal');
                                        "><?= smb_t('Edit', 'Editar') ?></button>
                                
                                <?php if ($user['disabled']): ?>
                                    <form action="/samba/users" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="enable_user">
                                        <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                        <button type="submit" class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs"><?= smb_t('Enable', 'Ativar') ?></button>
                                    </form>
                                <?php else: ?>
                                    <form action="/samba/users" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="disable_user">
                                        <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                        <button type="submit" class="px-2 py-1 bg-bg0 text-fg border border-bg0 hover:border-err hover:text-err transition-colors rounded-sm text-xs"><?= smb_t('Disable', 'Desativar') ?></button>
                                    </form>
                                <?php endif; ?>
                                
                                <form action="/samba/users" method="POST" class="inline-block" onsubmit="return confirm('<?= htmlspecialchars(smb_t('WARNING: This will permanently delete the user from Samba and the Linux system. Are you sure?', 'AVISO: isso apagará permanentemente o usuário do Samba e do Linux. Tem certeza?')) ?>');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user" value="<?= htmlspecialchars($user['username']) ?>">
                                    <button type="submit" class="px-2 py-1 bg-err/10 text-err border border-err/20 hover:bg-err hover:text-bg0 transition-colors rounded-sm text-xs"><?= smb_t('Delete', 'Excluir') ?></button>
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
            empty.textContent = '<?= addslashes(smb_t('No more options', 'Sem mais opções')) ?>';
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

    setSelected(values) {
        this.selectedValues = new Set(values);
        Array.from(this.select.options).forEach(opt => {
            opt.selected = this.selectedValues.has(opt.value);
        });
        this.input.value = '';
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
        el.multiSelect = new MultiSelect(el);
    });
});

function openGroupManager() {
    resetGroupForm();
    openModal('groupWizardModal');
}

function resetGroupForm() {
    const form = document.getElementById('groupForm');
    if (!form) return;
    form.reset();
    form.querySelector('input[name=groupname]').readOnly = false;
    document.getElementById('groupFormTitle').textContent = <?= json_encode(smb_t('Create department group', 'Criar grupo de setor')) ?>;
    document.getElementById('groupMembersSelect')?.multiSelect?.setSelected([]);
}

function editGroup(groupName, members) {
    const form = document.getElementById('groupForm');
    if (!form) return;
    const nameInput = form.querySelector('input[name=groupname]');
    nameInput.value = groupName;
    nameInput.readOnly = true;
    document.getElementById('groupFormTitle').textContent = <?= json_encode(smb_t('Edit group members', 'Editar membros do grupo')) ?>;
    document.getElementById('groupMembersSelect')?.multiSelect?.setSelected(members || []);
}
</script>
