<?php
namespace App\Services;

use App\Services\Shell;

class DiskManager {
    
    /**
     * Lista todos os discos e partições do sistema e os classifica.
     */
    public static function listDisks(): array {
        $output = shell_exec("/usr/bin/lsblk -J -o NAME,PATH,SIZE,FSTYPE,MOUNTPOINT,UUID,TYPE,PKNAME 2>/dev/null");
        
        if (!$output) {
            return [];
        }
        
        $data = json_decode(trim($output), true);
        if (!isset($data['blockdevices'])) {
            return [];
        }
        
        $parsedDisks = [];
        self::parseBlockDevices($data['blockdevices'], $parsedDisks);
        return $parsedDisks;
    }
    
    private static function parseBlockDevices(array $devices, array &$parsedDisks) {
        foreach ($devices as $dev) {
            $type = $dev['type'] ?? '';
            if ($type === 'disk' || $type === 'part') {
                $mount = $dev['mountpoint'] ?? null;
                $fs = $dev['fstype'] ?? null;
                
                $status = 'Desconhecido';
                if ($mount === '/' || $mount === '/boot' || (is_string($mount) && str_starts_with($mount, '/boot/'))) {
                    $status = 'Disco de Sistema';
                } elseif (empty($fs)) {
                    $status = 'Limpo (Virgem)';
                } elseif (!empty($fs) && empty($mount)) {
                    $status = 'Formatado Desmontado';
                } elseif (!empty($mount)) {
                    $status = 'Montado';
                }
                
                $dev['classification'] = $status;
                $parsedDisks[] = $dev;
            }
            
            if (!empty($dev['children'])) {
                self::parseBlockDevices($dev['children'], $parsedDisks);
            }
        }
    }

