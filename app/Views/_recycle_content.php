<?php
    $formatBytes = function (int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = max(0, $bytes);
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return ($unit === 0 ? (string)$size : number_format($size, 1)) . ' ' . $units[$unit];
    };
?>

<div class="mb-8 flex flex-col gap-2 md:flex-row md:items-baseline md:justify-between">
    <div class="flex items-baseline gap-4">
        <h1 class="text-2xl font-brand font-bold text-fg"><?= smb_t('Recycle Bin', 'Lixeira') ?></h1>
        <span class="text-sm text-muted font-mono">vfs_recycle</span>
    </div>

    <form action="/recycle" method="GET" class="flex items-center gap-2">
        <div class="relative">
            <svg class="w-4 h-4 text-muted absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input type="text" name="q" value="<?= htmlspecialchars($filter ?? '') ?>" placeholder="<?= htmlspecialchars(smb_t('Filter by name or path', 'Filtrar por nome ou caminho')) ?>" class="pl-9 pr-3 py-2 w-72 max-w-full text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
        </div>
        <button type="submit" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
            <?= smb_t('Filter', 'Filtrar') ?>
        </button>
    </form>
</div>

<?php if (empty($recycleShares)): ?>
    <div class="bg-bg1 rounded-sm border border-bg0 p-6 text-muted font-mono text-sm">
        <?= smb_t('No shares with Samba recycle bin enabled were found.', 'Nenhum compartilhamento com lixeira do Samba ativada foi encontrado.') ?>
    </div>
<?php else: ?>
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs font-mono text-muted">
        <span class="px-2 py-1 border border-bg0 bg-bg1 rounded-sm">
            <?= count($recycleShares) ?> <?= smb_t('recycle-enabled share(s)', 'compartilhamento(s) com lixeira') ?>
        </span>
        <span class="px-2 py-1 border border-bg0 bg-bg1 rounded-sm">
            <?= count($items) ?> <?= smb_t('deleted item(s)', 'itens excluídos') ?>
        </span>
    </div>

    <div class="bg-bg1 rounded-sm border border-bg0 p-1">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono whitespace-nowrap">
                <thead class="text-muted tracking-wider border-b border-bg0">
                    <tr>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Name', 'Nome') ?></th>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Share', 'Compartilhamento') ?></th>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Type', 'Tipo') ?></th>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('User', 'Usuário') ?></th>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Original location', 'Localização original') ?></th>
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Deleted at', 'Excluído em') ?></th>
                        <th class="px-4 py-3 font-medium uppercase text-right"><?= smb_t('Size', 'Tamanho') ?></th>
                        <th class="px-4 py-3 font-medium uppercase text-right"><?= smb_t('Action', 'Ação') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bg0">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-muted italic">
                                <?= smb_t('No deleted items match the current filter.', 'Nenhum item excluído corresponde ao filtro atual.') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-bg0/50 transition-colors">
                                <td class="px-4 py-2.5 text-fg font-bold max-w-[220px] truncate" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-4 py-2.5 text-acc"><?= htmlspecialchars($item['share']) ?></td>
                                <td class="px-4 py-2.5 text-muted"><?= $item['type'] === 'directory' ? smb_t('Folder', 'Pasta') : smb_t('File', 'Arquivo') ?></td>
                                <td class="px-4 py-2.5 text-fg"><?= htmlspecialchars($item['user']) ?></td>
                                <td class="px-4 py-2.5 text-muted max-w-md truncate" title="<?= htmlspecialchars($item['original_path']) ?>"><?= htmlspecialchars($item['original_path']) ?></td>
                                <td class="px-4 py-2.5 text-muted"><?= htmlspecialchars($item['deleted_at']) ?></td>
                                <td class="px-4 py-2.5 text-muted text-right"><?= $item['type'] === 'directory' ? '-' : htmlspecialchars($formatBytes((int)$item['size'])) ?></td>
                                <td class="px-4 py-2.5 text-right">
                                    <form action="/recycle" method="POST" class="inline-block" onsubmit="return confirm('<?= htmlspecialchars(smb_t('Restore this item to its original location?', 'Restaurar este item para o local original?')) ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="share" value="<?= htmlspecialchars($item['share']) ?>">
                                        <input type="hidden" name="recycle_path" value="<?= htmlspecialchars($item['recycle_path']) ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-acc text-bg0 hover:bg-acc/90 transition-colors rounded-sm text-xs uppercase tracking-wider font-bold">
                                            <?= smb_t('Restore', 'Restaurar') ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
