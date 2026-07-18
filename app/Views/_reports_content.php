<?php
    $filters = $filters ?? [];
    $filterValue = fn(string $key): string => htmlspecialchars($filters[$key] ?? '');
    $hasFilters = false;
    foreach ($filters as $value) {
        if (trim((string)$value) !== '') {
            $hasFilters = true;
            break;
        }
    }
?>

<div class="mb-8 flex flex-col gap-4">
    <div class="flex items-baseline gap-4">
        <h1 class="text-2xl font-brand font-bold text-fg"><?= smb_t('Audit Reports', 'Relatórios de auditoria') ?></h1>
        <span class="text-sm text-muted font-mono">vfs_full_audit</span>
    </div>

    <form action="/reports" method="GET" class="bg-bg1 border border-bg0 rounded-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-8 gap-3">
            <div class="md:col-span-2 xl:col-span-2 flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Name, content, location', 'Nome, conteúdo, local') ?></label>
                <div class="relative">
                    <svg class="w-4 h-4 text-muted absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" name="q" value="<?= $filterValue('q') ?>" placeholder="<?= htmlspecialchars(smb_t('File, folder, operation, raw log...', 'Arquivo, pasta, operação, log bruto...')) ?>" class="pl-9 pr-3 py-2 w-full text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('User', 'Usuário') ?></label>
                <input type="text" name="user" value="<?= $filterValue('user') ?>" placeholder="<?= htmlspecialchars(smb_t('e.g. maria', 'ex.: maria')) ?>" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider">IP</label>
                <input type="text" name="ip" value="<?= $filterValue('ip') ?>" placeholder="127.0.0.1" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Share', 'Compartilhamento') ?></label>
                <input type="text" name="share" value="<?= $filterValue('share') ?>" placeholder="<?= htmlspecialchars(smb_t('Share name', 'Nome do share')) ?>" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('Operation', 'Operação') ?></label>
                <input type="text" name="operation" value="<?= $filterValue('operation') ?>" placeholder="connect, unlinkat" class="px-3 py-2 text-sm text-fg placeholder:text-muted/50 border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('From', 'De') ?></label>
                <input type="date" name="date_from" value="<?= $filterValue('date_from') ?>" class="px-3 py-2 text-sm text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted uppercase tracking-wider"><?= smb_t('To', 'Até') ?></label>
                <input type="date" name="date_to" value="<?= $filterValue('date_to') ?>" class="px-3 py-2 text-sm text-fg border border-bg0 rounded-sm focus:border-acc transition-colors">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs text-muted font-mono">
                <?= count($logs) ?> <?= smb_t('matching audit record(s)', 'registro(s) de auditoria encontrados') ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($hasFilters): ?>
                    <a href="/reports" class="px-4 py-2 border border-bg0 text-fg hover:bg-bg0 transition-colors rounded-sm uppercase tracking-wider text-xs">
                        <?= smb_t('Clear', 'Limpar') ?>
                    </a>
                <?php endif; ?>
                <button type="submit" class="px-4 py-2 bg-acc text-bg0 font-medium hover:bg-acc/90 transition-colors rounded-sm uppercase tracking-wider text-xs">
                    <?= smb_t('Filter', 'Filtrar') ?>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="bg-bg1 rounded-sm border border-bg0 p-1">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono whitespace-nowrap">
            <thead class="text-muted tracking-wider border-b border-bg0">
                <tr>
                    <th class="px-4 py-3 font-medium uppercase"><?= smb_t('When', 'Quando') ?></th>
                    <th class="px-4 py-3 font-medium uppercase">IP</th>
                    <th class="px-4 py-3 font-medium uppercase"><?= smb_t('User', 'Usuário') ?></th>
                    <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Share', 'Compartilhamento') ?></th>
                    <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Op', 'Operação') ?></th>
                    <th class="px-4 py-3 font-medium uppercase"><?= smb_t('Name', 'Nome') ?></th>
                    <th class="px-4 py-3 font-medium uppercase w-full"><?= smb_t('Location / content', 'Local / conteúdo') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bg0">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-muted italic"><?= smb_t('No audit records found for the current filters.', 'Nenhum registro de auditoria encontrado para os filtros atuais.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-bg0/50 transition-colors">
                            <td class="px-4 py-2.5 text-fg"><?= htmlspecialchars($log['timestamp'] ?? '') ?></td>
                            <td class="px-4 py-2.5 text-muted"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                            <td class="px-4 py-2.5 text-fg"><?= htmlspecialchars($log['user'] ?? '') ?></td>
                            <td class="px-4 py-2.5 text-acc"><?= htmlspecialchars($log['share'] ?? '') ?></td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 border border-bg0 rounded-full text-[10px] text-muted bg-bg0/50 uppercase tracking-wide">
                                    <?= htmlspecialchars(($log['operation'] ?? '') . ' (' . ($log['status'] ?? '') . ')') ?>
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-fg max-w-[220px] truncate" title="<?= htmlspecialchars($log['name'] ?? '') ?>"><?= htmlspecialchars($log['name'] ?? '') ?></td>
                            <td class="px-4 py-2.5 text-muted truncate max-w-xl" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                <?= htmlspecialchars($log['details'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