    /**
     * Formata um disco limpo, cria ponto de montagem e adiciona ao fstab.
     */
    public static function formatDisk(string $devicePath, string $fsType, string $mountName): array {
        // Validação de segurança rígida do nome da montagem (apenas letras, números e hífens)
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $mountName)) {
            throw new \Exception(\smb_t('Invalid mount name. Use only letters, numbers, and hyphens.', 'Nome de montagem inválido. Use apenas letras, números e hífens.'));
        }
        
        $fsType = in_array($fsType, ['ext4', 'xfs']) ? $fsType : 'ext4';
        
        $devEsc = escapeshellarg($devicePath);
        $mntPath = escapeshellarg("/mnt/" . $mountName);
        
        // Formatar
        $fmtRes = Shell::execSudo("/usr/sbin/mkfs.$fsType $devEsc");
        if (!$fmtRes['success']) {
            throw new \Exception(\smb_t('Error while formatting disk: ', 'Erro ao formatar o disco: ') . $fmtRes['output']);
        }
        
        // Criar pasta de montagem
        Shell::execSudo("/usr/bin/mkdir -p $mntPath");
        
        // Obter UUID
        $uuidRes = Shell::execSudo("/usr/sbin/blkid -s UUID -o value $devEsc");
        $uuid = trim($uuidRes['output']);
        if (empty($uuid)) {
            throw new \Exception(\smb_t('Failed to get UUID after formatting.', 'Falha ao obter o UUID após a formatação.'));
        }
        
        // Montar
        $mountRes = Shell::execSudo("/usr/bin/mount $devEsc $mntPath");
        if (!$mountRes['success']) {
            throw new \Exception(\smb_t('Error while mounting disk: ', 'Erro ao montar o disco: ') . $mountRes['output']);
        }
        
        // Adicionar ao fstab (garantindo que o FSTAB fique integro)
        $fstabLine = "UUID=$uuid /mnt/$mountName $fsType defaults 0 2";
        $fstabEsc = escapeshellarg($fstabLine);
        Shell::execSudo("sh -c 'echo $fstabEsc >> /etc/fstab'");
        
        return ['success' => true, 'message' => \smb_t("Disk formatted and mounted at /mnt/$mountName.", "Disco formatado e montado em /mnt/$mountName.")];
    }

    /**
     * Importa um disco já formatado montando-o e adicionando ao fstab.
     */
    public static function importDisk(string $devicePath, string $mountName): array {
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $mountName)) {
            throw new \Exception(\smb_t('Invalid mount name. Use only letters, numbers, and hyphens.', 'Nome de montagem inválido. Use apenas letras, números e hífens.'));
        }
        
        $devEsc = escapeshellarg($devicePath);
        $mntPath = escapeshellarg("/mnt/" . $mountName);
        
        // Obter UUID e FSTYPE
        $blkidRes = Shell::execSudo("/usr/sbin/blkid -s UUID -s TYPE -o export $devEsc");
        if (!$blkidRes['success'] || empty($blkidRes['output'])) {
            throw new \Exception(\smb_t('Could not identify disk filesystem.', 'Não foi possível identificar o sistema de arquivos do disco.'));
        }
        
        $uuid = '';
        $fsType = '';
        $lines = explode("\n", trim($blkidRes['output']));
        foreach ($lines as $line) {
            if (str_starts_with($line, 'UUID=')) {
                $uuid = substr($line, 5);
            } elseif (str_starts_with($line, 'TYPE=')) {
                $fsType = substr($line, 5);
            }
        }
        
        if (empty($uuid) || empty($fsType)) {
            throw new \Exception(\smb_t('Missing UUID or TYPE data on disk.', 'Dados UUID ou TYPE ausentes no disco.'));
        }
        
        Shell::execSudo("/usr/bin/mkdir -p $mntPath");
        
        $mountRes = Shell::execSudo("/usr/bin/mount $devEsc $mntPath");
        if (!$mountRes['success']) {
            throw new \Exception(\smb_t('Error while importing and mounting disk: ', 'Erro ao importar e montar o disco: ') . $mountRes['output']);
        }
        
        $fstabLine = "UUID=$uuid /mnt/$mountName $fsType defaults 0 2";
        $fstabEsc = escapeshellarg($fstabLine);
        Shell::execSudo("sh -c 'echo $fstabEsc >> /etc/fstab'");
        
        return ['success' => true, 'message' => \smb_t("Disk imported successfully at /mnt/$mountName.", "Disco importado com sucesso em /mnt/$mountName.")];
    }

    /**
     * Desmonta o disco e remove a entrada do fstab.
     */
    public static function ejectDisk(string $mountPoint): array {
        // REGRA DE OURO: Validação rígida!
        if (!str_starts_with($mountPoint, '/mnt/')) {
            throw new \Exception(\smb_t('OPERATION DENIED. System protection is active: only disks mounted under /mnt/ can be ejected.', 'OPERAÇÃO NEGADA. Proteção de sistema ativa: só é permitido ejetar discos localizados dentro de /mnt/.'));
        }
        
        // Remover trailing slashes que podem bugar o sed
        $mountPoint = rtrim($mountPoint, '/');
        $mntEsc = escapeshellarg($mountPoint);
        
        // 1. Desmontar o disco
        $umountRes = Shell::execSudo("/usr/bin/umount $mntEsc");
        if (!$umountRes['success']) {
            throw new \Exception(\smb_t('Failed to unmount directory: ', 'Falha ao desmontar o diretório: ') . $umountRes['output']);
        }
        
        // 2. Remover do fstab
        // Usa `\|` como separador no sed, escapando o próprio mountPoint
        $sedMntEsc = escapeshellarg('\|\s' . $mountPoint . '\s|d');
        Shell::execSudo("/usr/bin/sed -i $sedMntEsc /etc/fstab");
        
        // 3. Remover diretório
        Shell::execSudo("/usr/bin/rmdir $mntEsc");
        
        return ['success' => true, 'message' => \smb_t("Disk safely ejected from $mountPoint.", "Disco ejetado com segurança de $mountPoint.")];
    }
}
