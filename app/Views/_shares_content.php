<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg"><?= smb_t('Shares', 'Compartilhamentos') ?></h1>
    <span class="text-sm text-muted font-mono">/etc/samba/shares.conf</span>
</div>

<?php if (isset($sharesIncluded) && !$sharesIncluded): ?>
    <div class="mb-6 bg-err/10 border-l-4 border-err p-4 text-sm font-mono text-err">
        <?= smb_t('Samba is not reading', 'O Samba ainda não está lendo') ?> <code>/etc/samba/shares.conf</code>. <?= smb_t('Save or create a share so the panel can add the include to', 'Salve ou crie um compartilhamento para o painel inserir o include em') ?> <code>/etc/samba/smb.conf</code>.
    </div>
<?php endif; ?>

<div class="mb-6">
    <button id="createShareButton" onclick="openModal('shareWizardModal')" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <?= smb_t('Create Share', 'Criar compartilhamento') ?>
    </button>
</div>

<!-- Share Wizard Modal -->
<div id="shareWizardModal" class="modal-overlay">
    <div class="modal-content" onclick="event.stopPropagation()">
        <form action="/samba/shares" method="POST" class="flex flex-col h-full font-mono text-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
                <h2 class="text-lg font-ui text-fg font-bold"><?= smb_t('Create or update share', 'Criar ou atualizar compartilhamento') ?></h2>
                <button type="button" onclick="closeModal('shareWizardModal')" class="text-muted hover:text-err transition">&times;</button>
            </div>
            
            <div class="flex border-b border-bg0 bg-bg0/30">
                <button type="button" class="tab-btn active font-sans" data-tab="s-tab-info" onclick="switchTab('shareWizardModal', 's-tab-info')"><?= smb_t('Path and owner', 'Pasta e dono') ?></button>
                <button type="button" class="tab-btn font-sans" data-tab="s-tab-perms" onclick="switchTab('shareWizardModal', 's-tab-perms')"><?= smb_t('SMB/ACL Permissions', 'Permissões SMB/ACL') ?></button>
            </div>
            
            <div id="s-tab-info" class="tab-pane active flex flex-col gap-4 min-h-[300px]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm"><?= smb_t('Share name *', 'Nome do share *') ?></label>
                        <input type="text" name="name" placeholder="<?= htmlspecialchars(smb_t('e.g. documents', 'ex.: documentos')) ?>" required class="px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm"><?= smb_t('Linux path', 'Pasta no Linux') ?></label>
                        <div class="flex rounded-sm border border-bg0 bg-bg0 focus-within:border-acc focus-within:shadow-[0_0_0_1px_var(--acc)] transition-colors">
                            <input type="text" name="path" value="/srv/samba/" placeholder="<?= htmlspecialchars(smb_t('e.g. /srv/samba/documents', 'ex.: /srv/samba/documentos')) ?>" required class="min-w-0 flex-1 px-3 py-2 text-fg placeholder:text-muted/50 border-0 focus:shadow-none">
                            <button type="button" onclick="openPathBrowser()" class="w-11 flex items-center justify-center border-l border-bg1 bg-bg0 text-muted hover:text-acc hover:bg-acc/10 transition-colors" title="<?= htmlspecialchars(smb_t('Browse folders', 'Navegar pelas pastas')) ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h6l2 2h10v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-bg0 my-2"></div>
                <h3 class="text-md font-ui text-fg"><?= smb_t('Linux folder ownership (chown)', 'Dono da pasta no Linux (chown)') ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-muted font-sans text-sm"><?= smb_t('Owner user', 'Usuário dono') ?></label>
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
                        <label class="text-muted font-sans text-sm"><?= smb_t('Owner group', 'Grupo dono') ?></label>
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
                        <input type="checkbox" name="hide_network" value="1" class="check-square">
                        <span><?= smb_t('Do not show this share in network browsing', 'Não listar este compartilhamento na navegação da rede') ?></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition">
                        <input type="checkbox" name="hide_unreadable" value="1" class="check-square">
                        <span><?= smb_t('Hide files and subfolders without read permission', 'Ocultar arquivos e subpastas sem permissão de leitura') ?></span>
                    </label>
                    <div class="flex flex-col">
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition">
                            <input type="checkbox" name="enable_recycle" value="1" class="check-square" onchange="document.getElementById('recycle_admin').disabled = !this.checked">
                            <span><?= smb_t('Enable Samba recycle bin on this share', 'Ativar lixeira do Samba neste share') ?></span>
                        </label>
                        <div class="ml-7 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer text-muted hover:text-fg transition">
                                <input type="checkbox" name="recycle_admin" id="recycle_admin" value="1" disabled class="check-square">
                                <span><?= smb_t('Restrict recycle bin to owner/admin', 'Restringir a lixeira ao dono/admin') ?></span>
                            </label>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-bg0/30 p-1 rounded-sm transition mt-2">
                        <input type="checkbox" name="enable_audit" value="yes" checked class="check-square">
                        <span class="text-muted"><?= smb_t('Enable full_audit on this share', 'Ativar auditoria full_audit neste share') ?></span>
                    </label>
                </div>
            </div>
            
            <div id="s-tab-perms" class="tab-pane hidden flex-col gap-4 min-h-[300px]">
                <div class="border border-bg0 rounded-sm bg-bg0/30 p-3 font-sans">
                    <div class="text-xs uppercase tracking-wider text-muted mb-2"><?= smb_t('Access mode', 'Modo de acesso') ?></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label class="flex items-start gap-3 border border-bg0 rounded-sm p-3 cursor-pointer hover:border-acc transition-colors">
                            <input type="radio" name="access_mode" value="authenticated" checked class="permission-choice mt-0.5" onchange="toggleShareAccessMode()">
                            <span>
                                <span class="block text-fg font-medium"><?= smb_t('Login and password', 'Login e senha') ?></span>
                                <span class="block text-xs text-muted mt-1"><?= smb_t('Only selected users and groups can access this share.', 'Somente usuários e grupos selecionados acessam este compartilhamento.') ?></span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 border border-bg0 rounded-sm p-3 cursor-pointer hover:border-acc transition-colors">
                            <input type="radio" name="access_mode" value="anonymous" class="permission-choice mt-0.5" onchange="toggleShareAccessMode()">
                            <span>
                                <span class="block text-fg font-medium"><?= smb_t('Anonymous access', 'Acesso anônimo') ?></span>
                                <span class="block text-xs text-muted mt-1"><?= smb_t('No SMB username or password is required for this share.', 'Não exige usuário nem senha SMB para este compartilhamento.') ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <div id="authenticatedPermissions" class="flex flex-col gap-4">
                    <div class="flex justify-between items-center mb-1">
                        <select id="permFilter" onchange="filterPerms()" class="px-3 py-1.5 bg-bg0/50 border border-bg0 rounded-sm text-fg text-sm focus:border-acc outline-none font-sans">
                            <option value="all"><?= smb_t('All', 'Todos') ?></option>
                            <option value="users"><?= smb_t('Local users', 'Usuários locais') ?></option>
                            <option value="groups"><?= smb_t('Local groups', 'Grupos locais') ?></option>
                        </select>
                        <div class="relative">
                            <svg class="w-4 h-4 text-muted absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" id="permSearch" onkeyup="filterPerms()" placeholder="<?= htmlspecialchars(smb_t('Search', 'Buscar')) ?>" class="pl-9 pr-3 py-1.5 bg-bg0/50 border border-bg0 rounded-sm text-fg text-sm focus:border-acc outline-none w-48 font-sans">
                        </div>
                    </div>
                
                    <div class="border border-bg0 rounded-sm overflow-hidden bg-bg0/30 flex-grow max-h-[350px] overflow-y-auto">
                        <table class="w-full text-left font-mono" id="permsTable">
                            <thead class="bg-bg0 text-muted sticky top-0 text-xs tracking-wider z-10 font-sans">
                                <tr>
                                    <th class="px-3 py-2 font-medium border-b border-bg1"><?= smb_t('User/Group', 'Usuário/Grupo') ?></th>
                                    <th class="px-3 py-2 font-medium border-b border-bg1 text-center"><?= smb_t('No access', 'Sem acesso') ?></th>
                                    <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-ok"><?= smb_t('Read/Write', 'Leitura/Escrita') ?></th>
                                    <th class="px-3 py-2 font-medium border-b border-bg1 text-center text-acc2"><?= smb_t('Read only', 'Somente leitura') ?></th>
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
                                            <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="none" <?= $u === 'root' ? '' : 'checked' ?> class="permission-choice"></td>
                                            <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="write" <?= $u === 'root' ? 'checked' : '' ?> class="permission-choice"></td>
                                            <td class="px-3 py-2 text-center"><input type="radio" name="user_perms[<?= htmlspecialchars($u) ?>]" value="read" class="permission-choice"></td>
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
                                            <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="none" <?= $g === 'root' ? '' : 'checked' ?> class="permission-choice"></td>
                                            <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="write" <?= $g === 'root' ? 'checked' : '' ?> class="permission-choice"></td>
                                            <td class="px-3 py-2 text-center"><input type="radio" name="group_perms[<?= htmlspecialchars($g) ?>]" value="read" class="permission-choice"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-t border-bg0 bg-bg0/50 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeModal('shareWizardModal')" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-3xl font-sans tracking-wide text-sm font-medium uppercase"><?= smb_t('Cancel', 'Cancelar') ?></button>
                <button type="submit" class="px-5 py-2 bg-acc text-bg0 hover:bg-acc/90 transition-colors rounded-3xl font-sans tracking-wide text-sm font-medium uppercase"><?= smb_t('Save and Apply', 'Salvar e aplicar') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Folder Browser Modal -->
