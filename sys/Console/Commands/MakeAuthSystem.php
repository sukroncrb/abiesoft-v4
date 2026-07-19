<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeAuthSystem extends BaseCommand
{
    public function handle(array $args): void
    {
        $targetDir = __DIR__ . '/../../../database/schemas/'; 
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');

        $userFileName = $timestamp . '_create_users_table.sql';
        $userSql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    nama VARCHAR(255),
    email VARCHAR(255),
    password_hash VARCHAR(255),
    photo VARCHAR(255),
    kode INT(4),
    role VARCHAR(255) DEFAULT 'staf',
    dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diedit TIMESTAMP,
    dihapus TIMESTAMP,
    INDEX (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";

        $logFileName = date('Y_m_d_His', strtotime('+1 second')) . '_create_log_table.sql';
        $logSql = "CREATE TABLE IF NOT EXISTS log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    email VARCHAR(255),
    device TEXT,
    lokasi VARCHAR(255),
    ip VARCHAR(255),
    aktifitas LONGTEXT,
    inisial VARCHAR(255),
    dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diedit TIMESTAMP,
    dihapus TIMESTAMP,
    INDEX (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";

        $userPathFull = $targetDir . $userFileName;
        $userPathDisplay = "database/schemas/" . $userFileName;

        if (file_exists($userPathFull)) {
            echo "\033[33m⚠ Lewati:\033[0m File sudah ada ($userPathDisplay)\n";
        } else {
            if (file_put_contents($userPathFull, $userSql) !== false) {
                echo "\033[32m✔ Berhasil:\033[0m $userPathDisplay\n";
            } else {
                echo "\033[31m✘ Gagal membuat file users.\033[0m\n";
            }
        }

        $logPathFull = $targetDir . $logFileName;
        $logPathDisplay = "database/schemas/" . $logFileName;

        if (file_exists($logPathFull)) {
            echo "\033[33m⚠ Lewati:\033[0m File sudah ada ($logPathDisplay)\n";
        } else {
            if (file_put_contents($logPathFull, $logSql) !== false) {
                echo "\033[32m✔ Berhasil:\033[0m $logPathDisplay\n";
            } else {
                echo "\033[31m✘ Gagal membuat file log.\033[0m\n";
            }
        }
    }
}