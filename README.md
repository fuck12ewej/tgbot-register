# Telegram Bot на PHP + MySQL

Полнофункциональный Telegram-бот с системой верификации email и реферальной программой.

## 🚀 Функционал

- ✅ Команда `/start` с обработкой реферальных ссылок
- 🔑 Верификация email с отправкой писем подтверждения
- 👤 Профиль пользователя с полной информацией
- 👥 Реферальная программа с отслеживанием приглашённых
- 📜 Документы и пользовательское соглашение
- 🛡️ Защита от спама и валидация данных

## 📁 Структура проекта

```
/bot
├── main.php              # Основной обработчик Telegram API
├── db.php                # Подключение к MySQL и функции CRUD
├── email.php             # Отправка email и подтверждение
├── functions.php         # Валидация, утилиты, форматирование
├── config.php            # Настройки бота, БД, SMTP
├── email_verify.php      # Обработчик подтверждения email
├── database.sql          # SQL скрипт для создания таблиц
└── README.md             # Документация
```

## ⚙️ Установка

### 1. Требования

- PHP 8.0 или выше
- MySQL 5.7 или выше
- Веб-сервер (Apache/Nginx)
- SSL сертификат (для webhook)

### 2. Настройка базы данных

1. Создайте базу данных MySQL:
```sql
CREATE DATABASE telegram_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Выполните SQL скрипт:
```bash
mysql -u username -p telegram_bot < database.sql
```

### 3. Настройка конфигурации

Отредактируйте файл `config.php`:

```php
// Настройки Telegram Bot API
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'yourbot');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'telegram_bot');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// Настройки SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Telegram Bot');

// Настройки веб-сервера
define('WEBHOOK_URL', 'https://yourdomain.com/main.php');
define('SITE_URL', 'https://yourdomain.com');
```

### 4. Создание Telegram бота

1. Найдите [@BotFather](https://t.me/botfather) в Telegram
2. Отправьте команду `/newbot`
3. Следуйте инструкциям для создания бота
4. Скопируйте полученный токен в `config.php`

### 5. Настройка webhook

Отправьте POST запрос на Telegram API:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://yourdomain.com/main.php"}'
```

### 6. Настройка SMTP (Gmail)

1. Включите двухфакторную аутентификацию в Google
2. Создайте пароль приложения:
   - Перейдите в настройки Google аккаунта
   - Безопасность → Пароли приложений
   - Создайте новый пароль для приложения
3. Используйте этот пароль в `SMTP_PASSWORD`

## 🗄️ Структура базы данных

### Таблица `users`
- `id` - Уникальный ID пользователя
- `telegram_id` - ID пользователя в Telegram
- `username` - Username в Telegram
- `email` - Email адрес
- `verified` - Статус подтверждения email
- `ref_code` - Уникальный реферальный код
- `ref_by` - Код пригласившего пользователя
- `date_reg` - Дата регистрации

### Таблица `referrals`
- `id` - Уникальный ID записи
- `referrer_id` - ID приглашающего
- `invited_id` - ID приглашённого
- `date_invited` - Дата приглашения
- `verified_at` - Дата подтверждения email приглашённым

### Таблица `email_verification_tokens`
- `id` - Уникальный ID токена
- `telegram_id` - ID пользователя в Telegram
- `token` - Токен подтверждения
- `expires_at` - Время истечения токена
- `used` - Использован ли токен

## 📋 Команды бота

### Основные команды
- `/start [refCode]` - Запуск бота (с реферальным кодом)
- `/profile` - Показать профиль пользователя
- `/help` - Справка по командам

### Inline кнопки
- 🔑 **Верификация** - Подтвердить email адрес
- 👤 **Профиль** - Показать информацию о пользователе
- 👥 **Мои приглашённые** - Список рефералов
- 📜 **Документы** - Политика конфиденциальности

## 🔧 API функции

### Database класс (`db.php`)
```php
$db = new Database();

// Получить пользователя
$user = $db->getUser($telegramId);

// Создать пользователя
$user = $db->createUser($telegramId, $username, $refCode);

// Обновить email
$db->updateUserEmail($telegramId, $email);

// Подтвердить email
$db->verifyUserEmail($telegramId);

// Добавить реферала
$db->addReferral($referrerId, $invitedId);

// Получить список рефералов
$referrals = $db->getUserReferrals($userId);
```

### EmailService класс (`email.php`)
```php
$emailService = new EmailService();

// Отправить письмо подтверждения
$result = $emailService->sendVerificationEmail($email, $username, $telegramId);

// Подтвердить email по токену
$result = $emailService->verifyEmailByToken($token);
```

## 🔒 Безопасность

- Все SQL запросы используют prepared statements
- Валидация всех входящих данных
- Защита от SQL инъекций
- Защита от XSS атак
- Проверка на спам
- Ограничение времени жизни токенов

## 🚀 Развёртывание

### Локальная разработка
1. Установите XAMPP/WAMP/MAMP
2. Скопируйте файлы в папку проекта
3. Настройте базу данных
4. Используйте ngrok для тестирования webhook

### Продакшн сервер
1. Загрузите файлы на сервер
2. Настройте SSL сертификат
3. Настройте webhook
4. Отключите отображение ошибок PHP
5. Настройте автоматическую очистку старых данных

## 📊 Мониторинг

### Логирование
Все ошибки записываются в error_log с помощью функции `logError()`.

### Статистика
Используйте таблицу `activity_logs` для отслеживания активности пользователей.

## 🔧 Настройка и кастомизация

### Изменение сообщений
Отредактируйте константы в `config.php` в массиве `MESSAGES`.

### Изменение клавиатуры
Измените массивы `KEYBOARD` в `config.php`.

### Добавление новых функций
1. Добавьте новые методы в класс `TelegramBot`
2. Обработайте новые команды в `handleCommand()`
3. Обновите базу данных при необходимости

## 🐛 Отладка

### Включение отладки
В файле `main.php` раскомментируйте:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Проверка webhook
```bash
curl -X GET "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

### Проверка подключения к БД
```php
try {
    $db = new Database();
    echo "Database connection successful";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
```

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи ошибок
2. Убедитесь в правильности настроек
3. Проверьте статус webhook
4. Проверьте подключение к базе данных
5. Проверьте настройки SMTP

## 📄 Лицензия

Этот проект распространяется под лицензией MIT.

## 🤝 Вклад в проект

Мы приветствуем вклад в развитие проекта! Пожалуйста:

1. Создайте fork проекта
2. Создайте ветку для новой функции
3. Внесите изменения
4. Создайте Pull Request

---

**Удачного использования бота! 🚀**
