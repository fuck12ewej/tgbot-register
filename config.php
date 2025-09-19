<?php
/**
 * Конфигурация Telegram-бота
 */

// Настройки Telegram Bot API
define('BOT_TOKEN', '8100941603:AAEsEaqkvTRsHPaosSaLkdOL5WpYA9mFhmY');
define('BOT_USERNAME', 'lkjdfskj_bot');

// Настройки базы данных MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'telegram_bot');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Настройки SMTP для отправки email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Telegram Bot');

// Настройки веб-сервера для подтверждения email
define('WEBHOOK_URL', 'https://49e5710d7b60.ngrok-free.app/bot/main.php');
define('SITE_URL', 'https://49e5710d7b60.ngrok-free.app/bot');

// Другие настройки
define('REF_CODE_LENGTH', 6);
define('VERIFICATION_TOKEN_LENGTH', 32);

// Сообщения бота
define('MESSAGES', [
    'welcome' => "👋 Добро пожаловать!\n\nИспользуя бота, вы автоматически соглашаетесь с политикой конфиденциальности и пользовательским соглашением.\n\nВыберите действие:",
    'email_request' => "🔑 Верификация\n\nОтправьте вашу почту в формате: pochta@domen.ru",
    'email_invalid' => "❌ Ошибка, неверная почта.",
    'email_exists' => "❌ Эта почта уже привязана к другому аккаунту.",
    'email_sent' => "✅ Письмо с подтверждением отправлено на вашу почту. Проверьте почтовый ящик и перейдите по ссылке для подтверждения.",
    'email_verified' => "✅ Ваша почта успешно привязана к аккаунту: @username",
    'email_required' => "⚠️ Для пользования проектом нужно указать почту.",
    'profile_template' => "👤 Ваш профиль:\n\nUserID: %d\nTelegram: @%s\nEmail: %s (%s)\nРеф. ссылка: t.me/%s?start=%s",
    'referrals_empty' => "👥 У вас пока нет приглашённых пользователей.",
    'referrals_list' => "👥 Мои приглашённые:\n\n%s",
    'documents' => "📜 Документы:\n\n• Политика конфиденциальности\n• Пользовательское соглашение",
    'unknown_command' => "❓ Неизвестная команда. Используйте меню для навигации."
]);

// Кнопки клавиатуры
define('KEYBOARD', [
    'inline' => [
        'verification' => ['text' => '🔑 Верификация', 'callback_data' => 'verification'],
        'profile' => ['text' => '👤 Профиль', 'callback_data' => 'profile'],
        'referrals' => ['text' => '👥 Мои приглашённые', 'callback_data' => 'referrals'],
        'documents' => ['text' => '📜 Документы', 'callback_data' => 'documents']
    ],
    'reply' => [
        'main_menu' => [
            ['🔑 Верификация', '👤 Профиль'],
            ['👥 Мои приглашённые', '📜 Документы']
        ]
    ]
]);
?>
