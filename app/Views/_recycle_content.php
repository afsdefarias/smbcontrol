<?php
    $filters = $filters ?? [];
    $showFiles = !empty($showFiles);
    $pagination = $pagination ?? ['page' => 1, 'per_page' => 200, 'total' => count($items ?? []), 'pages' => 1];
    $filterValue = fn(string $key): string => htmlspecialchars($filters[$key] ?? '');
    $queryParams = array_filter($filters, fn($value) => trim((string)$value) !== '');
    if ($showFiles) {
        $queryParams['files'] = '1';
    }
    $detailUrl = '/recycle?' . http_build_query(array_merge($queryParams, ['files' => '1', 'page' => 1]));
    $groupedUrl = '/recycle?' . http_build_query(array_merge($queryParams, ['files' => null, 'page' => 1]));
    $paginationUrl = function (int $page) use ($queryParams): string {
        return '/recycle?' . http_build_query(array_merge($queryParams, ['page' => $page]));
    };
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

<div class="mb-8 flex flex-col gap-4">
    <div class="flex items-baseline gap-4">
        <h1 class="text-2xl font-brand font-bold text-fg"><?= $showFiles ? smb_t('Recycle Bin Files', 'Arquivos da lixeira') : smb_t('Recycle Bin', 'Lixeira') ?></h1>
        <span class="text-sm text-muted font-mono">vfs_recycle</span>
    </div>

    <form action="/recycle" method="GET" class="bg-bg1 border border-bg0 rounded-sm p-4">
        <?php if ($showFiles): ?><input type="hidden" name="files" value="1"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <div class="md:col-span-2 flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Name or path', 'Nome ou caminho') ?></label>
                <div class="relative">
                    <svg class="w-4 h-4 text-muted absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" name="q" value="<?= $filterValue('q') ?>" placeholder="<?= htmlspecialchars(smb_t('File, folder, or path...', 'Arquivo, pasta ou caminho...')) ?>" class="pl-9 pr-3 py-2 w-full text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Share owner', 'Dono do share') ?></label>
                <input type="text" name="owner" value="<?= $filterValue('owner') ?>" placeholder="<?= htmlspecialchars(smb_t('Owner user', 'Usuário dono')) ?>" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Deleted by', 'Excluído por') ?></label>
                <input type="text" name="deleted_by" value="<?= $filterValue('deleted_by') ?>" placeholder="<?= htmlspecialchars(smb_t('SMB user', 'Usuário SMB')) ?>" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Deleted from', 'Excluído de') ?></label>
                <input type="date" name="date_from" value="<?= $filterValue('date_from') ?>" class="px-3 py-2 text-sm text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Deleted to', 'Excluído até') ?></label>
                <input type="date" name="date_to" value="<?= $filterValue('date_to') ?>" class="px-3 py-2 text-sm text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="<?= htmlspecialchars($showFiles ? $groupedUrl : $detailUrl) ?>" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    <?= $showFiles ? smb_t('Show folders', 'Mostrar pastas') : smb_t('Show files', 'Mostrar arquivos') ?>
                </a>
                <a href="/recycle" class="px-4 py-2 border border-bg0 text-muted hover:text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    <?= smb_t('Clear', 'Limpar') ?>
                </a>
            </div>
            <button type="submit" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
                <?= smb_t('Filter', 'Filtrar') ?>
            </button>
        </div>
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
            <?= (int)$pagination['total'] ?> <?= smb_t('deleted item(s)', 'itens excluídos') ?>
        </span>
        <span class="px-2 py-1 border border-bg0 bg-bg1 rounded-sm">
            <?= $showFiles ? smb_t('file detail view', 'visão detalhada de arquivos') : smb_t('grouped folder view', 'visão agrupada por pasta') ?>
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
                        <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Share owner', 'Dono do share') ?></th>
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
                            <td colspan="9" class="px-4 py-8 text-center text-muted italic">
                                <?= smb_t('No deleted items match the current filter.', 'Nenhum item excluído corresponde ao filtro atual.') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-bg0/50 transition-colors">
                                <td class="px-4 py-2.5 text-fg font-bold max-w-[220px] truncate" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-4 py-2.5 text-acc"><?= htmlspecialchars($item['share']) ?></td>
                                <td class="px-4 py-2.5 text-muted"><?= $item['type'] === 'directory' ? smb_t('Folder', 'Pasta') : smb_t('File', 'Arquivo') ?></td>
                                <td class="px-4 py-2.5 text-muted"><?= htmlspecialchars($item['owner'] ?: '-') ?></td>
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
    <?php if (($pagination['pages'] ?? 1) > 1): ?>
        <div class="mt-4 flex items-center justify-between text-xs font-mono text-muted">
            <span><?= smb_t('Page', 'Página') ?> <?= (int)$pagination['page'] ?> / <?= (int)$pagination['pages'] ?> · <?= smb_t('Maximum 200 results per page', 'Máximo de 200 resultados por página') ?></span>
            <div class="flex items-center gap-2">
                <?php if ($pagination['page'] > 1): ?><a href="<?= htmlspecialchars($paginationUrl($pagination['page'] - 1)) ?>" class="px-3 py-1.5 border border-bg0 hover:bg-bg0 text-fg rounded-sm"><?= smb_t('Previous', 'Anterior') ?></a><?php endif; ?>
                <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= htmlspecialchars($paginationUrl($pagination['page'] + 1)) ?>" class="px-3 py-1.5 border border-bg0 hover:bg-bg0 text-fg rounded-sm"><?= smb_t('Next', 'Próxima') ?></a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
