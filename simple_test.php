<?php
// Простой тест без зависимостей
echo "PHP работает!<br>";
echo "Время: " . date('Y-m-d H:i:s') . "<br>";
echo "Версия PHP: " . phpversion() . "<br>";

// Проверим основные расширения
$extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
echo "<h3>Расширения:</h3>";
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "{$ext}: {$status}<br>";
}

// Проверим доступность файлов
$files = ['config.php', 'db.php', 'functions.php', 'main.php'];
echo "<h3>Файлы:</h3>";
foreach ($files as $file) {
    $status = file_exists($file) ? '✅' : '❌';
    echo "{$file}: {$status}<br>";
}

echo "<h3>Тест завершён!</h3>";
?>
