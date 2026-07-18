<?php
// Headers de Segurança (Antihacker)
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Recomendado se usar HTTPS

require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Services/Shell.php';
require_once __DIR__ . '/../app/Services/SambaParser.php';
require_once __DIR__ . '/../app/Services/I18n.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/SambaController.php';
require_once __DIR__ . '/../app/Controllers/ProfileController.php';
require_once __DIR__ . '/../app/Controllers/DiskController.php';
require_once __DIR__ . '/../app/Services/DiskManager.php';

// Configurações restritas de Sessão
session_set_cookie_params([
    'lifetime' => 0, // Até fechar o navegador
    'path' => '/',
    'domain' => '', // Opcional: restringir ao seu domínio
    'secure' => isset($_SERVER['HTTPS']), // Apenas transmitir por HTTPS se disponível
    'httponly' => true, // JS não pode acessar o cookie de sessão (Mitiga XSS)
    'samesite' => 'Strict' // Bloqueia envio de cookies em requisições Cross-Site (Mitiga CSRF)
]);

session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'pt'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirectPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/dashboard';
    header('Location: ' . $redirectPath);
    exit;
}

// Geração do CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validação do CSRF Token em todas as requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenFromPost = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $tokenFromPost)) {
        http_response_code(403);
        die('Erro 403: Requisição bloqueada (CSRF Token inválido ou ausente). Volte e tente novamente.');
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Autoload simplificado manual (para o MVP)
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SambaController;
use App\Controllers\ProfileController;
use App\Controllers\DiskController;

// Roteamento
if ($uri === '/login') {
    (new AuthController())->login();
} elseif ($uri === '/logout') {
    (new AuthController())->logout();
} else {
    // Proteção de rotas autenticadas
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
    
    switch ($uri) {
        case '/':
        case '/dashboard':
            (new DashboardController())->index();
            break;
        case '/samba/service':
            (new DashboardController())->action();
            break;
        case '/samba/conf':
            (new SambaController())->conf();
            break;
        case '/samba/shares-config':
            (new SambaController())->sharesConf();
            break;
        case '/samba/shares':
            (new SambaController())->shares();
            break;
        case '/recycle':
            (new SambaController())->recycle();
            break;
        case '/samba/users':
            (new SambaController())->users();
            break;
        case '/reports':
            (new DashboardController())->reports();
            break;
        case '/disks':
            (new DiskController())->index();
            break;
        case '/profile':
            (new ProfileController())->index();
            break;
        default:
            http_response_code(404);
            echo "Página não encontrada.";
            break;
    }
}
