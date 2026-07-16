<?php echo shell_exec('sudo -n /usr/bin/cat /var/log/syslog 2>/dev/null | grep smbd_audit | tail -n 10'); ?>
