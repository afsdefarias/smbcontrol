<?php
namespace App\Controllers;

use App\Services\DiskManager;

class DiskController {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $devicePath = $_POST['device_path'] ?? '';
            $mountName = $_POST['mount_name'] ?? '';
            $fsType = $_POST['fstype'] ?? 'ext4';
            $mountPoint = $_POST['mountpoint'] ?? '';

            try {
                if ($action === 'format') {
                    $result = DiskManager::formatDisk($devicePath, $fsType, $mountName);
                    $_SESSION['message'] = $result['message'];
                } elseif ($action === 'import') {
                    $result = DiskManager::importDisk($devicePath, $mountName);
                    $_SESSION['message'] = $result['message'];
                } elseif ($action === 'eject') {
                    $result = DiskManager::ejectDisk($mountPoint);
                    $_SESSION['message'] = $result['message'];
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }

            header('Location: /disks');
            exit;
        }

        // Recupera a lista de discos para a View
        $disks = DiskManager::listDisks();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $totalDisks = count($disks);
        $pagination = [
            'page' => $page,
            'per_page' => 200,
            'total' => $totalDisks,
            'pages' => max(1, (int)ceil($totalDisks / 200)),
        ];
        $disks = array_slice($disks, ($page - 1) * 200, 200);
        $paginationPath = '/disks';
        $paginationBase = [];
        
        $contentView = __DIR__ . '/../Views/_disks_content.php';
        require __DIR__ . '/../Views/layout.php';
    }
}
