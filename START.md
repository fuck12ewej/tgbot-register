# 🚀 Запуск Telegram бота

## Быстрый старт

### 1. Запустите XAMPP
1. Откройте XAMPP Control Panel
2. Запустите **Apache** и **MySQL**
3. Убедитесь, что оба сервиса работают (зелёные индикаторы)

### 2. Скопируйте файлы
Скопируйте все файлы бота в папку:
```
C:\xampp\htdocs\bot\
```

### 3. Создайте базу данных
1. Откройте браузер: `http://localhost/phpmyadmin`
2. Создайте новую базу данных: `telegram_bot`
3. Импортируйте файл `database.sql`

### 4. Проверьте настройки
Откройте в браузере: `http://localhost/bot/test.php`

### 5. Запустите полную проверку
Откройте в браузере: `http://localhost/bot/setup.php`

### 6. Установите webhook (для тестирования)
```bash
curl -X POST "https://api.telegram.org/bot8100941603:AAEsEaqkvTRsHPaosSaLkdOL5WpYA9mFhmY/setWebhook" -d "url=http://localhost/bot/main.php"
```

## ⚠️ Важно для локального тестирования

Для работы с Telegram нужен HTTPS. Используйте один из способов:

### Способ 1: ngrok (Рекомендуется)
1. Скачайте [ngrok](https://ngrok.com/)
2. Запустите: `ngrok http 80`
3. Скопируйте HTTPS URL
4. Обновите в config.php:
   ```php
   define('WEBHOOK_URL', 'https://your-ngrok-url.ngrok.io/bot/main.php');
   define('SITE_URL', 'https://your-ngrok-url.ngrok.io/bot');
   ```

### Способ 2: Локальный HTTPS
Настройте SSL сертификат для localhost

## 🧪 Тестирование

1. Найдите бота в Telegram: `@lkjdfskj_bot`
2. Отправьте команду `/start`
3. Проверьте все функции:
   - 🔑 Верификация
   - 👤 Профиль
   - 👥 Рефералы
   - 📜 Документы

## 🆘 Если что-то не работает

1. Проверьте логи XAMPP: `C:\xampp\apache\logs\error.log`
2. Убедитесь, что все сервисы запущены
3. Проверьте права доступа к файлам
4. Убедитесь, что порт 80 свободен

## 📞 Поддержка

При возникновении проблем:
1. Проверьте `test.php` - покажет основные ошибки
2. Проверьте `setup.php` - полная диагностика
3. Проверьте логи ошибок
