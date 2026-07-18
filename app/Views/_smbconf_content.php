<?php
    $hasAudit = stripos($content, 'full_audit') !== false;
    if (!$hasAudit) {
        $sharesConf = shell_exec("sudo -n /usr/bin/cat /etc/samba/shares.conf 2>/dev/null");
        if ($sharesConf && stripos($sharesConf, 'full_audit') !== false) {
            $hasAudit = true;
        }
    }
?>
<div class="flex justify-between items-baseline mb-8">
    <div class="flex items-baseline gap-4">
        <h1 class="text-2xl font-brand font-bold text-fg"><?= smb_t('Global Settings', 'Configurações globais') ?></h1>
        <span class="text-sm text-muted font-mono">/etc/samba/smb.conf</span>
    </div>
    
    <div class="flex items-center gap-3 font-mono text-sm">
        <span class="text-muted">Audit (vfs_full_audit):</span>
        <?php if($hasAudit): ?>
            <span class="px-2 py-0.5 bg-ok/10 text-ok border border-ok/20 rounded-sm text-xs shadow-[0_0_8px_rgba(111,174,122,0.15)]"><?= smb_t('ENABLED', 'ATIVADA') ?></span>
        <?php else: ?>
            <span class="px-2 py-0.5 bg-err/10 text-err border border-err/20 rounded-sm text-xs"><?= smb_t('DISABLED', 'DESATIVADA') ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="bg-bg1 rounded-sm border border-bg0 p-6">
    <form action="/samba/conf" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <div class="mb-6">
            <?php foreach ($parsedConf as $section => $fields): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-brand font-bold text-acc mb-4 border-b border-bg0 pb-2">[<?php echo htmlspecialchars($section); ?>]</h3>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <?php foreach ($fields as $idx => $field): ?>
                            <?php 
                                if (!empty($field['is_standalone_comment'])) {
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
                                $isBool = $field['is_boolean'];
                                $comments = htmlspecialchars($field['comments']);
                                $opacityClass = $disabled ? 'opacity-40 grayscale' : 'opacity-100';
                            ?>
                            <div class="flex flex-col p-3 border border-bg0 rounded-sm bg-bg0/50 transition-all duration-300 <?= $opacityClass ?>" id="container-<?= md5($section.$idx) ?>">
                                <!-- Hidden inputs to preserve structure and comments -->
                                <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][key]" value="<?= $key ?>">
                                <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][comments]" value="<?= $comments ?>">
                                
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex flex-col">
                                        <label class="text-sm font-mono text-fg font-medium"><?= $key ?></label>
                                        <?php if ($comments): ?>
                                            <p class="text-[10px] font-mono text-muted/80 mt-1 whitespace-pre-line leading-tight"><?= $comments ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Toggle Ativar/Desativar (;) -->
                                    <div class="flex items-center gap-2 px-2 py-1 bg-bg1 border border-bg0 rounded-sm">
                                        <span class="text-[10px] font-mono text-muted uppercase tracking-wider"><?= smb_t('Enable', 'Ativar') ?></span>
                                        <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][enabled]" value="0">
                                        <input type="checkbox" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][enabled]" value="1" <?= !$disabled ? 'checked' : '' ?> 
                                            class="w-3.5 h-3.5 accent-acc cursor-pointer"
                                            onchange="
                                                document.getElementById('input-<?= md5($section.$idx) ?>').disabled = !this.checked; 
                                                document.getElementById('container-<?= md5($section.$idx) ?>').classList.toggle('opacity-40', !this.checked);
                                                document.getElementById('container-<?= md5($section.$idx) ?>').classList.toggle('grayscale', !this.checked);
                                                document.getElementById('container-<?= md5($section.$idx) ?>').classList.toggle('opacity-100', this.checked);
                                            ">
                                    </div>
                                </div>
                                
                                <div class="mt-auto pt-2">
                                    <?php if ($isBool): ?>
                                        <!-- Boolean Checkbox (instead of switch for industrial look) -->
                                        <input type="hidden" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][value]" value="no">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][value]" id="input-<?= md5($section.$idx) ?>" value="yes" class="accent-acc2 w-4 h-4" <?= $val === 'yes' ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                            <span class="text-xs font-mono text-fg"><?= smb_t('Yes (Enabled)', 'Sim (ativado)') ?></span>
                                        </label>
                                    <?php else: ?>
                                        <!-- Text Input -->
                                        <input type="text" name="config[<?= htmlspecialchars($section) ?>][<?= $idx ?>][value]" id="input-<?= md5($section.$idx) ?>" value="<?= $val ?>" <?= $disabled ? 'disabled' : '' ?> class="w-full px-2 py-1.5 text-xs font-mono bg-bg1 text-fg border border-bg0 rounded-sm focus:border-acc transition-colors disabled:text-muted/50">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-acc text-bg0 font-medium hover:bg-acc/90 rounded-sm transition uppercase tracking-wider text-xs font-mono">
            <?= smb_t('Save & Validate (testparm)', 'Salvar e validar (testparm)') ?>
        </button>
    </form>
</div>
