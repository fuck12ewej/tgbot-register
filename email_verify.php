<?php
/**
 * Обработчик подтверждения email
 * Этот файл должен быть доступен по веб-адресу для обработки ссылок из писем
 */

// Включаем отображение ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем необходимые файлы
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';
require_once 'email.php';

/**
 * HTML шаблон для страницы подтверждения
 */
function getVerificationPageTemplate($title, $message, $isSuccess = true) {
    $color = $isSuccess ? '#28a745' : '#dc3545';
    $icon = $isSuccess ? '✅' : '❌';
    
    return "
    <!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$title}</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            .icon {
                font-size: 4rem;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 20px;
                font-size: 2rem;
                font-weight: 600;
            }
            .message {
                color: #666;
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                background: {$color};
                color: white;
                padding: 12px 30px;
                border-radius: 25px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                margin: 10px;
            }
            .button:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                text-decoration: none;
                color: white;
            }
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #999;
                font-size: 0.9rem;
            }
            .loading {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 10px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='icon'>{$icon}</div>
            <h1>{$title}</h1>
            <div class='message'>{$message}</div>
            <a href='https://t.me/" . BOT_USERNAME . "' class='button'>Перейти в бот</a>
            <div class='footer'>
                <p>Если у вас возникли проблемы, обратитесь в поддержку</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Основная функция обработки
 */
function processEmailVerification() {
    try {
        // Получаем токен из URL
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            echo getVerificationPageTemplate(
                'Ошибка подтверждения',
                'Токен подтверждения не найден. Проверьте правильность ссылки.',
                false
            );
            return;
        }
        
        // Валидируем токен
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            echo getVerificationPageTemplate(
                'Неверный токен',
                'Токен подтверждения имеет неверный формат.',
                false
            );
            return;
        }
        
        // Создаём экземпляр сервиса email
        $emailService = new EmailService();
        
        // Подтверждаем email
        $result = $emailService->verifyEmailByToken($token);
        
        if ($result['success']) {
            echo getVerificationPageTemplate(
                'Email подтверждён!',
                'Ваш email успешно подтверждён. Теперь вы можете пользоваться всеми функциями бота.',
                true
            );
            
            // Логируем успешное подтверждение
            logError("Email verified successfully", [
                'telegram_id' => $result['user']['telegram_id'] ?? 'unknown',
                'token' => substr($token, 0, 8) . '...'
            ]);
            
        } else {
            echo getVerificationPageTemplate(
                'Ошибка подтверждения',
                $result['message'] . ' Попробуйте запросить новое письмо подтверждения в боте.',
                false
            );
            
            // Логируем ошибку подтверждения
            logError("Email verification failed", [
                'token' => substr($token, 0, 8) . '...',
                'error' => $result['message']
            ]);
        }
        
    } catch (Exception $e) {
        logError("Error in email verification process: " . $e->getMessage());
        
        echo getVerificationPageTemplate(
            'Ошибка сервера',
            'Произошла внутренняя ошибка сервера. Попробуйте позже или обратитесь в поддержку.',
            false
        );
    }
}

/**
 * Функция для отображения страницы ожидания (если нужно)
 */
function showLoadingPage() {
    echo "
    <!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Подтверждение email...</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: #f5f5f5;
            }
            .loading { 
                display: inline-block; 
                width: 40px; 
                height: 40px; 
                border: 4px solid #f3f3f3; 
                border-top: 4px solid #3498db; 
                border-radius: 50%; 
                animation: spin 1s linear infinite; 
                margin: 20px;
            }
            @keyframes spin { 
                0% { transform: rotate(0deg); } 
                100% { transform: rotate(360deg); } 
            }
        </style>
    </head>
    <body>
        <div class='loading'></div>
        <p>Подтверждение email...</p>
        <script>
            // Автоматическое перенаправление через 3 секунды
            setTimeout(function() {
                window.location.href = 'https://t.me/" . BOT_USERNAME . "';
            }, 3000);
        </script>
    </body>
    </html>";
}

// Проверяем, что файл вызван напрямую
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    // Добавляем заголовки безопасности
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Обрабатываем подтверждение email
    processEmailVerification();
}
?>
