<?php
/**
 * Простой тест для проверки работы бота
 */

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🤖 Тест Telegram бота</h1>";

// Проверяем PHP
echo "<h2>📋 Проверка PHP</h2>";
echo "PHP версия: " . phpversion() . "<br>";

// Проверяем расширения
echo "<h3>Расширения:</h3>";
$required = ['pdo', 'pdo_mysql', 'json', 'curl'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "{$ext}: {$status}<br>";
}

// Проверяем конфигурацию
echo "<h2>⚙️ Проверка конфигурации</h2>";
try {
    require_once 'config.php';
    echo "✅ Config загружен<br>";
    echo "Токен бота: " . substr(BOT_TOKEN, 0, 10) . "...<br>";
    echo "Username бота: " . BOT_USERNAME . "<br>";
} catch (Exception $e) {
    echo "❌ Ошибка конфигурации: " . $e->getMessage() . "<br>";
}

// Проверяем базу данных
echo "<h2>🗄️ Проверка базы данных</h2>";
try {
    require_once 'db.php';
    echo "✅ База данных подключена<br>";
} catch (Exception $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "<br>";
    echo "<p><strong>Решение:</strong> Убедитесь, что XAMPP запущен и MySQL работает.</p>";
}

// Проверяем функции
echo "<h2>🔧 Проверка функций</h2>";
try {
    require_once 'functions.php';
    echo "✅ Функции загружены<br>";
} catch (Exception $e) {
    echo "❌ Ошибка функций: " . $e->getMessage() . "<br>";
}

echo "<h2>🚀 Следующие шаги</h2>";
echo "<ol>";
echo "<li>Убедитесь, что XAMPP запущен (Apache и MySQL)</li>";
echo "<li>Создайте базу данных 'telegram_bot'</li>";
echo "<li>Импортируйте database.sql</li>";
echo "<li>Откройте <a href='setup.php'>setup.php</a> для полной проверки</li>";
echo "</ol>";

echo "<p><a href='setup.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Запустить полную проверку</a></p>";
?>
