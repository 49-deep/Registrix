<?php
/**
 * config/db.php
 * PDO connection factory + auto-migration + admin seed.
 * Reads .env file and environment variables.
 */

function load_env(string $path): void {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env from project root
load_env(dirname(__DIR__) . '/.env');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host     = getenv('MYSQLHOST')     ?: (getenv('MYSQL_HOST')     ?: (getenv('DB_HOST') ?: '127.0.0.1'));
    $port     = getenv('MYSQLPORT')     ?: (getenv('MYSQL_PORT')     ?: (getenv('DB_PORT') ?: '3306'));
    $dbname   = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: (getenv('DB_NAME') ?: 'registrix'));
    $user     = getenv('MYSQLUSER')     ?: (getenv('MYSQL_USER')     ?: (getenv('DB_USER') ?: 'root'));
    $password = getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: (getenv('MYSQL_ROOT_PASSWORD') ?: (getenv('DB_PASS') ?: '')));

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
    } catch (PDOException $e) {
        // If the database doesn't exist yet (e.g., first Railway deploy), create it.
        if (str_contains($e->getMessage(), 'Unknown database') || $e->getCode() == 1049) {
            $pdo_no_db = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $user,
                $password,
                $options
            );
            $pdo_no_db->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, $user, $password, $options);
        } else {
            throw $e;
        }
    }

    run_migrations($pdo);

    return $pdo;
}

function run_migrations(PDO $pdo): void {
    // ── admins ──────────────────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id`            INT          NOT NULL AUTO_INCREMENT,
            `username`      VARCHAR(50)  NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_admin_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ── students ─────────────────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `students` (
            `id`             INT           NOT NULL AUTO_INCREMENT,
            `roll_no`        VARCHAR(30)   NOT NULL,
            `name`           VARCHAR(100)  NOT NULL,
            `dob`            DATE          NOT NULL,
            `class`          VARCHAR(50)   NOT NULL,
            `course`         VARCHAR(100)  NOT NULL,
            `phone`          VARCHAR(20)   DEFAULT NULL,
            `email`          VARCHAR(100)  DEFAULT NULL,
            `address`        VARCHAR(255)  DEFAULT NULL,
            `guardian_name`  VARCHAR(100)  DEFAULT NULL,
            `guardian_phone` VARCHAR(20)   DEFAULT NULL,
            `photo`          LONGTEXT      DEFAULT NULL,
            `photo_mime`     VARCHAR(50)   DEFAULT NULL,
            `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_roll_no` (`roll_no`),
            FULLTEXT KEY `ft_search` (`name`, `roll_no`, `class`, `course`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ── student_accounts ─────────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_accounts` (
            `id`            INT          NOT NULL AUTO_INCREMENT,
            `student_id`    INT          NOT NULL,
            `username`      VARCHAR(50)  NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_student_id`       (`student_id`),
            UNIQUE KEY `uq_student_username` (`username`),
            CONSTRAINT `fk_sa_student`
                FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ── seed admin account from environment ──────────────────────────────────
    $admin_user = getenv('ADMIN_USERNAME') ?: 'dc1671860@gmail.com';
    $admin_pass = getenv('ADMIN_PASSWORD') ?: 'Deepika@123';

    // Remove legacy default 'admin' account if present
    if ($admin_user !== 'admin') {
        $del = $pdo->prepare("DELETE FROM `admins` WHERE `username` = 'admin'");
        $del->execute();
    }

    $stmt = $pdo->prepare("SELECT `id`, `password_hash` FROM `admins` WHERE `username` = ? LIMIT 1");
    $stmt->execute([$admin_user]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`) VALUES (?, ?)");
        $ins->execute([$admin_user, $hash]);
    } else {
        if (!password_verify($admin_pass, $existing['password_hash'])) {
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $upd  = $pdo->prepare("UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?");
            $upd->execute([$hash, $existing['id']]);
        }
    }
}
