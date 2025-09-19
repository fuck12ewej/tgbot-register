<?php
/**
 * Скрипт настройки и проверки системы
 * Запустите этот файл для проверки корректности настройки бота
 */

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Настройка Telegram бота</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #333; margin-bottom: 30px; }
        .check { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🤖 Настройка Telegram бота</h1>
            <p>Проверка системы и настройка компонентов</p>
        </div>";

$checks = [];
$errors = 0;
$warnings = 0;

// Проверка версии PHP
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.0.0', '>=')) {
    $checks[] = "<div class='check success'>✅ PHP версия: {$phpVersion} (поддерживается)</div>";
} else {
    $checks[] = "<div class='check error'>❌ PHP версия: {$phpVersion} (требуется 8.0+)</div>";
    $errors++;
}

// Проверка необходимых расширений PHP
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $checks[] = "<div class='check success'>✅ Расширение {$ext}: установлено</div>";
    } else {
        $checks[] = "<div class='check error'>❌ Расширение {$ext}: не установлено</div>";
        $errors++;
    }
}

// Проверка файлов конфигурации
$configFiles = ['config.php', 'db.php', 'functions.php', 'email.php', 'main.php'];
foreach ($configFiles as $file) {
    if (file_exists($file)) {
        $checks[] = "<div class='check success'>✅ Файл {$file}: найден</div>";
    } else {
        $checks[] = "<div class='check error'>❌ Файл {$file}: не найден</div>";
        $errors++;
    }
}

// Проверка прав на запись
$writableDirs = ['temp'];
foreach ($writableDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (is_writable($dir)) {
        $checks[] = "<div class='check success'>✅ Папка {$dir}: доступна для записи</div>";
    } else {
        $checks[] = "<div class='check error'>❌ Папка {$dir}: нет прав на запись</div>";
        $errors++;
    }
}

// Проверка конфигурации
try {
    if (file_exists('config.php')) {
        require_once 'config.php';
        
        // Проверка токена бота
        if (defined('BOT_TOKEN') && BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE') {
            $checks[] = "<div class='check success'>✅ Токен бота: настроен</div>";
        } else {
            $checks[] = "<div class='check warning'>⚠️ Токен бота: не настроен</div>";
            $warnings++;
        }
        
        // Проверка настроек БД
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            $checks[] = "<div class='check success'>✅ Настройки БД: настроены</div>";
            
            // Попытка подключения к БД
            try {
                require_once 'db.php';
                $checks[] = "<div class='check success'>✅ Подключение к БД: успешно</div>";
            } catch (Exception $e) {
                $checks[] = "<div class='check error'>❌ Подключение к БД: {$e->getMessage()}</div>";
                $errors++;
            }
        } else {
            $checks[] = "<div class='check warning'>⚠️ Настройки БД: не настроены</div>";
            $warnings++;
        }
        
        // Проверка настроек SMTP
        if (defined('SMTP_HOST') && defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your-email@gmail.com') {
            $checks[] = "<div class='check success'>✅ Настройки SMTP: настроены</div>";
        } else {
            $checks[] = "<div class='check warning'>⚠️ Настройки SMTP: не настроены</div>";
            $warnings++;
        }
        
    } else {
        $checks[] = "<div class='check error'>❌ Файл config.php не найден</div>";
        $errors++;
    }
} catch (Exception $e) {
    $checks[] = "<div class='check error'>❌ Ошибка загрузки конфигурации: {$e->getMessage()}</div>";
    $errors++;
}

// Проверка Telegram API
if (defined('BOT_TOKEN') && BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE') {
    $botInfo = @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getMe");
    if ($botInfo) {
        $botData = json_decode($botInfo, true);
        if ($botData && $botData['ok']) {
            $checks[] = "<div class='check success'>✅ Telegram API: бот найден (@{$botData['result']['username']})</div>";
        } else {
            $checks[] = "<div class='check error'>❌ Telegram API: неверный токен</div>";
            $errors++;
        }
    } else {
        $checks[] = "<div class='check warning'>⚠️ Telegram API: не удалось подключиться</div>";
        $warnings++;
    }
}

// Вывод результатов проверок
foreach ($checks as $check) {
    echo $check;
}

// Общий результат
echo "<hr>";
if ($errors == 0 && $warnings == 0) {
    echo "<div class='check success'><h3>🎉 Все проверки пройдены успешно!</h3><p>Бот готов к работе.</p></div>";
} elseif ($errors == 0) {
    echo "<div class='check warning'><h3>⚠️ Есть предупреждения</h3><p>Бот может работать, но рекомендуется исправить предупреждения.</p></div>";
} else {
    echo "<div class='check error'><h3>❌ Обнаружены ошибки</h3><p>Необходимо исправить ошибки перед запуском бота.</p></div>";
}

// Инструкции по настройке
echo "<h3>📋 Инструкции по настройке</h3>";

if ($errors > 0 || $warnings > 0) {
    echo "<div class='info'>
        <h4>Необходимые действия:</h4>
        <ol>
            <li>Отредактируйте файл <strong>config.php</strong> и укажите правильные настройки</li>
            <li>Создайте базу данных MySQL и выполните скрипт <strong>database.sql</strong></li>
            <li>Настройте токен бота от @BotFather</li>
            <li>Настройте SMTP для отправки email</li>
            <li>Установите webhook для бота</li>
        </ol>
    </div>";
}

echo "<h3>🔧 Команды для настройки webhook</h3>";
echo "<div class='code'>
# Установка webhook:
curl -X POST \"https://api.telegram.org/bot&lt;YOUR_BOT_TOKEN&gt;/setWebhook\" \\
     -H \"Content-Type: application/json\" \\
     -d '{\"url\": \"https://yourdomain.com/main.php\"}'

# Проверка webhook:
curl -X GET \"https://api.telegram.org/bot&lt;YOUR_BOT_TOKEN&gt;/getWebhookInfo\"

# Удаление webhook:
curl -X POST \"https://api.telegram.org/bot&lt;YOUR_BOT_TOKEN&gt;/deleteWebhook\"
</div>";

echo "<h3>📚 Дополнительная информация</h3>";
echo "<div class='info'>
    <p><strong>Документация:</strong> См. файл README.md для подробных инструкций</p>
    <p><strong>Поддержка:</strong> При возникновении проблем проверьте логи ошибок</p>
    <p><strong>Безопасность:</strong> Не забудьте отключить отображение ошибок в продакшене</p>
</div>";

echo "<div style='text-align: center; margin-top: 30px;'>
    <a href='README.md' class='button'>📖 Документация</a>
    <a href='database.sql' class='button'>🗄️ SQL скрипт</a>
</div>";

echo "</div></body></html>";
?>
