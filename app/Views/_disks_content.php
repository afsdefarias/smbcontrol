<div class="mb-8 flex items-center justify-between">
    <div class="flex items-baseline gap-4">
        <h1 class="text-3xl font-brand font-bold text-fg tracking-tight">Disks</h1>
        <span class="text-sm text-muted font-mono tracking-wide">lsblk • mount • fstab</span>
    </div>
    <a href="/disks" class="px-4 py-2 bg-transparent border border-bg0 text-fg hover:border-muted rounded-sm font-mono text-sm transition-colors">reload</a>
</div>

<div class="space-y-4 mb-8">
    <!-- Format Form -->
    <form action="/disks" method="POST" class="flex flex-wrap items-center gap-4 bg-bg1 border border-bg0 px-4 py-3 rounded-sm font-mono text-sm" onsubmit="return confirm('WARNING: Formatting will permanently erase the disk. Continue?');">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="action" value="format">
        
        <span class="text-muted text-xs uppercase tracking-widest font-bold w-16">Format</span>
        <input type="text" name="device_path" placeholder="/dev/sdb" required class="w-48 bg-bg0 border border-bg0 text-fg px-3 py-1.5 focus:border-acc outline-none rounded-sm transition-colors">
        <input type="text" name="mount_name" placeholder="storage_nome" required pattern="[a-zA-Z0-9-]+" class="w-48 bg-bg0 border border-bg0 text-fg px-3 py-1.5 focus:border-acc outline-none rounded-sm transition-colors">
        
        <select name="fstype" class="bg-bg0 border border-bg0 text-fg px-3 py-1.5 focus:border-acc outline-none rounded-sm transition-colors">
            <option value="ext4">ext4</option>
            <option value="xfs">xfs</option>
        </select>
        
        <button type="submit" class="px-4 py-1.5 text-acc border border-acc/30 bg-acc/5 hover:bg-acc hover:text-bg0 transition-colors rounded-sm ml-auto sm:ml-0">mkfs+mount</button>
    </form>

    <!-- Import Form -->
    <form action="/disks" method="POST" class="flex flex-wrap items-center gap-4 bg-bg1 border border-bg0 px-4 py-3 rounded-sm font-mono text-sm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="action" value="import">
        
        <span class="text-muted text-xs uppercase tracking-widest font-bold w-16">Import</span>
        <input type="text" name="device_path" placeholder="/dev/sdb1" required class="w-48 bg-bg0 border border-bg0 text-fg px-3 py-1.5 focus:border-acc outline-none rounded-sm transition-colors">
        <input type="text" name="mount_name" placeholder="backup" required pattern="[a-zA-Z0-9-]+" class="w-48 bg-bg0 border border-bg0 text-fg px-3 py-1.5 focus:border-acc outline-none rounded-sm transition-colors">
        
        <button type="submit" class="px-4 py-1.5 text-fg bg-bg0 border border-bg0 hover:border-muted transition-colors rounded-sm ml-auto sm:ml-0">mount</button>
    </form>
</div>

<div class="bg-bg1 border border-bg0 rounded-sm overflow-x-auto">
    <table class="w-full text-left font-mono text-sm whitespace-nowrap">
        <thead>
            <tr class="text-muted border-b border-bg0 text-xs uppercase tracking-wider">
                <th class="px-6 py-4 font-normal">Dev</th>
                <th class="px-6 py-4 font-normal">Size</th>
                <th class="px-6 py-4 font-normal">FS</th>
                <th class="px-6 py-4 font-normal">Mount</th>
                <th class="px-6 py-4 font-normal">Status</th>
                <th class="px-6 py-4 font-normal text-right">Sys</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-bg0/50">
            <?php if (empty($disks)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-muted">No disks found or error parsing lsblk.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($disks as $disk): ?>
                    <?php 
                        $isSys = ($disk['classification'] === 'Disco de Sistema' || $disk['mountpoint'] === '[SWAP]');
                        
                        // Translating status for UI based on mockup
                        if ($disk['classification'] === 'Disco de Sistema' || $disk['mountpoint'] === '[SWAP]') {
                            $statusText = 'Montado e em Uso';
                        } elseif ($disk['classification'] === 'Limpo (Virgem)') {
                            $statusText = 'Limpo';
                        } elseif ($disk['classification'] === 'Formatado Desmontado') {
                            $statusText = 'Formatado mas Desmontado';
                        } elseif ($disk['classification'] === 'Montado') {
                            $statusText = 'Montado e em Uso';
                        } else {
                            $statusText = $disk['classification'];
                        }
                        
                        $statusClass = 'border-bg0 text-muted';
                        if (strpos($statusText, 'Montado') !== false) {
                            $statusClass = 'border-acc2/30 text-acc2 bg-acc2/5'; // Use acc2 for green/ok state from mockup
                        } elseif (strpos($statusText, 'Formatado mas Desmontado') !== false) {
                            $statusClass = 'border-acc2/30 text-acc2 bg-acc2/5'; // Mockup shows it green-ish too, but let's use slightly dimmer or same
                        }
                    ?>
                    <tr class="hover:bg-bg0/20 transition-colors">
                        <td class="px-6 py-4 text-fg font-bold">/dev/<?= htmlspecialchars($disk['name']) ?></td>
                        <td class="px-6 py-4 text-fg"><?= htmlspecialchars($disk['size'] ?? '') ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($disk['fstype'] ?: '—') ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($disk['mountpoint'] ?: '—') ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium border <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($isSys): ?>
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider font-bold border border-acc/30 text-acc bg-acc/5">sys</span>
                            <?php elseif ($disk['classification'] === 'Montado'): ?>
                                <form action="/disks" method="POST" class="inline-block" onsubmit="return confirm('Eject this disk? It will be safely unmounted.');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="eject">
                                    <input type="hidden" name="mountpoint" value="<?= htmlspecialchars($disk['mountpoint'] ?? '') ?>">
                                    <button type="submit" class="px-2 py-1 bg-bg0 text-muted hover:border-err hover:text-err border border-bg0 transition-colors rounded-sm text-xs">eject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
