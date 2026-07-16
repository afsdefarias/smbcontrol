<?php
namespace App\Controllers;

use App\Models\Database;

class AuthController {
    public function login() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    header('Location: /dashboard');
                    exit;
                } else {
                    $error = 'Usuário ou senha inválidos.';
                }
            } catch (\Exception $e) {
                $error = "Erro no banco de dados. " . $e->getMessage();
            }
        }
        
        require __DIR__ . '/../Views/login.php';
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
