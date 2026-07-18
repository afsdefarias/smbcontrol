<?php
// Define the predefined common share options
$commonShareOptions = [
    'path' => ['description' => 'Pasta Linux exportada por este compartilhamento'],
    'comment' => ['description' => 'Descricao exibida para clientes SMB'],
    'browseable' => ['description' => 'Mostra o share na navegacao de rede (yes/no)'],
    'read only' => ['description' => 'Bloqueia escrita por padrao; write list pode liberar excecoes (yes/no)'],
    'writeable' => ['description' => 'Permite escrita por padrao (yes/no)'],
    'guest ok' => ['description' => 'Permite acesso anonimo/guest (yes/no)'],
    'public' => ['description' => 'Alias de guest ok (yes/no)'],
    'valid users' => ['description' => 'Usuarios/grupos autorizados a conectar; grupos usam @grupo'],
    'invalid users' => ['description' => 'Usuarios/grupos bloqueados'],
    'read list' => ['description' => 'Usuarios/grupos forçados para somente leitura'],
    'write list' => ['description' => 'Usuarios/grupos autorizados a gravar'],
    'admin users' => ['description' => 'Usuarios que operam como root neste share'],
    'create mask' => ['description' => 'Mascara para novos arquivos, ex.: 0660'],
    'directory mask' => ['description' => 'Mascara para novas pastas, ex.: 0770'],
    'force user' => ['description' => 'Forca todas as conexoes a gravarem como este usuario'],
    'force group' => ['description' => 'Forca todas as conexoes a gravarem como este grupo'],
    'vfs objects' => ['description' => 'Modulos VFS ativos, ex.: recycle full_audit'],
    'veto files' => ['description' => 'Padroes de arquivos escondidos/bloqueados'],
    'hide dot files' => ['description' => 'Oculta arquivos iniciados por ponto (yes/no)'],
    'inherit permissions' => ['description' => 'Novos arquivos herdam permissoes da pasta pai (yes/no)'],
];

// Combine existing parsed options with our common options for each share
$displayData = [];
if (!empty($parsedConf)) {
    foreach ($parsedConf as $section => $fields) {
        $sectionOptions = [];
        $existingKeys = [];
        
        // Add existing options first
        foreach ($fields as $idx => $field) {
            if (!empty($field['is_standalone_comment'])) {
                // If it's just a comment, add it as is
                $sectionOptions[] = array_merge($field, ['is_predefined' => false, 'idx' => $idx]);
                continue;
            }
            $existingKeys[strtolower($field['key'])] = true;
            $sectionOptions[] = array_merge($field, ['is_predefined' => false, 'idx' => $idx]);
        }
        
        // Now add any common options that are missing, marked as disabled
        foreach ($commonShareOptions as $key => $info) {
            if (!isset($existingKeys[$key])) {
                $sectionOptions[] = [
                    'key' => $key,
                    'value' => '',
                    'comments' => $info['description'],
                    'disabled' => true,
                    'is_boolean' => false,
                    'is_predefined' => true,
                    'is_standalone_comment' => false
                ];
            }
        }
        
        $displayData[$section] = $sectionOptions;
    }
}
?>

<div class="flex justify-between items-baseline mb-8">
    <div class="flex items-baseline gap-4">
        <h1 class="text-2xl font-brand font-bold text-fg">Parametros dos compartilhamentos</h1>
        <span class="text-sm text-muted font-mono">/etc/samba/shares.conf</span>
    </div>
</div>

<?php if (isset($sharesIncluded) && !$sharesIncluded): ?>
    <div class="mb-6 bg-err/10 border-l-4 border-err p-4 text-sm font-mono text-err">
        <code>/etc/samba/shares.conf</code> nao esta incluido no <code>/etc/samba/smb.conf</code>. O painel tenta corrigir isso automaticamente antes de salvar.
    </div>
<?php endif; ?>