<div id="pathBrowserModal" class="modal-overlay">
    <div class="modal-content max-w-2xl" onclick="event.stopPropagation()">
        <div class="p-4 border-b border-bg0 bg-bg0/50 flex justify-between items-center">
            <h2 class="text-lg font-ui text-fg font-bold"><?= smb_t('Select Linux folder', 'Selecionar pasta Linux') ?></h2>
            <button type="button" onclick="closeModal('pathBrowserModal')" class="text-muted hover:text-err transition">&times;</button>
        </div>

        <div class="p-5 flex flex-col gap-4 font-mono text-sm">
            <div class="flex items-center gap-2">
                <button type="button" onclick="pathBrowserGoUp()" class="w-9 h-9 flex items-center justify-center border border-bg0 bg-bg0 text-muted hover:text-acc hover:border-acc rounded-sm transition" title="<?= htmlspecialchars(smb_t('Go up', 'Subir')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </button>
                <div id="pathBrowserCurrent" class="min-w-0 flex-1 px-3 py-2 bg-bg0 border border-bg0 rounded-sm text-fg truncate">/srv/samba</div>
                <button type="button" onclick="selectCurrentPath()" class="px-4 py-2 bg-acc text-bg0 hover:bg-acc/90 rounded-sm transition uppercase tracking-wider text-xs font-medium">
                    <?= smb_t('Select', 'Selecionar') ?>
                </button>
            </div>

            <div id="pathBrowserError" class="hidden bg-err/10 border-l-4 border-err p-3 text-err text-xs"></div>

            <div id="pathBrowserList" class="border border-bg0 rounded-sm bg-bg0/30 min-h-[220px] max-h-[320px] overflow-y-auto divide-y divide-bg0"></div>

            <div class="border-t border-bg0 pt-4 flex flex-col md:flex-row gap-2">
                <input type="text" id="newFolderName" placeholder="<?= htmlspecialchars(smb_t('New folder name', 'Nome da nova pasta')) ?>" class="min-w-0 flex-1 px-3 py-2 text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                <button type="button" onclick="createFolderInCurrentPath()" class="px-4 py-2 bg-acc2 text-bg0 hover:bg-acc2/90 rounded-sm transition uppercase tracking-wider text-xs font-medium">
                    <?= smb_t('Create Folder', 'Criar pasta') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const defaultShareBasePath = '/srv/samba/';
