<?php

/**
 * Скрипт для создания базы данных laravel_react
 * Запуск: php create_database.php
 */

$host = '127.0.0.1';
$port = 3306;
$username = 'root';
$password = '';
$database = 'laravel_react';

try {
    // Подключаемся к MySQL без выбора базы данных
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Создаем базу данных
    $sql = "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);

    echo "✅ База данных '{$database}' успешно создана!\n";

    // Проверяем создание
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$database}'");
    $result = $stmt->fetch();

    if ($result) {
        echo "✅ База данных подтверждена: {$result['Database (' . $database . ')']}\n";
    }

} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "\n💡 Убедитесь, что:\n";
    echo "   - MySQL сервер запущен\n";
    echo "   - Пользователь 'root' существует и не требует пароля\n";
    echo "   - Порт MySQL: 3306\n";
    exit(1);
}