<div class="bg-bg1 rounded-sm border border-bg0 p-6">
    <form action="/samba/shares-config" method="POST" id="sharesForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <div class="mb-6">
            <?php if (empty($displayData)): ?>
                <div class="p-6 text-center text-muted font-mono bg-bg0/30 border border-bg0 rounded-sm">
                    Nenhum compartilhamento encontrado em shares.conf. Crie um primeiro na tela Compartilhamentos.
                </div>
            <?php else: ?>
                <?php foreach ($displayData as $section => $fields): ?>
                    <div class="mb-8 p-4 bg-bg0/20 border border-bg0/50 rounded-sm">
                        <h3 class="text-xl font-brand font-bold text-acc mb-4 border-b border-bg0 pb-2">[<?= htmlspecialchars($section) ?>]</h3>
                        
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <?php 
                                $idxCounter = 9000;
                                foreach ($fields as $field): 
                            ?>
                                <?php 
                                    if (!empty($field['is_standalone_comment'])) {
                                        $idx = $field['idx'];
                                        $comments = htmlspecialchars($field['comments']);
                                        echo '<div class="col-span-1 xl:col-span-2 mb-2 bg-bg0/30 p-3 border-l-2 border-muted/50 rounded-sm">';
                                        echo '<p class="text-xs font-mono text-muted/70 italic whitespace-pre-line"># ' . $comments . '</p>';
                                        echo '<input type="hidden" name="config[' . htmlspecialchars($section) . '][' . $idx . '][is_standalone_comment]" value="1">';
                                        echo '<input type="hidden" name="config[' . htmlspecialchars($section) . '][' . $idx . '][comments]" value="' . $comments . '">';
                                        echo '</div>';
                                        continue;
                                    }
                                    
                                    $key = htmlspecialchars($field['key']);
                                    $val = htmlspecialchars($field['value']);
                                    $disabled = $field['disabled'];
                                    $comments = htmlspecialchars($field['comments']);
                                    
                                    $isPredefined = !empty($field['is_predefined']);
                                    $idx = $isPredefined ? $idxCounter++ : $field['idx'];
                                    
                                    $opacityClass = $disabled ? 'opacity-50 grayscale' : 'opacity-100';
                                    $borderClass = $disabled ? 'border-dashed border-bg0' : 'border-solid border-acc/30 bg-bg0/60';
                                ?>
                                <div class="flex flex-col p-3 border rounded-sm transition-all duration-300 <?= $opacityClass ?> <?= $borderClass ?>" id="container-<?= md5($section.$key) ?>">
                                    
                                    <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][key]" value="<?= $key ?>">
                                    <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][comments]" value="<?= $comments ?>" <?= $disabled ? 'disabled' : '' ?> class="comment-input">
                                    <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][enabled]" value="0">
                                    
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex flex-col">
                                            <label class="text-sm font-mono text-fg font-medium"><?= $key ?></label>
                                            <?php if ($comments): ?>
                                                <span class="text-xs font-mono text-muted/60 mt-0.5 break-all max-w-[200px]"><?= $comments ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                                            <input type="checkbox" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][enabled]" value="1" class="sr-only peer toggle-active" data-target="container-<?= md5($section.$key) ?>" <?= !$disabled ? 'checked' : '' ?>>
                                            <div class="w-9 h-5 bg-bg1 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-acc"></div>
                                        </label>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <input type="text" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][value]" 
                                               value="<?= $val ?>" 
                                               class="w-full font-mono text-sm px-3 py-1.5 rounded-sm bg-bg1/50 border border-bg0 focus:border-acc text-fg placeholder:text-muted/30 transition-colors val-input"
                                               <?= $disabled ? 'disabled placeholder="Disabled"' : '' ?>
                                        >
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="flex justify-end pt-4 border-t border-bg0">
            <button type="submit" class="px-6 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-sm flex items-center gap-2">
                Salvar, validar e aplicar
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.toggle-active');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const containerId = this.getAttribute('data-target');
            const container = document.getElementById(containerId);
            const valueInput = container.querySelector('.val-input');
            const commentInput = container.querySelector('.comment-input');
            
            if (this.checked) {
                container.classList.remove('opacity-50', 'grayscale', 'border-dashed', 'border-bg0');
                container.classList.add('opacity-100', 'border-solid', 'border-acc/30', 'bg-bg0/60');
                
                valueInput.disabled = false;
                valueInput.placeholder = 'Enter value...';
                if (commentInput) commentInput.disabled = false;
                
                valueInput.focus();
            } else {
                container.classList.add('opacity-50', 'grayscale', 'border-dashed', 'border-bg0');
                container.classList.remove('opacity-100', 'border-solid', 'border-acc/30', 'bg-bg0/60');
                
                valueInput.disabled = true;
                valueInput.placeholder = 'Disabled';
                if (commentInput) commentInput.disabled = true;
            }
        });
    });
    
    document.getElementById('sharesForm').addEventListener('submit', function() {
        document.querySelectorAll('.val-input, .comment-input').forEach(input => {
            input.disabled = false;
        });
    });
});
</script>