let sharePermissionsTouched = false;
let pathBrowserPath = defaultShareBasePath.replace(/\/$/, '');
const pathBrowserText = {
    loading: <?= json_encode(smb_t('Loading folders...', 'Carregando pastas...')) ?>,
    empty: <?= json_encode(smb_t('No subfolders found.', 'Nenhuma subpasta encontrada.')) ?>,
    open: <?= json_encode(smb_t('Open folder', 'Abrir pasta')) ?>,
    createName: <?= json_encode(smb_t('Enter a folder name.', 'Informe o nome da pasta.')) ?>,
    requestFailed: <?= json_encode(smb_t('Request failed.', 'A requisição falhou.')) ?>,
};

function pathBrowserCsrf() {
    return document.querySelector('#shareWizardModal input[name=csrf_token]')?.value || '';
}

function setPathBrowserError(message = '') {
    const errorBox = document.getElementById('pathBrowserError');
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.toggle('hidden', message === '');
}

function setPathBrowserLoading() {
    const list = document.getElementById('pathBrowserList');
    if (!list) return;
    list.innerHTML = '';
    const row = document.createElement('div');
    row.className = 'px-4 py-8 text-center text-muted italic';
    row.textContent = pathBrowserText.loading;
    list.appendChild(row);
}

function renderPathBrowser(path, directories) {
    pathBrowserPath = path || '/';
    const current = document.getElementById('pathBrowserCurrent');
    const list = document.getElementById('pathBrowserList');
    if (current) current.textContent = pathBrowserPath;
    if (!list) return;

    list.innerHTML = '';
    if (!directories.length) {
        const row = document.createElement('div');
        row.className = 'px-4 py-8 text-center text-muted italic';
        row.textContent = pathBrowserText.empty;
        list.appendChild(row);
        return;
    }

    directories.forEach(directory => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-bg0/60 transition-colors';
        button.title = `${pathBrowserText.open}: ${directory.path}`;
        button.addEventListener('click', () => loadPathBrowser(directory.path));

        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'w-4 h-4 text-acc shrink-0');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h6l2 2h10v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>';

        const label = document.createElement('span');
        label.className = 'min-w-0 flex-1 text-fg truncate';
        label.textContent = directory.name;

        button.appendChild(icon);
        button.appendChild(label);
        list.appendChild(button);
    });
}

