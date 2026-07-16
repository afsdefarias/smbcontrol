<?php
namespace App\Controllers;

use App\Services\Shell;
use App\Models\Database;

class DashboardController {
    public function index() {
        $status = Shell::execSudo('/usr/bin/systemctl is-active smbd');
        $isActive = trim($status['output']) === 'active';
        
        require __DIR__ . '/../Views/dashboard.php';
    }
    
    public function action() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if (in_array($action, ['start', 'stop', 'restart'])) {
                $result = Shell::execSudo("/usr/bin/systemctl $action smbd");
                if ($result['success']) {
                    $_SESSION['message'] = "Serviço smbd executou a ação: $action com sucesso.";
                } else {
                    $_SESSION['error'] = "Erro na ação $action: " . $result['output'];
                }
            }
        }
        header('Location: /dashboard');
        exit;
    }

    public function reports() {
        $logs = [];
        try {
            $output = shell_exec("sudo -n /usr/bin/cat /var/log/syslog 2>/dev/null | grep smbd_audit | tail -n 100");
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    // syslog format: Jul 16 00:12:34 smbcontrol smbd_audit: andre|192.168.1.10|Arquivos|mkdir|ok|NewFolder
                    // or similar. We will just pass the raw line for now, or parse basic info.
                    
                    // Simple parse
                    $parts = explode('smbd_audit:', $line);
                    if (count($parts) >= 2) {
                        $datePart = trim($parts[0]); // e.g. Jul 16 00:12:34 smbcontrol
                        $auditPart = trim($parts[1]); // e.g. andre|192.168.1.10|Arquivos|mkdir|ok|NewFolder
                        
                        $auditFields = explode('|', $auditPart);
                        $logs[] = [
                            'data_hora' => $datePart,
                            'usuario' => $auditFields[0] ?? '?',
                            'ip' => $auditFields[1] ?? '?',
                            'arquivo' => $auditFields[2] ?? '?', // Share
                            'acao' => ($auditFields[3] ?? '') . ' (' . ($auditFields[4] ?? '') . ') ' . ($auditFields[5] ?? ''),
                        ];
                    }
                }
                $logs = array_reverse($logs); // Newest first
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = "Erro ao buscar logs: " . $e->getMessage();
        }
        require __DIR__ . '/../Views/reports.php';
    }
}
