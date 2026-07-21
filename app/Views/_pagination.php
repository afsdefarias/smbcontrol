<?php if (($pagination['pages'] ?? 1) > 1): ?>
    <?php
        $paginationBase = $paginationBase ?? [];
        $paginationUrl = function (int $page) use ($paginationBase, $paginationPath): string {
            return $paginationPath . '?' . http_build_query(array_merge($paginationBase, ['page' => $page]));
        };
    ?>
    <div class="mt-4 flex items-center justify-between text-xs font-mono text-muted">
        <span><?= smb_t('Page', 'Página') ?> <?= (int)$pagination['page'] ?> / <?= (int)$pagination['pages'] ?> · <?= smb_t('Maximum 200 results per page', 'Máximo de 200 resultados por página') ?></span>
        <div class="flex items-center gap-2">
            <?php if ($pagination['page'] > 1): ?><a href="<?= htmlspecialchars($paginationUrl($pagination['page'] - 1)) ?>" class="px-3 py-1.5 border border-bg0 hover:bg-bg0 text-fg rounded-sm"><?= smb_t('Previous', 'Anterior') ?></a><?php endif; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= htmlspecialchars($paginationUrl($pagination['page'] + 1)) ?>" class="px-3 py-1.5 border border-bg0 hover:bg-bg0 text-fg rounded-sm"><?= smb_t('Next', 'Próxima') ?></a><?php endif; ?>
        </div>
    </div>
<?php endif; ?>