async function loadPathBrowser(path) {
    setPathBrowserError('');
    setPathBrowserLoading();
    try {
        const response = await fetch(`/samba/path-browser?path=${encodeURIComponent(path || defaultShareBasePath)}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.error || pathBrowserText.requestFailed);
        renderPathBrowser(payload.path, payload.directories || []);
    } catch (error) {
        renderPathBrowser(pathBrowserPath, []);
        setPathBrowserError(error.message || pathBrowserText.requestFailed);
    }
}

function openPathBrowser() {
    const pathInput = document.querySelector('#shareWizardModal input[name=path]');
    const startPath = (pathInput?.value || defaultShareBasePath).trim() || defaultShareBasePath;
    openModal('pathBrowserModal');
    loadPathBrowser(startPath);
}

function pathBrowserGoUp() {
    if (pathBrowserPath === '/') return;
    const parent = pathBrowserPath.replace(/\/+$/, '').split('/').slice(0, -1).join('/') || '/';
    loadPathBrowser(parent);
}

function selectCurrentPath() {
    const pathInput = document.querySelector('#shareWizardModal input[name=path]');
    if (pathInput) {
        pathInput.value = pathBrowserPath === '/' ? '/' : pathBrowserPath.replace(/\/+$/, '');
        pathInput.dispatchEvent(new Event('input'));
    }
    closeModal('pathBrowserModal');
}

async function createFolderInCurrentPath() {
    const input = document.getElementById('newFolderName');
    const name = (input?.value || '').trim();
    if (!name) {
        setPathBrowserError(pathBrowserText.createName);
        return;
    }

    setPathBrowserError('');
    try {
        const body = new URLSearchParams({
            csrf_token: pathBrowserCsrf(),
            parent: pathBrowserPath,
            name,
        });
        const response = await fetch('/samba/path-browser', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.error || pathBrowserText.requestFailed);
        if (input) input.value = '';
        renderPathBrowser(pathBrowserPath, payload.directories || []);
        loadPathBrowser(payload.path);
    } catch (error) {
        setPathBrowserError(error.message || pathBrowserText.requestFailed);
    }
}

function normalizeShareSlug(value) {
    return value
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9_.-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function escapeAttributeValue(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function findPermissionRadio(prefix, name, value) {
    const safeName = escapeAttributeValue(name);
    return document.querySelector(`input[name="${prefix}_perms[${safeName}]"][value="${value}"]`);
}

function resetPermissionsToNone() {
    document.querySelectorAll('input[name^="user_perms"][value="none"], input[name^="group_perms"][value="none"]').forEach(radio => {
        radio.checked = true;
    });
}

function setPermission(prefix, name, value) {
    const radio = findPermissionRadio(prefix, name, value);
    if (radio) radio.checked = true;
}

function applyOwnerPermissionDefaults(force = false) {
    if (sharePermissionsTouched && !force) return;

    const modal = document.querySelector('#shareWizardModal');
    if (!modal) return;

    const ownerUser = modal.querySelector('select[name=owner_user]')?.value;
    const ownerGroup = modal.querySelector('select[name=owner_group]')?.value;

    resetPermissionsToNone();
    if (ownerUser) setPermission('user', ownerUser, 'write');
    if (ownerGroup) setPermission('group', ownerGroup, 'write');
}

function toggleShareAccessMode() {
    const modal = document.querySelector('#shareWizardModal');
    if (!modal) return;
    const anonymous = modal.querySelector('input[name=access_mode][value=anonymous]')?.checked;
    const permissionPanel = document.getElementById('authenticatedPermissions');
    if (!permissionPanel) return;
    permissionPanel.classList.toggle('opacity-40', anonymous);
    permissionPanel.classList.toggle('pointer-events-none', anonymous);
    permissionPanel.querySelectorAll('input, select').forEach(input => {
        input.disabled = !!anonymous;
    });
}

function setupDefaultSharePath() {
    const modal = document.querySelector('#shareWizardModal');
    if (!modal) return;

    const nameInput = modal.querySelector('input[name=name]');
    const pathInput = modal.querySelector('input[name=path]');
    if (!nameInput || !pathInput) return;

    modal.querySelectorAll('#permsTable input[type=radio]').forEach(radio => {
        radio.addEventListener('change', () => {
            sharePermissionsTouched = true;
        });
    });

    modal.querySelectorAll('select[name=owner_user], select[name=owner_group]').forEach(select => {
        select.addEventListener('change', () => applyOwnerPermissionDefaults(false));
    });

    let pathWasEdited = false;
    pathInput.addEventListener('input', () => {
        pathWasEdited = pathInput.value !== defaultShareBasePath;
    });

    nameInput.addEventListener('input', () => {
        if (pathWasEdited || nameInput.readOnly) return;
        const slug = normalizeShareSlug(nameInput.value);
        pathInput.value = defaultShareBasePath + slug;
    });

    const createButton = document.getElementById('createShareButton');
    if (createButton) {
        createButton.addEventListener('click', () => {
            const form = modal.querySelector('form');
            if (form) form.reset();
            nameInput.readOnly = false;
            modal.querySelector('input[name=access_mode][value=authenticated]').checked = true;
            toggleShareAccessMode();
            sharePermissionsTouched = false;
            applyOwnerPermissionDefaults(true);
            pathWasEdited = false;
            pathInput.value = defaultShareBasePath;
        });
    }

    applyOwnerPermissionDefaults(true);
}

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
    modal.querySelector('input[name=path]').dispatchEvent(new Event('input'));
    
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

    const isAnonymous = data.guest_ok === 'yes' || data.guest_only === 'yes';
    modal.querySelector(`input[name=access_mode][value=${isAnonymous ? 'anonymous' : 'authenticated'}]`).checked = true;
    toggleShareAccessMode();
    
    if(data.force_group) {
        const selGroup = modal.querySelector('select[name=owner_group]');
        if(selGroup) selGroup.value = data.force_group;
    }
    if(data.force_user) {
        const selUser = modal.querySelector('select[name=owner_user]');
        if(selUser) selUser.value = data.force_user;
    }
    
    // Permissions from the saved Samba config take precedence over create defaults.
    sharePermissionsTouched = true;
    resetPermissionsToNone();
    
    const splitSambaList = value => (value || '').split(/[,\s]+/).map(s => s.trim()).filter(s => s);
    const readList = splitSambaList(data.read_list);
    const writeList = splitSambaList(data.write_list);
    
    const applyPerm = (list, val) => {
        list.forEach(item => {
            if (item.startsWith('@')) {
                const grp = item.substring(1);
                setPermission('group', grp, val);
            } else {
                setPermission('user', item, val);
            }
        });
    };
    
    applyPerm(readList, 'read');
    applyPerm(writeList, 'write');
    
    // Switch to Basic Info tab
    switchTab('shareWizardModal', 's-tab-info');
    openModal('shareWizardModal');
}

document.addEventListener('DOMContentLoaded', setupDefaultSharePath);
document.addEventListener('DOMContentLoaded', toggleShareAccessMode);
</script>

<!-- Panel 2: Existing Shares -->
<div class="mt-8 bg-bg1 rounded-sm border border-bg0 p-6">
    <h2 class="text-xl font-brand font-bold text-fg mb-4 border-b border-bg0 pb-2"><?= smb_t('Shares in shares.conf', 'Compartilhamentos em shares.conf') ?></h2>
    
    <?php if (empty($existingShares)): ?>
        <p class="text-muted text-sm font-mono"><?= smb_t('No custom shares found in /etc/samba/shares.conf.', 'Nenhum compartilhamento personalizado encontrado em /etc/samba/shares.conf.') ?></p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-sm whitespace-nowrap">
                <thead>
                    <tr class="text-muted border-b border-bg0 text-xs uppercase tracking-wider">
                        <th class="py-3 font-normal px-2"><?= smb_t('Name', 'Nome') ?></th>
                        <th class="py-3 font-normal px-2"><?= smb_t('Path', 'Pasta') ?></th>
                        <th class="py-3 font-normal px-2"><?= smb_t('Read Only', 'Somente leitura') ?></th>
                        <th class="py-3 font-normal px-2"><?= smb_t('Allowed Users', 'Usuários permitidos') ?></th>
                        <th class="py-3 font-normal px-2 text-right"><?= smb_t('Actions', 'Ações') ?></th>
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
	                                'guest_ok' => '',
	                                'guest_only' => '',
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
	                                    if ($k === 'guest ok') $shareData['guest_ok'] = $v;
	                                    if ($k === 'guest only') $shareData['guest_only'] = $v;
	                                    if ($k === 'read list') $shareData['read_list'] = $v;
                                    if ($k === 'write list') $shareData['write_list'] = $v;
                                    if ($k === 'read only') $shareData['read_only'] = $v;
                                }
                            }
                            $validUsersDisplay = $shareData['read_list'] . ($shareData['read_list'] && $shareData['write_list'] ? ', ' : '') . $shareData['write_list'];
	                            if ($shareData['guest_ok'] === 'yes' || $shareData['guest_only'] === 'yes') {
	                                $validUsersDisplay = smb_t('Anonymous access', 'Acesso anônimo');
	                            } elseif (empty($validUsersDisplay) && $shareData['read_only'] === 'no') {
	                                $validUsersDisplay = smb_t('Everyone authenticated (Read/Write)', 'Todos autenticados (Leitura/Gravação)');
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
                                        onclick='editShare(<?= json_encode($shareData) ?>)'><?= smb_t('Edit', 'Editar') ?></button>
                                
                                <form action="/samba/shares" method="POST" class="inline-block" onsubmit="return confirm('<?= htmlspecialchars(smb_t('WARNING: Are you sure you want to delete this share?', 'AVISO: tem certeza de que deseja excluir este compartilhamento?')) ?>');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="share_name" value="<?= htmlspecialchars($sectionName) ?>">
                                    <button type="submit" class="px-2 py-1 bg-bg0 text-err border border-bg0 hover:border-err transition-colors rounded-sm text-xs uppercase tracking-wider font-bold"><?= smb_t('Delete', 'Excluir') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
            $pagination = $sharePagination ?? ['page' => 1, 'per_page' => 200, 'total' => count($existingShares), 'pages' => 1];
            $paginationPath = '/samba/shares';
            $paginationBase = [];
            require __DIR__ . '/_pagination.php';
        ?>
    <?php endif; ?>
</div>
