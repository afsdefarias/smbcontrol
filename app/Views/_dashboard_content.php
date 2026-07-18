<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Dashboard</h1>
    <span class="text-sm text-muted font-mono"><?= smb_t('system_status', 'status_do_sistema') ?></span>
</div>

<div class="bg-bg1 rounded-sm border border-bg0 p-6 max-w-2xl">
    <div class="flex items-center justify-between mb-6 border-b border-bg0 pb-4">
        <h2 class="text-lg font-ui text-fg"><?= smb_t('Service Status', 'Status do serviço') ?> <span class="font-mono text-muted text-sm ml-2">(smbd)</span></h2>
        
        <?php if (isset($isActive) && $isActive): ?>
            <span class="px-3 py-1 bg-ok/10 text-ok border border-ok/20 rounded-full text-xs font-mono flex items-center gap-2 shadow-[0_0_10px_rgba(111,174,122,0.2)]">
                <div class="w-1.5 h-1.5 rounded-full bg-ok animate-pulse"></div> <?= smb_t('RUNNING', 'RODANDO') ?>
            </span>
        <?php else: ?>
            <span class="px-3 py-1 bg-err/10 text-err border border-err/20 rounded-full text-xs font-mono flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-err"></div> <?= smb_t('STOPPED', 'PARADO') ?>
            </span>
        <?php endif; ?>
    </div>
    
    <form action="/samba/service" method="POST" class="flex gap-3 font-mono text-sm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <button type="submit" name="action" value="start" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors"><?= smb_t('start', 'iniciar') ?></button>
        <button type="submit" name="action" value="stop" class="px-4 py-2 bg-bg0 text-fg border border-bg0 hover:border-err hover:text-err transition-colors"><?= smb_t('stop', 'parar') ?></button>
        <button type="submit" name="action" value="restart" class="px-4 py-2 bg-bg0 text-fg border border-bg0 hover:border-muted hover:text-fg transition-colors"><?= smb_t('restart', 'reiniciar') ?></button>
    </form>
</div>
