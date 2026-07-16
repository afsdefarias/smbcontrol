<div class="mb-8 flex items-baseline gap-4">
    <h1 class="text-2xl font-brand font-bold text-fg">Audit</h1>
    <span class="text-sm text-muted font-mono">vfs_full_audit</span>
</div>

<div class="bg-bg1 rounded-sm border border-bg0 p-1">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono whitespace-nowrap">
            <thead class="text-muted tracking-wider border-b border-bg0">
                <tr>
                    <th class="px-4 py-3 font-medium uppercase">When</th>
                    <th class="px-4 py-3 font-medium uppercase">IP</th>
                    <th class="px-4 py-3 font-medium uppercase">User</th>
                    <th class="px-4 py-3 font-medium uppercase">Op</th>
                    <th class="px-4 py-3 font-medium uppercase w-full">File</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bg0">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted italic">No audit records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-bg0/50 transition-colors">
                            <td class="px-4 py-2.5 text-fg"><?php echo htmlspecialchars($log['data_hora'] ?? ''); ?></td>
                            <td class="px-4 py-2.5 text-muted"><?php echo htmlspecialchars($log['ip'] ?? ''); ?></td>
                            <td class="px-4 py-2.5 text-fg"><?php echo htmlspecialchars($log['usuario'] ?? ''); ?></td>
                            <td class="px-4 py-2.5">
                                <?php 
                                    $acao = htmlspecialchars($log['acao'] ?? '');
                                    // Tradução visual rápida ou estilo
                                    echo "<span class='px-2 py-0.5 border border-bg0 rounded-full text-[10px] text-muted bg-bg0/50 uppercase tracking-wide'>{$acao}</span>";
                                ?>
                            </td>
                            <td class="px-4 py-2.5 text-muted truncate max-w-xl" title="<?php echo htmlspecialchars($log['arquivo'] ?? ''); ?>">
                                <?php echo htmlspecialchars($log['arquivo'] ?? ''); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
