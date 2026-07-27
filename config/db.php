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

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $use_sqlite = getenv('USE_SQLITE') === 'true';

    if (!$use_sqlite) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown database') || $e->getCode() == 1049) {
                try {
                    $pdo_no_db = new PDO(
                        "mysql:host={$host};port={$port};charset=utf8mb4",
                        $user,
                        $password,
                        $options
                    );
                    $pdo_no_db->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo = new PDO($dsn, $user, $password, $options);
                } catch (Throwable $db_err) {
                    $pdo = null;
                }
            } else {
                $pdo = null;
            }
        }
    }

    // Zero-config SQLite fallback if MySQL is unavailable
    if ($pdo === null) {
        $sqlite_dir = sys_get_temp_dir() . '/registrix_db';
        if (!is_dir($sqlite_dir)) {
            @mkdir($sqlite_dir, 0777, true);
        }
        $sqlite_path = $sqlite_dir . '/registrix.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_path, null, null, $options);
        $pdo->exec("PRAGMA foreign_keys = ON;");
    }

    run_migrations($pdo);

    return $pdo;
}

function run_migrations(PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                roll_no TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                dob TEXT NOT NULL,
                class TEXT NOT NULL,
                course TEXT NOT NULL,
                phone TEXT DEFAULT NULL,
                email TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                guardian_name TEXT DEFAULT NULL,
                guardian_phone TEXT DEFAULT NULL,
                photo TEXT DEFAULT NULL,
                photo_mime TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS student_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            );
        ");
    } else {
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
    }

    // Seed default admin accounts
    $default_admins = [
        'dc1671860@gmail.com' => getenv('ADMIN_PASSWORD') ?: 'Deepika@123',
        'admin'               => getenv('ADMIN_PASSWORD') ?: 'Deepika@123',
    ];

    foreach ($default_admins as $admin_user => $admin_pass) {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$admin_user]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $ins->execute([$admin_user, $hash]);
        } else {
            if (!password_verify($admin_pass, $existing['password_hash'])) {
                $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
                $upd  = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                $upd->execute([$hash, $existing['id']]);
            }
        }
    }
}
