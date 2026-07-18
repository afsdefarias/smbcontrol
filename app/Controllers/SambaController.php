<?php
namespace App\Controllers;

use App\Services\Shell;
use App\Services\SambaParser;

class SambaController {
    private function getSystemUsers(): array {
        $users = [];
        $passwdFile = @file('/etc/passwd');
        if ($passwdFile) {
            foreach ($passwdFile as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) >= 3 && $parts[2] >= 1000 && $parts[2] < 60000) {
                    $users[] = $parts[0];
                }
            }
        }
        return $users;
    }

    private function getSystemGroups(): array {
        $groups = [];
        $groupFile = @file('/etc/group');
        if ($groupFile) {
            foreach ($groupFile as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) >= 3 && $parts[2] >= 1000 && $parts[2] < 60000) {
                    $groups[] = $parts[0];
                }
            }
        }
        return $groups;
    }

    public function conf() {
        $smbConfPath = '/etc/samba/smb.conf';
        $content = Shell::execSudo("/usr/bin/cat $smbConfPath")['output'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configPost = $_POST['config'] ?? [];
            $newContent = SambaParser::generate($configPost);
            
            // Salvar temporariamente para rodar o testparm
            $tempFile = '/tmp/smb.conf.tmp';
            file_put_contents($tempFile, $newContent);
            
            // testparm -s valida a configuração
            $testResult = Shell::execSudo("/usr/bin/testparm -s $tempFile");
            
            if ($testResult['code'] === 0) {
                // Copiar o arquivo validado para o destino real
                Shell::execSudo("/usr/bin/cp $tempFile $smbConfPath");
                $_SESSION['message'] = "smb.conf salvo e validado com sucesso.";
                unlink($tempFile);
                header('Location: /samba/conf');
                exit;
            } else {
                $_SESSION['error'] = "Erro de validação (testparm): " . nl2br(htmlspecialchars($testResult['output']));
                $content = $newContent; // Mantém o que o usuário digitou na tela
            }
        }
        
        // Parse via SambaParser
        $parsedConf = SambaParser::parse($content);
        
        require __DIR__ . '/../Views/smbconf.php';
    }

    public function sharesConf() {
        $sharesConfPath = '/etc/samba/shares.conf';
        $content = Shell::execSudo("/usr/bin/cat $sharesConfPath")['output'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configPost = $_POST['config'] ?? [];
            $newContent = SambaParser::generate($configPost);
            
            $tempFile = '/tmp/shares.conf.tmp';
            file_put_contents($tempFile, $newContent);
            
            // Validate with testparm
            // testparm -s without arguments checks smb.conf which typically includes shares.conf
            // However, to check just the shares file isolated, testparm doesn't have a direct flag if it's included,
            // but we can try to testparm the global smb.conf while temporarily replacing the shares.conf
            
            // To be safe and simple, we can just save it. Or better, we can validate the global smb.conf.
            // Let's just backup and overwrite, then testparm. If fail, restore.
            Shell::execSudo("/usr/bin/cp $sharesConfPath /tmp/shares.conf.bak");
            Shell::execSudo("/usr/bin/cp $tempFile $sharesConfPath");
            
            $testResult = Shell::execSudo("/usr/bin/testparm -s");
            
            if ($testResult['code'] === 0) {
                $_SESSION['message'] = "shares.conf salvo e validado com sucesso.";
                Shell::execSudo("/usr/bin/systemctl reload smbd");
                header('Location: /samba/shares-config');
                exit;
            } else {
                // Restore backup
                Shell::execSudo("/usr/bin/cp /tmp/shares.conf.bak $sharesConfPath");
                $_SESSION['error'] = "Erro de validação (testparm): " . nl2br(htmlspecialchars($testResult['output']));
                $content = $newContent;
            }
            @unlink($tempFile);
            Shell::execSudo("/usr/bin/rm -f /tmp/shares.conf.bak");
        }
        
        $parsedConf = SambaParser::parse($content);
        
        require __DIR__ . '/../Views/sharesconf.php';
    }

    public function shares() {
        // Pre-carregar usuários e grupos para validação e exibição
        $systemUsers = $this->getSystemUsers();
        array_unshift($systemUsers, 'root');
        
        $systemGroups = $this->getSystemGroups();
        array_unshift($systemGroups, 'root');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';
            
            if ($action === 'delete') {
                $shareName = $_POST['share_name'] ?? '';
                if ($shareName) {
                    $content = shell_exec("sudo -n /usr/bin/cat /etc/samba/shares.conf 2>/dev/null");
                    if ($content) {
                        $parsed = SambaParser::parse($content);
                        if (isset($parsed[$shareName])) {
                            unset($parsed[$shareName]);
                            $newConf = SambaParser::generate($parsed);
                            $tmp = '/tmp/shares_conf.tmp';
                            file_put_contents($tmp, $newConf);
                            Shell::execSudo("sh -c 'cat $tmp > /etc/samba/shares.conf'");
                            unlink($tmp);
                            Shell::execSudo("/usr/bin/systemctl reload smbd");
                            $_SESSION['message'] = "Compartilhamento $shareName removido com sucesso.";
                        }
                    }
                }
                header('Location: /samba/shares');
                exit;
            }

            $name = $_POST['name'] ?? '';
            $path = $_POST['path'] ?? '';
            $ownerUser = $_POST['owner_user'] ?? 'root';
            $ownerGroup = $_POST['owner_group'] ?? 'root';
            $groupPerms = $_POST['group_perms'] ?? [];
            
            // Validações Anti-Hacker
            // 1. Path Traversal & Formato de Path
            if (strpos($path, '..') !== false || !str_starts_with($path, '/')) {
                $_SESSION['error'] = "Caminho inválido. O caminho deve ser absoluto (começar com /) e não conter '..'.";
                header('Location: /samba/shares');
                exit;
            }
            
            // 2. Validação de Owner/Group (evita forjar inputs no form)
            if (!in_array($ownerUser, $systemUsers) || !in_array($ownerGroup, $systemGroups)) {
                $_SESSION['error'] = "Usuário ou grupo dono inválido.";
                header('Location: /samba/shares');
                exit;
            }

            if ($name && $path) {
                $owner = escapeshellarg($ownerUser . ':' . $ownerGroup);
                $pathEsc = escapeshellarg($path);
                
                Shell::execSudo("/usr/bin/mkdir -p $pathEsc");
                Shell::execSudo("/usr/bin/chown $owner $pathEsc");
                Shell::execSudo("/usr/bin/chmod 0770 $pathEsc"); // Secure base permission
                
                // Clear previous ACLs and set base owner/group ACLs
                Shell::execSudo("/usr/bin/setfacl -b $pathEsc");
                $ownerUserEsc = escapeshellarg($ownerUser);
                $ownerGroupEsc = escapeshellarg($ownerGroup);
                Shell::execSudo("/usr/bin/setfacl -m u:$ownerUserEsc:rwx,g:$ownerGroupEsc:rwx $pathEsc");
                
                $readList = [];
                $writeList = [];
                
                $userPerms = $_POST['user_perms'] ?? [];
                foreach ($userPerms as $usr => $perm) {
                    if (!in_array($usr, $systemUsers)) continue;
                    $usrEsc = escapeshellarg($usr);
                    if ($perm === 'read') {
                        $readList[] = $usr;
                        Shell::execSudo("/usr/bin/setfacl -m u:$usrEsc:rx -d -m u:$usrEsc:rx $pathEsc");
                    } elseif ($perm === 'write') {
                        $writeList[] = $usr;
                        Shell::execSudo("/usr/bin/setfacl -m u:$usrEsc:rwx -d -m u:$usrEsc:rwx $pathEsc");
                    }
                }
                
                $groupPerms = $_POST['group_perms'] ?? [];
                foreach ($groupPerms as $grp => $perm) {
                    if (!in_array($grp, $systemGroups)) continue;
                    $grpEsc = escapeshellarg($grp);
                    if ($perm === 'read') {
                        $readList[] = '@' . $grp;
                        Shell::execSudo("/usr/bin/setfacl -m g:$grpEsc:rx -d -m g:$grpEsc:rx $pathEsc");
                    } elseif ($perm === 'write') {
                        $writeList[] = '@' . $grp;
                        Shell::execSudo("/usr/bin/setfacl -m g:$grpEsc:rwx -d -m g:$grpEsc:rwx $pathEsc");
                    }
                }
                
                $validUsers = array_merge($readList, $writeList);
                
                $block = "\n[$name]\n   path = $path\n   guest ok = no\n";
                $block .= "   force group = $ownerGroup\n";
                $block .= "   create mask = 0660\n";
                $block .= "   directory mask = 0770\n";

                
                if (!empty($validUsers)) {
                    $block .= "   read only = yes\n";
                    $block .= "   valid users = " . implode(', ', $validUsers) . "\n";
                    if (!empty($readList)) $block .= "   read list = " . implode(', ', $readList) . "\n";
                    if (!empty($writeList)) $block .= "   write list = " . implode(', ', $writeList) . "\n";
                } else {
                    $block .= "   read only = no\n";
                }
                
                $hideNetwork = $_POST['hide_network'] ?? '';
                if ($hideNetwork === '1') {
                    $block .= "   browseable = no\n";
                }
                
                $hideUnreadable = $_POST['hide_unreadable'] ?? '';
                if ($hideUnreadable === '1') {
                    $block .= "   hide unreadable = yes\n";
                }
                
                $vfsObjects = [];
                $enableRecycle = $_POST['enable_recycle'] ?? '';
                if ($enableRecycle === '1') {
                    $vfsObjects[] = 'recycle';
                    $block .= "   recycle:repository = #recycle\n";
                    $block .= "   recycle:keeptree = yes\n";
                    $block .= "   recycle:versions = yes\n";
                    $block .= "   recycle:exclude_dir = tmp, cache\n";
                    
                    $recycleAdmin = $_POST['recycle_admin'] ?? '';
                    if ($recycleAdmin === '1') {
                        // Restrict .recycle to owners/admins
                        $block .= "   recycle:directory_mode = 0700\n";
                    }
                }
                
                $enableAudit = $_POST['enable_audit'] ?? '';
                if ($enableAudit === 'yes') {
                    $vfsObjects[] = 'full_audit';
                    $block .= "   full_audit:prefix = %u|%I|%S\n";
                    $block .= "   full_audit:prompt = error\n";
                    $block .= "   full_audit:success = connect disconnect mkdirat unlinkat pread pwrite renameat\n";
                    $block .= "   full_audit:failure = connect\n";
                    $block .= "   full_audit:facility = local7\n";
                    $block .= "   full_audit:priority = notice\n";
                }
                
                if (!empty($vfsObjects)) {
                    $block .= "   vfs objects = " . implode(' ', $vfsObjects) . "\n";
                }
                
                // --- Limpeza: Remove bloco antigo se estiver editando ---
                $content = shell_exec("sudo -n /usr/bin/cat /etc/samba/shares.conf 2>/dev/null");
                if ($content) {
                    $parsed = SambaParser::parse($content);
                    if (isset($parsed[$name])) {
                        unset($parsed[$name]);
                        $newConf = SambaParser::generate($parsed);
                        $tmp = '/tmp/shares_conf.tmp';
                        file_put_contents($tmp, $newConf);
                        Shell::execSudo("sh -c 'cat $tmp > /etc/samba/shares.conf'");
                        unlink($tmp);
                    }
                }
                // --------------------------------------------------------
                
                $tempBlock = '/tmp/smb_block.tmp';
                file_put_contents($tempBlock, $block);
                
                // Garantir que shares.conf existe
                Shell::execSudo("sh -c 'touch /etc/samba/shares.conf'");
                // Adicionar o bloco ao shares.conf
                Shell::execSudo("sh -c 'cat $tempBlock >> /etc/samba/shares.conf'");
                unlink($tempBlock);
                
                // Garantir que smb.conf tem o include
                $smbConfContent = shell_exec("sudo -n /usr/bin/cat /etc/samba/smb.conf 2>/dev/null");
                if ($smbConfContent && strpos($smbConfContent, 'include = /etc/samba/shares.conf') === false) {
                    // Adicionar no final do arquivo garantindo que está no escopo global
                    Shell::execSudo("sh -c 'echo \"\n[global]\n   include = /etc/samba/shares.conf\" >> /etc/samba/smb.conf'");
                }
                
                Shell::execSudo("/usr/bin/systemctl reload smbd");
                
                $_SESSION['message'] = "Compartilhamento criado com sucesso com permissões avançadas.";
                header('Location: /samba/shares');
                exit;
            } else {
                $_SESSION['error'] = "Preencha o nome e o caminho.";
            }
        }
        // Obter compartilhamentos existentes
        $existingShares = [];
        $sharesConfContent = shell_exec("sudo -n /usr/bin/cat /etc/samba/shares.conf 2>/dev/null");
        if ($sharesConfContent) {
            $existingShares = SambaParser::parse($sharesConfContent);
        }
        
        require __DIR__ . '/../Views/shares.php';
    }

    public function users() {
        // Obter listas de usuários/grupos do sistema para validação e para a view
        $systemUsers = $this->getSystemUsers();
        $systemGroups = $this->getSystemGroups();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'create_user') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $groups = $_POST['groups'] ?? []; // array of groups
                
                // Validações básicas (não permitir caracteres especiais perigosos no nome)
                if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                    $_SESSION['error'] = "Nome de usuário inválido.";
                    header('Location: /samba/users');
                    exit;
                }
                
                if ($username) {
                    $userEsc = escapeshellarg($username);
                    
                    $createHome = isset($_POST['create_home']) && $_POST['create_home'] == '1';
                    $createUserGroup = isset($_POST['create_user_group']) && $_POST['create_user_group'] == '1';
                    
                    $mFlag = $createHome ? '-m' : '-M';
                    $nFlag = $createUserGroup ? '-U' : '-N';
                    
                    // Se o usuário não existir no Linux, criamos. Se já existir, isso falha silenciosamente (esperado).
                    Shell::execSudo("/usr/sbin/useradd $nFlag $mFlag -s /sbin/nologin $userEsc");
                    
                    // Validar se os grupos submetidos realmente existem
                    $validGroups = [];
                    foreach ($groups as $g) {
                        if (in_array($g, $systemGroups)) {
                            $validGroups[] = $g;
                        }
                    }
                    
                    if (!empty($validGroups)) {
                        $groupsStr = escapeshellarg(implode(',', $validGroups));
                        Shell::execSudo("/usr/sbin/usermod -G $groupsStr $userEsc"); // Use -G para substituir os grupos secundários pelo que foi marcado na UI
                    }
                    
                    if ($password) {
                        $tmpPass = '/tmp/smbpass_' . bin2hex(random_bytes(8));
                        file_put_contents($tmpPass, $password . "\n" . $password . "\n");
                        $result = Shell::execSudo("/usr/bin/smbpasswd -a -s $userEsc < $tmpPass");
                        unlink($tmpPass);
                        
                        if ($result['success']) {
                            $_SESSION['message'] = "Usuário $username atualizado no Samba com sucesso.";
                        } else {
                            $_SESSION['error'] = "Erro ao atualizar senha no Samba: " . $result['output'];
                        }
                    } else {
                        $_SESSION['message'] = "Grupos do usuário $username atualizados com sucesso.";
                    }
                    
                    header('Location: /samba/users');
                    exit;
                } else {
                    $_SESSION['error'] = "Preencha o nome de usuário.";
                }
                
            } elseif ($action === 'create_group') {
                $groupname = $_POST['groupname'] ?? '';
                $users = $_POST['users'] ?? []; // array of users
                
                if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $groupname)) {
                    $_SESSION['error'] = "Nome de grupo inválido.";
                    header('Location: /samba/users');
                    exit;
                }
                
                if ($groupname) {
                    $groupEsc = escapeshellarg($groupname);
                    $result = Shell::execSudo("/usr/sbin/groupadd $groupEsc");
                    
                    if ($result['success'] || strpos($result['output'], 'already exists') !== false) {
                        
                        $validUsers = [];
                        foreach ($users as $u) {
                            if (in_array($u, $systemUsers)) {
                                $validUsers[] = $u;
                            }
                        }
                        
                        if (!empty($validUsers)) {
                            $usersStr = escapeshellarg(implode(',', $validUsers));
                            Shell::execSudo("/usr/bin/gpasswd -M $usersStr $groupEsc");
                        }
                        $_SESSION['message'] = "Grupo $groupname criado/atualizado com sucesso.";
                    } else {
                        $_SESSION['error'] = "Erro ao criar grupo: " . $result['output'];
                    }
                } else {
                    $_SESSION['error'] = "Preencha o nome do grupo.";
                }
                
                header('Location: /samba/users');
                exit;
            } elseif ($action === 'delete_user') {
                $targetUser = $_POST['target_user'] ?? '';
                if (in_array($targetUser, $systemUsers)) {
                    $userEsc = escapeshellarg($targetUser);
                    Shell::execSudo("/usr/bin/smbpasswd -x $userEsc");
                    Shell::execSudo("/usr/sbin/userdel -r $userEsc");
                    $_SESSION['message'] = "Usuário $targetUser apagado com sucesso do Samba e do sistema.";
                } else {
                    $_SESSION['error'] = "Usuário inválido ou não encontrado.";
                }
                header('Location: /samba/users');
                exit;
            } elseif ($action === 'enable_user') {
                $targetUser = $_POST['target_user'] ?? '';
                if (in_array($targetUser, $systemUsers)) {
                    $userEsc = escapeshellarg($targetUser);
                    Shell::execSudo("/usr/bin/smbpasswd -e $userEsc");
                    $_SESSION['message'] = "Usuário $targetUser ativado no Samba.";
                }
                header('Location: /samba/users');
                exit;
            } elseif ($action === 'disable_user') {
                $targetUser = $_POST['target_user'] ?? '';
                if (in_array($targetUser, $systemUsers)) {
                    $userEsc = escapeshellarg($targetUser);
                    Shell::execSudo("/usr/bin/smbpasswd -d $userEsc");
                    $_SESSION['message'] = "Usuário $targetUser desativado no Samba.";
                }
                header('Location: /samba/users');
                exit;
            }
        }
        
        // Obter lista de usuários do Samba e seus status
        $sambaUsers = [];
        $pdbOutput = shell_exec("sudo -n /usr/bin/pdbedit -L -w 2>&1");
        
        if ($pdbOutput !== null) {
            if (strpos($pdbOutput, 'password is required') !== false || strpos($pdbOutput, 'sudo:') !== false) {
                // Not configured in sudoers
                $sambaUsers = null; // Will trigger a specific warning in view
            } else {
                $lines = explode("\n", trim($pdbOutput));
                foreach ($lines as $line) {
                    if (empty($line) || strpos($line, ':') === false) continue;
                    $parts = explode(':', $line);
                    if (count($parts) >= 5) {
                        $username = $parts[0];
                        $flags = $parts[4];
                        $isDisabled = strpos($flags, 'D') !== false;
                        
                        if (in_array($username, $systemUsers)) {
                            // Fetch groups for the user
                            $userGroups = trim(shell_exec("id -Gn " . escapeshellarg($username)));
                            $sambaUsers[] = [
                                'username' => $username,
                                'disabled' => $isDisabled,
                                'groups' => $userGroups
                            ];
                        }
                    }
                }
            }
        }
        
        require __DIR__ . '/../Views/users.php';
    }
}
