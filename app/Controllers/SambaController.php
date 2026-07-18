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
                    $block .= "   recycle:repository = #recycle\n";
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
                    if (!$this->linuxGroupExists($groupname)) {
                        $this->runSudo("/usr/sbin/groupadd $groupEsc", smb_t('Could not create Linux group', 'Não foi possível criar o grupo Linux'));
                    }

                    $validUsers = [];
                    foreach ($users as $user) {
                        if (in_array($user, $systemUsers, true)) {
                            $validUsers[] = $user;
                        }
                    }
                    if (!empty($validUsers)) {
                        $usersStr = escapeshellarg(implode(',', $validUsers));
                        $this->runSudo("/usr/bin/gpasswd -M $usersStr $groupEsc", smb_t('Could not update group members', 'Não foi possível atualizar membros do grupo'));
                    }

                    $_SESSION['message'] = smb_t("Group $groupname created/updated in Linux.", "Grupo $groupname criado/atualizado no Linux.");
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
                            $sambaUsers[] = [
                                'username' => $username,
                                'disabled' => strpos($parts[4], 'D') !== false,
                                'groups' => trim(shell_exec('id -Gn ' . escapeshellarg($username)))
                            ];
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../Views/users.php';
    }
}
