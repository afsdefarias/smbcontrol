<?php
namespace App\Controllers;

use App\Services\Shell;
use App\Services\SambaParser;

class SambaController {
    private const SMB_CONF = '/etc/samba/smb.conf';
    private const SHARES_CONF = '/etc/samba/shares.conf';

    private function getSystemUsers(): array {
        $users = [];
        $passwdFile = @file('/etc/passwd');
        if ($passwdFile) {
            foreach ($passwdFile as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) >= 3 && (int)$parts[2] >= 1000 && (int)$parts[2] < 60000) {
                    $users[] = $parts[0];
                }
            }
        }
        sort($users);
        return $users;
    }

    private function getSystemGroups(): array {
        $groups = [];
        $groupFile = @file('/etc/group');
        if ($groupFile) {
            foreach ($groupFile as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) >= 3 && (int)$parts[2] >= 1000 && (int)$parts[2] < 60000) {
                    $groups[] = $parts[0];
                }
            }
        }
        sort($groups);
        return $groups;
    }

    private function runSudo(string $command, string $label): array {
        $result = Shell::execSudo($command);
        if (!$result['success']) {
            throw new \RuntimeException($label . ': ' . ($result['output'] ?: smb_t('command failed without output.', 'comando falhou sem saída.')));
        }
        return $result;
    }

    private function readRootFile(string $path): string {
        $result = $this->runSudo('/usr/bin/cat ' . escapeshellarg($path), smb_t("Could not read $path", "Não foi possível ler $path"));
        return $result['output'];
    }

    private function writeRootFile(string $path, string $content): void {
        $tmp = '/tmp/smbcontrol_' . bin2hex(random_bytes(8));
        file_put_contents($tmp, $content);
        $this->runSudo('/usr/bin/cp ' . escapeshellarg($tmp) . ' ' . escapeshellarg($path), smb_t("Could not write $path", "Não foi possível gravar $path"));
        @unlink($tmp);
    }

    private function validateAndReload(): void {
        $this->runSudo('/usr/bin/testparm -s', smb_t('Invalid Samba configuration', 'Configuração Samba inválida'));
        $this->runSudo('/usr/bin/systemctl reload smbd', smb_t('Could not reload smbd', 'Não foi possível recarregar o smbd'));
    }

    private function isValidAccountName(string $name): bool {
        return (bool)preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
    }

    private function linuxUserExists(string $username): bool {
        exec('getent passwd ' . escapeshellarg($username) . ' >/dev/null 2>&1', $out, $code);
        return $code === 0;
    }

    private function linuxGroupExists(string $group): bool {
        exec('getent group ' . escapeshellarg($group) . ' >/dev/null 2>&1', $out, $code);
        return $code === 0;
    }

    private function sharesIncludeEnabled(): bool {
        $content = $this->readRootFile(self::SMB_CONF);
        return (bool)preg_match('/^\s*include\s*=\s*\/etc\/samba\/shares\.conf\s*$/mi', $content);
    }

    private function ensureSharesConfig(): void {
        $this->runSudo('/usr/bin/touch ' . escapeshellarg(self::SHARES_CONF), smb_t('Could not create shares.conf', 'Não foi possível criar shares.conf'));

        $content = $this->readRootFile(self::SMB_CONF);
        if ($this->sharesIncludeEnabled()) {
            return;
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $newLines = [];
        $inserted = false;
        foreach ($lines as $line) {
            $newLines[] = $line;
            if (!$inserted && preg_match('/^\s*\[global\]\s*$/i', $line)) {
                $newLines[] = '   include = /etc/samba/shares.conf';
                $inserted = true;
            }
        }
        if (!$inserted) {
            $newLines[] = '';
            $newLines[] = '[global]';
            $newLines[] = '   include = /etc/samba/shares.conf';
        }

        $newContent = rtrim(implode("\n", $newLines)) . "\n";
        $tmp = '/tmp/smbcontrol_smb_' . bin2hex(random_bytes(8));
        file_put_contents($tmp, $newContent);
        $this->runSudo('/usr/bin/testparm -s ' . escapeshellarg($tmp), smb_t('smb.conf with include is invalid', 'smb.conf com include inválido'));
        $this->runSudo('/usr/bin/cp ' . escapeshellarg($tmp) . ' ' . escapeshellarg(self::SMB_CONF), smb_t('Could not update smb.conf', 'Não foi possível atualizar smb.conf'));
        @unlink($tmp);
    }

    private function normalizeList(array $items): string {
        return implode(' ', array_values(array_unique($items)));
    }

    private function sendJson(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function assertSafeAbsolutePath(string $path): void {
        if ($path === '' || str_contains($path, "\0") || !str_starts_with($path, '/') || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            throw new \InvalidArgumentException(smb_t('Invalid path. Use an absolute path without traversal.', 'Caminho inválido. Use um caminho absoluto sem travessia de diretórios.'));
        }
    }

    private function assertSafeFolderName(string $name): void {
        if ($name === '' || str_contains($name, "\0") || str_contains($name, '/') || $name === '.' || $name === '..') {
            throw new \InvalidArgumentException(smb_t('Invalid folder name.', 'Nome de pasta inválido.'));
        }
    }

    private function listDirectories(string $path): array {
        $this->assertSafeAbsolutePath($path);
        $path = rtrim($path, '/') ?: '/';
        $command = '/usr/bin/find ' . escapeshellarg($path) . ' -mindepth 1 -maxdepth 1 -type d -printf ' . escapeshellarg("%f\t%p\n");
        $result = $this->runSudo($command, smb_t('Could not list folders', 'Não foi possível listar as pastas'));
        $directories = [];

        foreach (explode("\n", trim($result['output'])) as $line) {
            if ($line === '' || !str_contains($line, "\t")) {
                continue;
            }
            [$name, $fullPath] = explode("\t", $line, 2);
            $directories[] = [
                'name' => $name,
                'path' => $fullPath,
            ];
        }

        usort($directories, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $directories;
    }

    private function sectionFields(array $fields): array {
        $indexed = [];
        foreach ($fields as $field) {
            if (!empty($field['is_standalone_comment']) || empty($field['key'])) {
                continue;
            }
            $indexed[strtolower(trim($field['key']))] = trim($field['value'] ?? '');
        }
        return $indexed;
    }

    private function recycleShares(): array {
        $shares = [];
        $parsed = SambaParser::parse($this->readRootFile(self::SHARES_CONF));

        foreach ($parsed as $shareName => $fields) {
            if (strtolower($shareName) === 'global' || !is_array($fields)) {
                continue;
            }

            $indexed = $this->sectionFields($fields);
            $vfsObjects = preg_split('/\s+/', strtolower($indexed['vfs objects'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $path = $indexed['path'] ?? '';

            if ($path === '' || !in_array('recycle', $vfsObjects, true)) {
                continue;
            }

            $repository = $indexed['recycle:repository'] ?? '#recycle';
            $shares[$shareName] = [
                'name' => $shareName,
                'path' => rtrim($path, '/'),
                'repository' => trim($repository),
                'keeptree' => strtolower($indexed['recycle:keeptree'] ?? 'no') === 'yes',
            ];
        }

        return $shares;
    }

    private function recycleBaseRelative(string $repository): string {
        $repository = trim($repository);
        if ($repository === '') {
            return '#recycle';
        }

        $repository = preg_replace('#/%U(?:/|$).*#', '', $repository) ?: $repository;
        $repository = preg_replace('#/%u(?:/|$).*#', '', $repository) ?: $repository;
        if (str_starts_with($repository, '/')) {
            return ltrim(basename($repository), '/');
        }

        return trim($repository, '/');
    }

    private function splitRecyclePath(array $share, string $relativePath): array {
        $base = $this->recycleBaseRelative($share['repository']);
        $path = trim($relativePath, '/');
        $insideRecycle = $path;

        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $insideRecycle = trim(substr($path, strlen($base)), '/');
        }

        $user = smb_t('Unknown', 'Desconhecido');
        $originalRelative = $insideRecycle;
        if (preg_match('#%(U|u)#', $share['repository']) && $insideRecycle !== '') {
            $parts = explode('/', $insideRecycle, 2);
            $user = $parts[0] !== '' ? $parts[0] : $user;
            $originalRelative = $parts[1] ?? basename($relativePath);
        }

        if ($originalRelative === '') {
            $originalRelative = basename($relativePath);
        }

        return [$user, $originalRelative];
    }

    private function assertSafeRelativePath(string $path): void {
        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            throw new \InvalidArgumentException(smb_t('Invalid recycle item path.', 'Caminho do item da lixeira inválido.'));
        }
    }

    private function listRecycleItems(array $shares, string $filter): array {
        $items = [];

        foreach ($shares as $share) {
            $base = $this->recycleBaseRelative($share['repository']);
            $recycleRoot = $share['path'] . '/' . $base;
            $findCommand = '/usr/bin/find ' . escapeshellarg($recycleRoot) . ' -type f -printf ' . escapeshellarg("%p\t%s\t%T@\t%TY-%Tm-%Td %TH:%TM\n");
            $result = Shell::execSudo($findCommand);

            if (!$result['success'] || trim($result['output']) === '') {
                continue;
            }

            foreach (explode("\n", trim($result['output'])) as $line) {
                $parts = explode("\t", $line);
                if (count($parts) < 4) {
                    continue;
                }

                [$absolutePath, $size, $mtimeSort, $mtimeDisplay] = $parts;
                $sharePath = $share['path'] . '/';
                if (!str_starts_with($absolutePath, $sharePath)) {
                    continue;
                }

                $relativePath = substr($absolutePath, strlen($sharePath));
                [$user, $originalRelative] = $this->splitRecyclePath($share, $relativePath);
                $fileName = basename($relativePath);

                if ($filter !== '' && stripos($fileName, $filter) === false && stripos($originalRelative, $filter) === false) {
                    continue;
                }

                $items[] = [
                    'share' => $share['name'],
                    'user' => $user,
                    'name' => $fileName,
                    'size' => (int)$size,
                    'deleted_at' => $mtimeDisplay,
                    'sort_time' => (float)$mtimeSort,
                    'recycle_path' => $relativePath,
                    'original_path' => $share['name'] . '/' . $originalRelative,
                ];
            }
        }

        usort($items, fn($a, $b) => $b['sort_time'] <=> $a['sort_time']);
        return $items;
    }

    private function restoreRecycleItem(array $shares, string $shareName, string $relativePath): void {
        if (!isset($shares[$shareName])) {
            throw new \InvalidArgumentException(smb_t('Recycle share not found.', 'Compartilhamento com lixeira não encontrado.'));
        }

        $this->assertSafeRelativePath($relativePath);
        $share = $shares[$shareName];
        $base = $this->recycleBaseRelative($share['repository']);
        if ($base !== '' && !str_starts_with(trim($relativePath, '/'), $base . '/')) {
            throw new \InvalidArgumentException(smb_t('Selected item is outside the recycle bin.', 'O item selecionado está fora da lixeira.'));
        }

        [, $originalRelative] = $this->splitRecyclePath($share, $relativePath);
        $this->assertSafeRelativePath($originalRelative);

        $source = $share['path'] . '/' . trim($relativePath, '/');
        $destination = $share['path'] . '/' . trim($originalRelative, '/');
        $destinationDir = dirname($destination);

        $script = 'if [ ! -e "$1" ]; then exit 10; fi; if [ -e "$2" ]; then exit 11; fi; mkdir -p "$3" && mv "$1" "$2"';
        $command = '/bin/sh -c ' . escapeshellarg($script) . ' smbcontrol ' . escapeshellarg($source) . ' ' . escapeshellarg($destination) . ' ' . escapeshellarg($destinationDir);
        $result = Shell::execSudo($command);

        if (!$result['success']) {
            if ($result['code'] === 10) {
                throw new \RuntimeException(smb_t('Recycle item no longer exists.', 'O item da lixeira não existe mais.'));
            }
            if ($result['code'] === 11) {
                throw new \RuntimeException(smb_t('The original path already has a file with this name. Move or rename it before restoring.', 'O caminho original já tem um arquivo com este nome. Mova ou renomeie antes de restaurar.'));
            }
            throw new \RuntimeException(smb_t('Could not restore recycle item: ', 'Não foi possível restaurar o item da lixeira: ') . $result['output']);
        }
    }

    private function setSambaPassword(string $username, string $password): void {
        $tmpPass = '/tmp/smbcontrol_smbpass_' . bin2hex(random_bytes(12));
        $userEsc = escapeshellarg($username);

        try {
            if (file_put_contents($tmpPass, $password . "\n" . $password . "\n", LOCK_EX) === false) {
                throw new \RuntimeException(smb_t('Could not create temporary Samba password file.', 'Não foi possível criar o arquivo temporário da senha Samba.'));
            }
            chmod($tmpPass, 0600);
            $this->runSudo("/usr/bin/smbpasswd -a -s $userEsc < " . escapeshellarg($tmpPass), smb_t('Could not save Samba password', 'Não foi possível gravar a senha Samba'));
            $this->runSudo("/usr/bin/smbpasswd -e $userEsc", smb_t('Could not enable Samba user', 'Não foi possível ativar o usuário Samba'));
        } finally {
            if (is_file($tmpPass)) {
                @unlink($tmpPass);
            }
        }
    }

    private function getSmbUserActivityStats(): array {
        $stats = [];
        $result = Shell::execSudo('/usr/bin/cat /var/log/syslog');
        if (!$result['success'] || trim($result['output']) === '') {
            return $stats;
        }

        foreach (explode("\n", trim($result['output'])) as $line) {
            if (!str_contains($line, 'smbd_audit:')) {
                continue;
            }

            [$prefix, $auditPart] = explode('smbd_audit:', $line, 2);
            $timestamp = strtok(trim($prefix), ' ') ?: '';
            $fields = explode('|', trim($auditPart));
            $username = $fields[0] ?? '';
            $operation = strtolower($fields[3] ?? '');

            if ($username === '' || $username === '?') {
                continue;
            }

            if (!isset($stats[$username])) {
                $stats[$username] = [
                    'activities' => 0,
                    'last_seen' => '',
                ];
            }

            if ($operation === 'connect') {
                $stats[$username]['activities']++;
            }
            if ($timestamp !== '' && $timestamp > $stats[$username]['last_seen']) {
                $stats[$username]['last_seen'] = $timestamp;
            }
        }

        return $stats;
    }

    private function formatAuditTimestamp(string $timestamp): string {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);
        if ($time === false) {
            return $timestamp;
        }

        return date('Y-m-d H:i', $time);
    }

    private function getGroupRecords(array $systemUsers, array $systemGroups): array {
        $passwdGroups = [];
        $passwdFile = @file('/etc/passwd') ?: [];
        foreach ($passwdFile as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) >= 4 && in_array($parts[0], $systemUsers, true)) {
                $passwdGroups[(int)$parts[3]][] = $parts[0];
            }
        }

        $groupsToShow = array_fill_keys($systemGroups, true);

        $records = [];
        $groupFile = @file('/etc/group') ?: [];
        foreach ($groupFile as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) < 4 || !isset($groupsToShow[$parts[0]])) {
                continue;
            }

            $gid = (int)$parts[2];
            $members = array_filter(array_map('trim', explode(',', $parts[3])));
            foreach ($passwdGroups[$gid] ?? [] as $primaryMember) {
                $members[] = $primaryMember;
            }
            $members = array_values(array_unique($members));
            sort($members);

            $records[$parts[0]] = [
                'name' => $parts[0],
                'gid' => $gid,
                'members' => $members,
                'manageable' => $gid >= 1000 && $gid < 60000,
            ];
        }

        uasort($records, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $records;
    }

    private function getGroupShareAccess(array $groupNames): array {
        $access = array_fill_keys($groupNames, []);

        try {
            $parsed = SambaParser::parse($this->readRootFile(self::SHARES_CONF));
        } catch (\Exception $e) {
            return $access;
        }

        foreach ($parsed as $shareName => $fields) {
            if (strtolower($shareName) === 'global' || !is_array($fields)) {
                continue;
            }

            $indexed = $this->sectionFields($fields);
            $readOnly = strtolower($indexed['read only'] ?? 'yes');
            $validUsers = preg_split('/[\s,]+/', $indexed['valid users'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
            $readList = preg_split('/[\s,]+/', $indexed['read list'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
            $writeList = preg_split('/[\s,]+/', $indexed['write list'] ?? '', -1, PREG_SPLIT_NO_EMPTY);

            foreach ($groupNames as $groupName) {
                $principal = '@' . $groupName;
                if (in_array($principal, $writeList, true)) {
                    $access[$groupName][] = ['share' => $shareName, 'permission' => 'write'];
                } elseif (in_array($principal, $readList, true)) {
                    $access[$groupName][] = ['share' => $shareName, 'permission' => 'read'];
                } elseif (in_array($principal, $validUsers, true)) {
                    $access[$groupName][] = ['share' => $shareName, 'permission' => $readOnly === 'no' ? 'write' : 'read'];
                }
            }
        }

        return $access;
    }

    public function conf() {
        try {
            $this->ensureSharesConfig();
            $content = $this->readRootFile(self::SMB_CONF);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $content = '';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newContent = SambaParser::generate($_POST['config'] ?? []);
            $tempFile = '/tmp/smb.conf.tmp';
            file_put_contents($tempFile, $newContent);
            $testResult = Shell::execSudo('/usr/bin/testparm -s ' . escapeshellarg($tempFile));

            if ($testResult['success']) {
                try {
                    $this->writeRootFile(self::SMB_CONF, $newContent);
                    $this->validateAndReload();
                    $_SESSION['message'] = smb_t('Global configuration saved, validated by testparm, and applied to smbd.', 'Configuração global salva, validada pelo testparm e aplicada ao smbd.');
                    @unlink($tempFile);
                    header('Location: /samba/conf');
                    exit;
                } catch (\Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                }
            } else {
                $_SESSION['error'] = smb_t('Validation error (testparm): ', 'Erro de validação (testparm): ') . $testResult['output'];
            }
            $content = $newContent;
        }

        $parsedConf = SambaParser::parse($content);
        require __DIR__ . '/../Views/smbconf.php';
    }

    public function sharesConf() {
        try {
            $this->ensureSharesConfig();
            $content = $this->readRootFile(self::SHARES_CONF);
            $sharesIncluded = $this->sharesIncludeEnabled();
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $content = '';
            $sharesIncluded = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newContent = SambaParser::generate($_POST['config'] ?? []);
            try {
                $backup = $content;
                $this->writeRootFile(self::SHARES_CONF, $newContent);
                $testResult = Shell::execSudo('/usr/bin/testparm -s');

                if ($testResult['success']) {
                    $this->runSudo('/usr/bin/systemctl reload smbd', smb_t('Could not reload smbd', 'Não foi possível recarregar o smbd'));
                    $_SESSION['message'] = smb_t('Share parameters saved, validated, and applied to Samba.', 'Parâmetros dos compartilhamentos salvos, validados e aplicados ao Samba.');
                    header('Location: /samba/shares-config');
                    exit;
                }

                $this->writeRootFile(self::SHARES_CONF, $backup);
                $_SESSION['error'] = smb_t('Validation error (testparm): ', 'Erro de validação (testparm): ') . $testResult['output'];
                $content = $newContent;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $content = $newContent;
            }
        }

        $parsedConf = SambaParser::parse($content);
        require __DIR__ . '/../Views/sharesconf.php';
    }

    public function shares() {
        $systemUsers = array_values(array_unique(array_merge(['root'], $this->getSystemUsers())));
        $systemGroups = array_values(array_unique(array_merge(['root'], $this->getSystemGroups())));
        $sharesIncluded = false;

        try {
            $this->ensureSharesConfig();
            $sharesIncluded = $this->sharesIncludeEnabled();
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';

            try {
                if ($action === 'delete') {
                    $shareName = $_POST['share_name'] ?? '';
                    if (!$this->isValidAccountName($shareName)) {
                        throw new \InvalidArgumentException(smb_t('Invalid share name.', 'Nome de compartilhamento inválido.'));
                    }

                    $parsed = SambaParser::parse($this->readRootFile(self::SHARES_CONF));
                    if (!isset($parsed[$shareName])) {
                        throw new \InvalidArgumentException(smb_t("Share $shareName does not exist in shares.conf.", "O compartilhamento $shareName não existe no shares.conf."));
                    }

                    unset($parsed[$shareName]);
                    $this->writeRootFile(self::SHARES_CONF, SambaParser::generate($parsed));
                    $this->validateAndReload();
                    $_SESSION['message'] = smb_t("Share $shareName removed from shares.conf and applied to Samba.", "Compartilhamento $shareName removido do shares.conf e aplicado ao Samba.");
                    header('Location: /samba/shares');
                    exit;
                }

                $name = trim($_POST['name'] ?? '');
                $path = trim($_POST['path'] ?? '');
                $ownerUser = $_POST['owner_user'] ?? 'root';
                $ownerGroup = $_POST['owner_group'] ?? 'root';

                if (!$this->isValidAccountName($name)) {
                    throw new \InvalidArgumentException(smb_t('Invalid name. Use only letters, numbers, dot, hyphen, and underscore.', 'Nome inválido. Use apenas letras, números, ponto, hífen e sublinhado.'));
                }
                if ($path === '' || strpos($path, '..') !== false || !str_starts_with($path, '/')) {
                    throw new \InvalidArgumentException(smb_t("Invalid path. Use an absolute path without '..'.", "Caminho inválido. Use um caminho absoluto e sem '..'."));
                }
                if (!in_array($ownerUser, $systemUsers, true) || !in_array($ownerGroup, $systemGroups, true)) {
                    throw new \InvalidArgumentException(smb_t('Invalid owner user or group.', 'Usuário ou grupo dono inválido.'));
                }

                $pathEsc = escapeshellarg($path);
                $owner = escapeshellarg($ownerUser . ':' . $ownerGroup);

                $this->runSudo("/usr/bin/mkdir -p $pathEsc", smb_t('Could not create Linux folder', 'Não foi possível criar a pasta Linux'));
                $this->runSudo("/usr/bin/chown $owner $pathEsc", smb_t('Could not set folder owner', 'Não foi possível ajustar o dono da pasta'));
                $this->runSudo("/usr/bin/chmod 0770 $pathEsc", smb_t('Could not set folder mode', 'Não foi possível ajustar o modo da pasta'));
                $this->runSudo("/usr/bin/setfacl -b $pathEsc", smb_t('Could not clear old ACLs', 'Não foi possível limpar ACLs antigas'));
                $this->runSudo('/usr/bin/setfacl -m ' . escapeshellarg("u:$ownerUser:rwx") . ' -m ' . escapeshellarg("g:$ownerGroup:rwx") . ' ' . $pathEsc, smb_t('Could not apply owner ACL', 'Não foi possível aplicar ACL do dono'));

                $readList = [];
                $writeList = [];

                foreach (($_POST['user_perms'] ?? []) as $usr => $perm) {
                    if (!in_array($usr, $systemUsers, true) || $perm === 'none') {
                        continue;
                    }
                    $acl = $perm === 'write' ? 'rwx' : 'rx';
                    $targetList = $perm === 'write' ? 'writeList' : 'readList';
                    ${$targetList}[] = $usr;
                    $this->runSudo('/usr/bin/setfacl -m ' . escapeshellarg("u:$usr:$acl") . ' -m ' . escapeshellarg("d:u:$usr:$acl") . ' ' . $pathEsc, smb_t("Could not apply ACL for user $usr", "Não foi possível aplicar ACL do usuário $usr"));
                }

                foreach (($_POST['group_perms'] ?? []) as $grp => $perm) {
                    if (!in_array($grp, $systemGroups, true) || $perm === 'none') {
                        continue;
                    }
                    $acl = $perm === 'write' ? 'rwx' : 'rx';
                    $targetList = $perm === 'write' ? 'writeList' : 'readList';
                    ${$targetList}[] = '@' . $grp;
                    $this->runSudo('/usr/bin/setfacl -m ' . escapeshellarg("g:$grp:$acl") . ' -m ' . escapeshellarg("d:g:$grp:$acl") . ' ' . $pathEsc, smb_t("Could not apply ACL for group $grp", "Não foi possível aplicar ACL do grupo $grp"));
                }

                if (empty($readList) && empty($writeList)) {
                    $writeList[] = $ownerUser;
                    $writeList[] = '@' . $ownerGroup;
                }

                $validUsers = array_merge($readList, $writeList);
                $block = "\n[$name]\n";
                $block .= "   path = $path\n";
                $block .= "   guest ok = no\n";
                $block .= "   force group = $ownerGroup\n";
                $block .= "   create mask = 0660\n";
                $block .= "   directory mask = 0770\n";

                if (!empty($validUsers)) {
                    $block .= "   read only = yes\n";
                    $block .= "   valid users = " . $this->normalizeList($validUsers) . "\n";
                    if (!empty($readList)) {
                        $block .= "   read list = " . $this->normalizeList($readList) . "\n";
                    }
                    if (!empty($writeList)) {
                        $block .= "   write list = " . $this->normalizeList($writeList) . "\n";
                    }
                } else {
                    $block .= "   read only = no\n";
                }

                if (($_POST['hide_network'] ?? '') === '1') {
                    $block .= "   browseable = no\n";
                }
                if (($_POST['hide_unreadable'] ?? '') === '1') {
                    $block .= "   hide unreadable = yes\n";
                }

                $vfsObjects = [];
                if (($_POST['enable_recycle'] ?? '') === '1') {
                    $vfsObjects[] = 'recycle';
                    $block .= "   recycle:repository = #recycle/%U\n";
                    $block .= "   recycle:keeptree = yes\n";
                    $block .= "   recycle:versions = yes\n";
                    $block .= "   recycle:exclude_dir = tmp, cache\n";
                    if (($_POST['recycle_admin'] ?? '') === '1') {
                        $block .= "   recycle:directory_mode = 0700\n";
                    }
                }
                if (($_POST['enable_audit'] ?? '') === 'yes') {
                    $vfsObjects[] = 'full_audit';
                    $block .= "   full_audit:prefix = %u|%I|%S\n";
                    $block .= "   full_audit:success = connect disconnect mkdirat unlinkat pread pwrite renameat\n";
                    $block .= "   full_audit:failure = connect\n";
                    $block .= "   full_audit:facility = local7\n";
                    $block .= "   full_audit:priority = notice\n";
                }
                if (!empty($vfsObjects)) {
                    $block .= "   vfs objects = " . implode(' ', $vfsObjects) . "\n";
                }

                $parsed = SambaParser::parse($this->readRootFile(self::SHARES_CONF));
                $shareBlock = SambaParser::parse($block);
                $parsed[$name] = $shareBlock[$name] ?? [];
                $this->writeRootFile(self::SHARES_CONF, SambaParser::generate($parsed));
                $this->validateAndReload();

                $_SESSION['message'] = smb_t("Share $name created/updated in shares.conf, folder created at $path, and smbd reloaded.", "Compartilhamento $name criado/atualizado em shares.conf, pasta criada em $path e smbd recarregado.");
                header('Location: /samba/shares');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /samba/shares');
                exit;
            }
        }

        $existingShares = [];
        try {
            $sharesConfContent = $this->readRootFile(self::SHARES_CONF);
            if (trim($sharesConfContent) !== '') {
                $existingShares = SambaParser::parse($sharesConfContent);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        require __DIR__ . '/../Views/shares.php';
    }

    public function recycle() {
        $filter = trim($_GET['q'] ?? '');
        $items = [];
        $recycleShares = [];

        try {
            $recycleShares = $this->recycleShares();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $shareName = $_POST['share'] ?? '';
                $relativePath = $_POST['recycle_path'] ?? '';
                $this->restoreRecycleItem($recycleShares, $shareName, $relativePath);
                $_SESSION['message'] = smb_t('Item restored to its original location.', 'Item restaurado para o local original.');
                header('Location: /recycle');
                exit;
            }

            $items = $this->listRecycleItems($recycleShares, $filter);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        require __DIR__ . '/../Views/recycle.php';
    }

    public function pathBrowser() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $parent = trim($_POST['parent'] ?? '/srv/samba');
                $name = trim($_POST['name'] ?? '');
                $this->assertSafeAbsolutePath($parent);
                $this->assertSafeFolderName($name);

                $newPath = rtrim($parent, '/') . '/' . $name;
                $this->assertSafeAbsolutePath($newPath);
                $this->runSudo('/usr/bin/mkdir -p ' . escapeshellarg($newPath), smb_t('Could not create folder', 'Não foi possível criar a pasta'));

                $this->sendJson([
                    'ok' => true,
                    'path' => $newPath,
                    'directories' => $this->listDirectories($parent),
                ]);
            }

            $path = trim($_GET['path'] ?? '/srv/samba');
            $path = rtrim($path, '/') ?: '/';
            try {
                $directories = $this->listDirectories($path);
            } catch (\Exception $e) {
                if ($path === '/') {
                    throw $e;
                }
                $path = dirname($path);
                $directories = $this->listDirectories($path);
            }

            $this->sendJson([
                'ok' => true,
                'path' => $path,
                'parent' => dirname(rtrim($path, '/') ?: '/'),
                'directories' => $directories,
            ]);
        } catch (\Exception $e) {
            $this->sendJson([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function users() {
        $systemUsers = $this->getSystemUsers();
        $systemGroups = $this->getSystemGroups();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            try {
                if ($action === 'create_user') {
                    $username = trim($_POST['username'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $groups = $_POST['groups'] ?? [];

                    if (!$this->isValidAccountName($username)) {
                        throw new \InvalidArgumentException(smb_t('Invalid username.', 'Nome de usuário inválido.'));
                    }

                    $userExists = $this->linuxUserExists($username);
                    if (!$userExists && $password === '') {
                        throw new \InvalidArgumentException(smb_t('Enter a password to create the Samba user.', 'Informe uma senha para criar o usuário Samba.'));
                    }

                    $userEsc = escapeshellarg($username);
                    if (!$userExists) {
                        $mFlag = isset($_POST['create_home']) && $_POST['create_home'] == '1' ? '-m' : '-M';
                        $nFlag = isset($_POST['create_user_group']) && $_POST['create_user_group'] == '1' ? '-U' : '-N';
                        $this->runSudo("/usr/sbin/useradd $nFlag $mFlag -s /usr/sbin/nologin $userEsc", smb_t('Could not create Linux user', 'Não foi possível criar o usuário Linux'));
                    }

                    $validGroups = [];
                    foreach ($groups as $group) {
                        if (in_array($group, $systemGroups, true)) {
                            $validGroups[] = $group;
                        }
                    }
                    if (!empty($validGroups)) {
                        $groupsStr = escapeshellarg(implode(',', $validGroups));
                        $this->runSudo("/usr/sbin/usermod -a -G $groupsStr $userEsc", smb_t('Could not add user to groups', 'Não foi possível adicionar o usuário aos grupos'));
                    }

                    if ($password !== '') {
                        $this->setSambaPassword($username, $password);
                        $_SESSION['message'] = smb_t("User $username created/updated in Linux and Samba.", "Usuário $username criado/atualizado no Linux e no Samba.");
                    } else {
                        $_SESSION['message'] = smb_t("User $username updated in Linux groups. Samba password kept unchanged.", "Usuário $username atualizado nos grupos Linux. Senha Samba mantida.");
                    }

                    header('Location: /samba/users');
                    exit;
                }

	                if ($action === 'create_group') {
	                    $groupname = trim($_POST['groupname'] ?? '');
	                    $users = $_POST['users'] ?? [];

	                    if (!$this->isValidAccountName($groupname)) {
	                        throw new \InvalidArgumentException(smb_t('Invalid group name.', 'Nome de grupo inválido.'));
	                    }

	                    $groupEsc = escapeshellarg($groupname);
	                    $groupExists = $this->linuxGroupExists($groupname);
	                    if ($groupExists) {
	                        $existing = $this->getGroupRecords($systemUsers, array_values(array_unique(array_merge($systemGroups, [$groupname]))));
	                        if (isset($existing[$groupname]) && !$existing[$groupname]['manageable']) {
	                            throw new \InvalidArgumentException(smb_t('System/default groups cannot be managed here. Create a department group instead.', 'Grupos de sistema/padrão não podem ser gerenciados aqui. Crie um grupo de setor.'));
	                        }
	                    } else {
	                        $this->runSudo("/usr/sbin/groupadd $groupEsc", smb_t('Could not create Linux group', 'Não foi possível criar o grupo Linux'));
	                    }

	                    $validUsers = [];
	                    foreach ($users as $user) {
                        if (in_array($user, $systemUsers, true)) {
	                            $validUsers[] = $user;
	                        }
	                    }
	                    $usersStr = escapeshellarg(implode(',', $validUsers));
	                    $this->runSudo("/usr/bin/gpasswd -M $usersStr $groupEsc", smb_t('Could not update group members', 'Não foi possível atualizar membros do grupo'));

	                    $_SESSION['message'] = smb_t("Group $groupname created/updated in Linux.", "Grupo $groupname criado/atualizado no Linux.");
	                    header('Location: /samba/users');
	                    exit;
	                }

	                if ($action === 'delete_group') {
	                    $groupname = trim($_POST['groupname'] ?? '');
	                    if (!$this->isValidAccountName($groupname) || !$this->linuxGroupExists($groupname)) {
	                        throw new \InvalidArgumentException(smb_t('Invalid group or group not found.', 'Grupo inválido ou não encontrado.'));
	                    }

	                    $existing = $this->getGroupRecords($systemUsers, array_values(array_unique(array_merge($systemGroups, [$groupname]))));
	                    if (isset($existing[$groupname]) && !$existing[$groupname]['manageable']) {
	                        throw new \InvalidArgumentException(smb_t('System/default groups cannot be deleted here.', 'Grupos de sistema/padrão não podem ser excluídos aqui.'));
	                    }

	                    $this->runSudo('/usr/sbin/groupdel ' . escapeshellarg($groupname), smb_t('Could not delete Linux group', 'Não foi possível excluir o grupo Linux'));
	                    $_SESSION['message'] = smb_t("Group $groupname deleted from Linux.", "Grupo $groupname excluído do Linux.");
	                    header('Location: /samba/users');
	                    exit;
	                }

                if (in_array($action, ['delete_user', 'enable_user', 'disable_user'], true)) {
                    $targetUser = $_POST['target_user'] ?? '';
                    if (!in_array($targetUser, $systemUsers, true)) {
                        throw new \InvalidArgumentException(smb_t('Invalid user or user not found.', 'Usuário inválido ou não encontrado.'));
                    }
                    $userEsc = escapeshellarg($targetUser);

                    if ($action === 'delete_user') {
                        $this->runSudo("/usr/bin/smbpasswd -x $userEsc", smb_t('Could not remove Samba user', 'Não foi possível remover o usuário do Samba'));
                        $this->runSudo("/usr/sbin/userdel -r $userEsc", smb_t('Could not remove Linux user', 'Não foi possível remover o usuário Linux'));
                        $_SESSION['message'] = smb_t("User $targetUser removed from Samba and Linux.", "Usuário $targetUser removido do Samba e do Linux.");
                    } elseif ($action === 'enable_user') {
                        $this->runSudo("/usr/bin/smbpasswd -e $userEsc", smb_t('Could not enable Samba user', 'Não foi possível ativar o usuário Samba'));
                        $_SESSION['message'] = smb_t("User $targetUser enabled in Samba.", "Usuário $targetUser ativado no Samba.");
                    } else {
                        $this->runSudo("/usr/bin/smbpasswd -d $userEsc", smb_t('Could not disable Samba user', 'Não foi possível desativar o usuário Samba'));
                        $_SESSION['message'] = smb_t("User $targetUser disabled in Samba.", "Usuário $targetUser desativado no Samba.");
                    }
                    header('Location: /samba/users');
                    exit;
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /samba/users');
                exit;
            }
        }

        $sambaUsers = [];
        $smbMemberUsers = [];
        $activityStats = $this->getSmbUserActivityStats();
        $pdbOutput = shell_exec('sudo -n /usr/bin/pdbedit -L -w 2>&1');

        if ($pdbOutput !== null) {
            if (strpos($pdbOutput, 'password is required') !== false || strpos($pdbOutput, 'sudo:') !== false) {
                $sambaUsers = null;
            } else {
                $lines = explode("\n", trim($pdbOutput));
                foreach ($lines as $line) {
                    if (empty($line) || strpos($line, ':') === false) {
                        continue;
                    }
                    $parts = explode(':', $line);
	                    if (count($parts) >= 5) {
	                        $username = $parts[0];
	                        if (in_array($username, $systemUsers, true)) {
	                            $stats = $activityStats[$username] ?? ['activities' => 0, 'last_seen' => ''];
	                            $smbMemberUsers[] = $username;
	                            $sambaUsers[] = [
	                                'username' => $username,
	                                'disabled' => strpos($parts[4], 'D') !== false,
	                                'groups' => trim(shell_exec('id -Gn ' . escapeshellarg($username))),
	                                'activities' => $stats['activities'],
	                                'last_seen' => $this->formatAuditTimestamp($stats['last_seen']),
	                            ];
	                        }
	                    }
                }
            }
        }

        sort($smbMemberUsers);

        $groupRecords = $this->getGroupRecords($systemUsers, $systemGroups);
        $groupShareAccess = $this->getGroupShareAccess(array_keys($groupRecords));

        require __DIR__ . '/../Views/users.php';
    }
}
