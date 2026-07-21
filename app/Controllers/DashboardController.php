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
                    $_SESSION['message'] = smb_t("smbd service completed action $action successfully.", "Serviço smbd executou a ação $action com sucesso.");
                } else {
                    $_SESSION['error'] = smb_t("Error while running action $action: ", "Erro ao executar a ação $action: ") . $result['output'];
                }
            }
        }
        header('Location: /dashboard');
        exit;
    }

    private function parseAuditLine(string $line): ?array {
        if (!str_contains($line, 'smbd_audit:')) {
            return null;
        }

        [$prefix, $auditPart] = explode('smbd_audit:', $line, 2);
        $prefixParts = preg_split('/\s+/', trim($prefix), 2);
        $timestamp = $prefixParts[0] ?? '';
        $host = $prefixParts[1] ?? '';
        $fields = explode('|', trim($auditPart));

        if (count($fields) < 5) {
            return null;
        }

        $details = array_slice($fields, 5);
        $lastDetail = end($details) ?: '';
        $fileName = $lastDetail !== '' ? basename($lastDetail) : ($fields[2] ?? '');

        return [
            'timestamp' => $timestamp,
            'date' => substr($timestamp, 0, 10),
            'host' => $host,
            'user' => $fields[0] ?? '?',
            'ip' => $fields[1] ?? '?',
            'share' => $fields[2] ?? '?',
            'operation' => $fields[3] ?? '?',
            'status' => $fields[4] ?? '?',
            'details' => implode(' -> ', $details),
            'name' => $fileName,
            'raw' => $line,
        ];
    }

    private function auditLogMatches(array $log, array $filters): bool {
        foreach (['user', 'ip', 'share', 'operation', 'date_from', 'date_to', 'q'] as $key) {
            $filters[$key] = trim($filters[$key] ?? '');
        }

        if ($filters['user'] !== '' && stripos($log['user'], $filters['user']) === false) return false;
        if ($filters['ip'] !== '' && stripos($log['ip'], $filters['ip']) === false) return false;
        if ($filters['share'] !== '' && stripos($log['share'], $filters['share']) === false) return false;
        if ($filters['operation'] !== '' && stripos($log['operation'], $filters['operation']) === false) return false;
        if ($filters['date_from'] !== '' && $log['date'] < $filters['date_from']) return false;
        if ($filters['date_to'] !== '' && $log['date'] > $filters['date_to']) return false;

        if ($filters['q'] !== '') {
            $haystack = implode(' ', [
                $log['name'],
                $log['user'],
                $log['ip'],
                $log['share'],
                $log['operation'],
                $log['status'],
                $log['details'],
                $log['raw'],
            ]);
            if (stripos($haystack, $filters['q']) === false) return false;
        }

        return true;
    }

    public function reports() {
        $logs = [];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'q' => $_GET['q'] ?? '',
            'user' => $_GET['user'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'share' => $_GET['share'] ?? '',
            'operation' => $_GET['operation'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        try {
            $result = Shell::execSudo('/usr/bin/cat /var/log/syslog');
            if ($result['success'] && trim($result['output']) !== '') {
                $lines = explode("\n", trim($result['output']));
                foreach ($lines as $line) {
                    $parsed = $this->parseAuditLine($line);
                    if ($parsed && $this->auditLogMatches($parsed, $filters)) {
                        $logs[] = $parsed;
                    }
                }
                $logs = array_reverse($logs);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = smb_t('Error while reading logs: ', 'Erro ao buscar logs: ') . $e->getMessage();
        }

        $totalLogs = count($logs);
        $logs = array_slice($logs, ($page - 1) * 200, 200);
        $pagination = [
            'page' => $page,
            'per_page' => 200,
            'total' => $totalLogs,
            'pages' => max(1, (int)ceil($totalLogs / 200)),
        ];

        require __DIR__ . '/../Views/reports.php';
    }
}
