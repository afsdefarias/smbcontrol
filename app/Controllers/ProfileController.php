<?php
namespace App\Controllers;

use App\Models\Database;

class ProfileController {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $userId = $_SESSION['user_id'];
                
                if ($username && $password) {
                    try {
                        $db = Database::getConnection();
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        
                        $stmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                        $stmt->execute([$username, $hash, $userId]);
                        
                        // Força logout após alterar dados sensíveis
                        session_destroy();
                        header('Location: /login');
                        exit;
                    } catch (\PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $_SESSION['error'] = smb_t('This username already exists.', 'Esse nome de usuário já existe.');
                        } else {
                            $_SESSION['error'] = smb_t('Error while updating profile: ', 'Erro ao atualizar perfil: ') . $e->getMessage();
                        }
                    }
                } else {
                    $_SESSION['error'] = smb_t('Enter the username and new password.', 'Preencha o nome de usuário e a nova senha.');
                }
                
                header('Location: /profile');
                exit;
                
            } elseif ($action === 'create_admin') {
                $newUsername = $_POST['new_username'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                
                if ($newUsername && $newPassword) {
                    try {
                        $db = Database::getConnection();
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                        
                        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                        $stmt->execute([$newUsername, $hash]);
                        
                        $_SESSION['message'] = smb_t("New access ($newUsername) created successfully.", "Novo acesso ($newUsername) criado com sucesso.");
                    } catch (\PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $_SESSION['error'] = smb_t('This username already exists.', 'Esse nome de usuário já existe.');
                        } else {
                            $_SESSION['error'] = smb_t('Error while creating access: ', 'Erro ao criar acesso: ') . $e->getMessage();
                        }
                    }
                } else {
                    $_SESSION['error'] = smb_t('Enter the username and password.', 'Preencha o nome de usuário e a senha.');
                }
                
                header('Location: /profile');
                exit;
            } elseif ($action === 'delete_admin') {
                $targetId = $_POST['admin_id'] ?? '';
                
                if ($targetId && $targetId != $_SESSION['user_id']) {
                    try {
                        $db = Database::getConnection();
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$targetId]);
                        
                        $_SESSION['message'] = smb_t('Administrator removed successfully.', 'Administrador removido com sucesso.');
                    } catch (\Exception $e) {
                        $_SESSION['error'] = smb_t('Error while removing administrator: ', 'Erro ao remover administrador: ') . $e->getMessage();
                    }
                } else {
                    $_SESSION['error'] = smb_t('Invalid action or attempt to remove the active user.', 'Ação inválida ou tentativa de remover o próprio usuário ativo.');
                }
                
                header('Location: /profile');
                exit;
            }
        }
        
        // Puxar dados atuais para popular o form do próprio usuário
        $currentUsername = $_SESSION['username'];
        
        // Puxar lista de todos os administradores para a tabela
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC");
            $allAdmins = $stmt->fetchAll();
        } catch (\Exception $e) {
            $allAdmins = [];
        }
        
        require __DIR__ . '/../Views/profile.php';
    }
}
