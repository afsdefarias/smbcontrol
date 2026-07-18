<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Compartilhamentos</h1>
    <span class="text-sm text-muted font-mono">/etc/samba/shares.conf</span>
</div>

<?php if (isset($sharesIncluded) && !$sharesIncluded): ?>
    <div class="mb-6 bg-err/10 border-l-4 border-err p-4 text-sm font-mono text-err">
        O Samba ainda nao esta lendo <code>/etc/samba/shares.conf</code>. Salve ou crie um compartilhamento para o painel inserir o include no <code>/etc/samba/smb.conf</code>.
    </div>
<?php endif; ?>

<div class="mb-6">
    <button onclick="openModal('shareWizardModal')" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Criar compartilhamento
    </button>
</div>

<!-- Share Wizard Modal -->
<div id="shareWizardModal" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <form action="/samba/shares" method="POST" class="flex flex-col h-full font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
                <h2 class="text-lg font-ui text-fg font-bold">Criar ou atualizar compartilhamento</h2>
                <button type="button" onclick="closeModal('shareWizardModal')" class="text-muted hover:text-err transition">&times;</button>
            </div>
            
            <div class="flex border-b border-bg0 bg-bg0/30">
                <button type="button" class="tab-btn active font-sans" data-tab="s-tab-info" onclick="switchTab('shareWizardModal', 's-tab-info')">Pasta e dono</button>
                <button type="button" class="tab-btn font-sans" data-tab="s-tab-perms" onclick="switchTab('shareWizardModal', 's-tab-perms')">Permissoes SMB/ACL</button>
            </div>
            
            <div id="s-tab-info" class="tab-pane active flex flex-col gap-4 min-h-[300px]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm">Nome do share *</label>
                        <input type="text" name="name" placeholder="ex.: documentos" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm">Pasta no Linux</label>
                        <input type="text" name="path" placeholder="ex.: /srv/samba/documentos" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                    </div>
                </div>
                
                <div class="border-t border-bg0 my-2"></div>
                <h3 class="text-md font-ui text-fg">Dono da pasta no Linux (chown)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm">Usuario dono</label>
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
                        <label class="text-muted font-sans text-sm">Grupo dono</label>
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
                
                <div class="space-y-1.5 mt-auto pt-4 text-fg font-sans text-[13px]">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition">
                        <input type="checkbox" name="hide_network" value="1" class="accent-acc w-4 h-4">
                        <span>Nao listar este compartilhamento na navegacao da rede</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition">
                        <input type="checkbox" name="hide_unreadable" value="1" class="accent-acc w-4 h-4">
                        <span>Ocultar arquivos e subpastas sem permissao de leitura</span>
                    </label>
                    <div class="flex flex-col">
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition">
                            <input type="checkbox" name="enable_recycle" value="1" class="accent-acc w-4 h-4" onchange="document.getElementById('recycle_admin').disabled = !this.checked">
                            <span>Ativar lixeira do Samba neste share</span>
                        </label>
                        <div class="ml-7 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer text-muted hover:text-fg transition">
                                <input type="checkbox" name="recycle_admin" id="recycle_admin" value="1" disabled class="accent-acc w-3.5 h-3.5">
                                <span>Restringir a lixeira ao dono/admin</span>
                            </label>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition mt-2">
                        <input type="checkbox" name="enable_audit" value="yes" checked class="accent-acc w-4 h-4">
                        <span class="text-muted">Ativar auditoria full_audit neste share</span>
                    </label>
                </div>
            </div>
            
            <div id="s-tab-perms" class="tab-pane hidden flex-col gap-4 min-h-[300px]">
                <div class="flex justify-between items-center mb-1">
                    <select id="permFilter" onchange="filterPerms()" class="px-3 py-1.5 bg-bg0/50 border border-bg0 rounded-sm text-fg text-sm focus:border-acc outline-none font-sans">
                        <option value="all">Todos</option>
                        <option value="users">Usuarios locais</option>
                        <option value="groups">Grupos locais</option>
                    </select>
                    <div class="relative">
                        <svg class="w-4 h-4 text-muted absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="permSearch" onkeyup="filterPerms()" placeholder="Buscar" class="pl-9 pr-3 py-1.5 bg-bg0/50 border border-bg0 rounded-sm text-fg text-sm focus:border-acc outline-none w-48 font-sans">
                    </div>
                </div>
                
                <div class="border border-bg0 rounded-sm overflow-hidden bg-bg0/30 flex-grow max-h-[350px] overflow-y-auto">
                    <table class="w-full text-left font-mono" id="permsTable">
                        <thead class="bg-bg0 text-muted sticky top-0 text-xs tracking-wider z-10 font-sans">
                            <tr>
                                <th class="px-3 py-2 font-medium border-b border-bg1">Usuario/Grupo</th>
                                <th class="px-3 py-2 font-medium border-b border-bg1 text-center">Sem acesso</th>
                                <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-ok">Leitura/Escrita</th>
                                <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-acc2">Somente leitura</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bg0 text-sm">
                            <?php if (isset($systemUsers) && !empty($systemUsers)): ?>
                                <?php foreach ($systemUsers as $u): ?>
                                    <tr class="hover:bg-bg0/50 transition perm-row" data-type="users" data-name="<?= htmlspecialchars(strtolower($u)) ?>">
                                        <td class="px-3 py-2 text-fg flex items-center gap-2">
                                            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <?= htmlspecialchars($u) ?>
                                        </td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="none" checked class="w-3.5 h-3.5 accent-muted"></td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="write" class="w-3.5 h-3.5 accent-ok"></td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="read" class="w-3.5 h-3.5 accent-acc2"></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (isset($systemGroups) && !empty($systemGroups)): ?>
                                <?php foreach ($systemGroups as $g): ?>
                                    <tr class="hover:bg-bg0/50 transition bg-bg0/10 perm-row" data-type="groups" data-name="<?= htmlspecialchars(strtolower($g)) ?>">
                                        <td class="px-3 py-2 text-fg flex items-center gap-2">
                                            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            <?= htmlspecialchars($g) ?>
                                        </td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="none" checked class="w-3.5 h-3.5 accent-muted"></td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="write" class="w-3.5 h-3.5 accent-ok"></td>
                                        <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="read" class="w-3.5 h-3.5 accent-acc2"></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="p-4 border-t border-bg0 bg-bg0/50 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeModal('shareWizardModal')" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-3xl font-sans tracking-wide text-sm font-medium uppercase">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-acc text-bg0 hover:bg-acc/90 transition-colors rounded-3xl font-sans tracking-wide text-sm font-medium uppercase">Salvar e aplicar</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterPerms() {
    const filter = document.getElementById('permFilter').value;
    const search = document.getElementById('permSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.perm-row');
    
    rows.forEach(row => {
        const type = row.dataset.type;
        const name = row.dataset.name;
        
        const matchesFilter = filter === 'all' || type === filter;
        const matchesSearch = name.includes(search);
        
        if (matchesFilter && matchesSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function editShare(data) {
    const modal = document.querySelector('#shareWizardModal');
    
    // Basic Info
    modal.querySelector('input[name=name]').value = data.name;
    modal.querySelector('input[name=name]').readOnly = true;
    modal.querySelector('input[name=path]').value = data.path;
    
    modal.querySelector('input[name=hide_network]').checked = (data.browseable === 'no');
    modal.querySelector('input[name=hide_unreadable]').checked = (data.hide_unreadable === 'yes');
    
    const vfs = data.vfs_objects || '';
    const hasRecycle = vfs.includes('recycle');
    const chkRecycle = modal.querySelector('input[name=enable_recycle]');
    chkRecycle.checked = hasRecycle;
    
    const chkRecycleAdmin = modal.querySelector('input[name=recycle_admin]');
    chkRecycleAdmin.disabled = !hasRecycle;
    chkRecycleAdmin.checked = (data.recycle_directory_mode === '0700');
    
    modal.querySelector('input[name=enable_audit]').checked = vfs.includes('full_audit');
    
    if(data.force_group) {
        const selGroup = modal.querySelector('select[name=owner_group]');
        if(selGroup) selGroup.value = data.force_group;
    }
    if(data.force_user) {
        const selUser = modal.querySelector('select[name=owner_user]');
        if(selUser) selUser.value = data.force_user;
    }
    
    // Permissions
    // Reset all to 'none' first
    document.querySelectorAll('input[name^="user_perms"][value="none"]').forEach(r => r.checked = true);
    document.querySelectorAll('input[name^="group_perms"][value="none"]').forEach(r => r.checked = true);
    
    const readList = (data.read_list || '').split(',').map(s => s.trim()).filter(s => s);
    const writeList = (data.write_list || '').split(',').map(s => s.trim()).filter(s => s);
    
    const applyPerm = (list, val) => {
        list.forEach(item => {
            if (item.startsWith('@')) {
                const grp = item.substring(1);
                const radio = document.querySelector(`input[name="group_perms[${grp}]"][value="${val}"]`);
                if(radio) radio.checked = true;
            } else {
                const radio = document.querySelector(`input[name="user_perms[${item}]"][value="${val}"]`);
                if(radio) radio.checked = true;
            }
        });
    };
    
    applyPerm(readList, 'read');
    applyPerm(writeList, 'write');
    
    // Switch to Basic Info tab
    switchTab('shareWizardModal', 's-tab-info');
    openModal('shareWizardModal');
}
</script>

<!-- Panel 2: Existing Shares -->
<div class="mt-8 bg-bg1 rounded-sm border border-bg0 p-6">
    <h2 class="text-xl font-brand font-bold text-fg mb-4 border-b border-bg0 pb-2">Compartilhamentos em shares.conf</h2>
    
    <?php if (empty($existingShares)): ?>
        <p class="text-muted text-sm font-mono">Nenhum compartilhamento personalizado encontrado em /etc/samba/shares.conf.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-sm whitespace-nowrap">
                <thead>
                    <tr class="text-muted border-b border-bg0 text-xs uppercase tracking-wider">
                        <th class="py-3 font-normal px-2">Nome</th>
                        <th class="py-3 font-normal px-2">Pasta</th>
                        <th class="py-3 font-normal px-2">Somente leitura</th>
                        <th class="py-3 font-normal px-2">Usuarios permitidos</th>
                        <th class="py-3 font-normal px-2 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bg0/50">
                    <?php foreach ($existingShares as $sectionName => $fields): ?>
                        <?php
                            $shareData = [
                                'name' => $sectionName,
                                'path' => '',
                                'browseable' => '',
                                'hide_unreadable' => '',
                                'vfs_objects' => '',
                                'recycle_directory_mode' => '',
                                'force_group' => '',
                                'force_user' => '',
                                'read_list' => '',
                                'write_list' => '',
                                'read_only' => ''
                            ];
                            
                            foreach ($fields as $field) {
                                if (isset($field['key'])) {
                                    $k = trim($field['key']);
                                    $v = trim($field['value']);
                                    if ($k === 'path') $shareData['path'] = $v;
                                    if ($k === 'browseable') $shareData['browseable'] = $v;
                                    if ($k === 'hide unreadable') $shareData['hide_unreadable'] = $v;
                                    if ($k === 'vfs objects') $shareData['vfs_objects'] = $v;
                                    if ($k === 'recycle:directory_mode') $shareData['recycle_directory_mode'] = $v;
                                    if ($k === 'force group') $shareData['force_group'] = $v;
                                    if ($k === 'force user') $shareData['force_user'] = $v;
                                    if ($k === 'read list') $shareData['read_list'] = $v;
                                    if ($k === 'write list') $shareData['write_list'] = $v;
                                    if ($k === 'read only') $shareData['read_only'] = $v;
                                }
                            }
                            $validUsersDisplay = $shareData['read_list'] . ($shareData['read_list'] && $shareData['write_list'] ? ', ' : '') . $shareData['write_list'];
                            if (empty($validUsersDisplay) && $shareData['read_only'] === 'no') {
                                $validUsersDisplay = 'Todos (Leitura/Gravação)';
                            }
                        ?>
                        <tr class="hover:bg-bg0/20 transition-colors">
                            <td class="py-4 px-2 text-acc font-bold">[<?= htmlspecialchars($sectionName) ?>]</td>
                            <td class="py-4 px-2 text-fg font-mono"><?= htmlspecialchars($shareData['path']) ?></td>
                            <td class="py-4 px-2 text-fg"><?= htmlspecialchars($shareData['read_only'] ?: '-') ?></td>
                            <td class="py-4 px-2 text-fg max-w-[200px] truncate" title="<?= htmlspecialchars($validUsersDisplay) ?>"><?= htmlspecialchars($validUsersDisplay) ?></td>
                            <td class="py-4 px-2 text-right">
                                <button type="button" 
                                        class="px-2 py-1 bg-acc/10 text-acc border border-acc/20 hover:bg-acc hover:text-bg0 transition-colors rounded-sm text-xs uppercase tracking-wider font-bold mr-2"
                                        onclick='editShare(<?= json_encode($shareData) ?>)'>Edit</button>
                                
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
