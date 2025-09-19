<?php
/**
 * Основной обработчик Telegram Bot API
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
 * Основной класс бота
 */
class TelegramBot {
    private $db;
    private $emailService;
    
    public function __construct() {
        global $db, $emailService;
        $this->db = $db;
        $this->emailService = $emailService;
    }
    
    /**
     * Обработка входящих обновлений
     */
    public function handleUpdate($update) {
        try {
            // Логируем входящее обновление
            logError("Incoming update", $update);
            
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
            
        } catch (Exception $e) {
            logError("Error handling update: " . $e->getMessage(), $update);
        }
    }
    
    /**
     * Обработка текстовых сообщений
     */
    private function handleMessage($message) {
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $username = $message['from']['username'] ?? '';
        $text = $message['text'] ?? '';
        
        // Проверяем на спам
        if (isSpam($text, $userId)) {
            sendMessage($chatId, "⚠️ Ваше сообщение отклонено как спам.");
            return;
        }
        
        // Получаем или создаём пользователя
        $user = $this->getOrCreateUser($userId, $username);
        if (!$user) {
            sendMessage($chatId, "❌ Ошибка обработки запроса. Попробуйте позже.");
            return;
        }
        
        // Обрабатываем команды
        if (isCommand($text)) {
            $this->handleCommand($chatId, $user, $text);
        } else {
            $this->handleTextMessage($chatId, $user, $text);
        }
    }
    
    /**
     * Обработка callback query (нажатия на inline кнопки)
     */
    private function handleCallbackQuery($callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];
        $userId = $callbackQuery['from']['id'];
        $username = $callbackQuery['from']['username'] ?? '';
        $data = $callbackQuery['data'];
        $callbackQueryId = $callbackQuery['id'];
        
        // Получаем пользователя
        $user = $this->getOrCreateUser($userId, $username);
        if (!$user) {
            answerCallbackQuery($callbackQueryId, "❌ Ошибка обработки запроса.");
            return;
        }
        
        // Обрабатываем callback data
        switch ($data) {
            case 'verification':
                $this->handleVerification($chatId, $user);
                break;
                
            case 'profile':
                $this->handleProfile($chatId, $user);
                break;
                
            case 'referrals':
                $this->handleReferrals($chatId, $user);
                break;
                
            case 'documents':
                $this->handleDocuments($chatId, $user);
                break;
                
            default:
                answerCallbackQuery($callbackQueryId, "❓ Неизвестная команда.");
        }
        
        // Подтверждаем callback query
        answerCallbackQuery($callbackQueryId);
    }
    
    /**
     * Получение или создание пользователя
     */
    private function getOrCreateUser($telegramId, $username) {
        $user = $this->db->getUser($telegramId);
        
        if (!$user) {
            // Создаём нового пользователя
            $user = $this->db->createUser($telegramId, $username);
        } else {
            // Обновляем username если изменился
            if ($user['username'] !== $username) {
                $stmt = $this->db->getPdo()->prepare("UPDATE users SET username = ? WHERE telegram_id = ?");
                $stmt->execute([$username, $telegramId]);
                $user['username'] = $username;
            }
        }
        
        return $user;
    }
    
    /**
     * Обработка команд
     */
    private function handleCommand($chatId, $user, $text) {
        $commandData = parseCommand($text);
        $command = $commandData['command'];
        $params = $commandData['params'];
        
        switch ($command) {
            case 'start':
                $this->handleStart($chatId, $user, $params);
                break;
                
            case 'profile':
                $this->handleProfile($chatId, $user);
                break;
                
            case 'help':
                $this->handleHelp($chatId);
                break;
                
            default:
                sendMessage($chatId, MESSAGES['unknown_command'], getMainMenu());
        }
    }
    
    /**
     * Обработка команды /start
     */
    private function handleStart($chatId, $user, $params) {
        // Если есть реферальный код
        if (!empty($params)) {
            $this->processReferralCode($chatId, $user, $params);
        }
        
        // Отправляем приветственное сообщение
        sendMessage($chatId, MESSAGES['welcome'], getMainMenu());
    }
    
    /**
     * Обработка реферального кода
     */
    private function processReferralCode($chatId, $user, $refCode) {
        // Проверяем, не является ли пользователь уже рефералом
        if ($user['ref_by']) {
            sendMessage($chatId, "ℹ️ Вы уже пришли по реферальной ссылке.");
            return;
        }
        
        // Ищем приглашающего по реферальному коду
        $referrer = $this->db->getUserByRefCode($refCode);
        if (!$referrer) {
            sendMessage($chatId, "❌ Неверная реферальная ссылка.");
            return;
        }
        
        // Проверяем, что пользователь не приглашает сам себя
        if ($referrer['telegram_id'] == $user['telegram_id']) {
            sendMessage($chatId, "❌ Вы не можете пригласить сами себя.");
            return;
        }
        
        // Обновляем реферальную связь
        $stmt = $this->db->getPdo()->prepare("UPDATE users SET ref_by = ? WHERE id = ?");
        $result = $stmt->execute([$refCode, $user['id']]);
        
        if ($result) {
            // Добавляем запись в таблицу рефералов
            $this->db->addReferral($referrer['id'], $user['id']);
            
            // Отправляем уведомление приглашающему
            $referrerUsername = $referrer['username'] ? '@' . $referrer['username'] : 'пользователь';
            sendMessage($chatId, "✅ Вы пришли по приглашению от {$referrerUsername}!");
            
            // Уведомляем приглашающего
            $invitedUsername = $user['username'] ? '@' . $user['username'] : 'новый пользователь';
            sendMessage($referrer['telegram_id'], "🎉 У вас новый реферал: {$invitedUsername}!");
            
            // Отправляем email уведомление приглашающему (если email подтверждён)
            if ($referrer['verified']) {
                $this->emailService->sendReferralNotification($referrer['telegram_id'], $user);
            }
        }
    }
    
    /**
     * Обработка текстовых сообщений
     */
    private function handleTextMessage($chatId, $user, $text) {
        // Проверяем, ожидает ли бот email от пользователя
        $waitingForEmail = $this->isWaitingForEmail($user['telegram_id']);
        
        if ($waitingForEmail) {
            $this->handleEmailInput($chatId, $user, $text);
        } else {
            sendMessage($chatId, "❓ Используйте команды или кнопки меню для навигации.", getMainMenu());
        }
    }
    
    /**
     * Обработка ввода email
     */
    private function handleEmailInput($chatId, $user, $text) {
        $email = trim($text);
        
        // Валидируем email
        if (!validateEmail($email)) {
            sendMessage($chatId, MESSAGES['email_invalid']);
            return;
        }
        
        // Проверяем, не используется ли email другим пользователем
        $existingUser = $this->db->getUserByEmail($email);
        if ($existingUser && $existingUser['telegram_id'] != $user['telegram_id']) {
            sendMessage($chatId, MESSAGES['email_exists']);
            return;
        }
        
        // Обновляем email пользователя
        $result = $this->db->updateUserEmail($user['telegram_id'], $email);
        
        if ($result) {
            // Отправляем письмо с подтверждением
            $emailResult = $this->emailService->sendVerificationEmail($email, $user['username'], $user['telegram_id']);
            
            if ($emailResult['success']) {
                sendMessage($chatId, MESSAGES['email_sent']);
                $this->removeWaitingForEmail($user['telegram_id']);
            } else {
                sendMessage($chatId, "❌ Ошибка отправки письма. Попробуйте позже.");
            }
        } else {
            sendMessage($chatId, "❌ Ошибка сохранения email. Попробуйте позже.");
        }
    }
    
    /**
     * Обработка кнопки "Верификация"
     */
    private function handleVerification($chatId, $user) {
        if ($user['verified']) {
            sendMessage($chatId, "✅ Ваш email уже подтверждён: " . $user['email']);
            return;
        }
        
        if ($user['email']) {
            sendMessage($chatId, "📧 Ваш email: " . $user['email'] . "\n\nПисьмо с подтверждением уже отправлено. Проверьте почтовый ящик.");
            return;
        }
        
        sendMessage($chatId, MESSAGES['email_request']);
        $this->setWaitingForEmail($user['telegram_id']);
    }
    
    /**
     * Обработка кнопки "Профиль"
     */
    private function handleProfile($chatId, $user) {
        $profileText = formatUserProfile($user);
        sendMessage($chatId, $profileText, getMainMenu());
    }
    
    /**
     * Обработка кнопки "Мои приглашённые"
     */
    private function handleReferrals($chatId, $user) {
        if (!requireVerification($user, $chatId)) {
            return;
        }
        
        $referrals = $this->db->getUserReferrals($user['id']);
        $referralsText = formatReferralsList($referrals);
        sendMessage($chatId, $referralsText, getMainMenu());
    }
    
    /**
     * Обработка кнопки "Документы"
     */
    private function handleDocuments($chatId, $user) {
        sendMessage($chatId, MESSAGES['documents'], getMainMenu());
    }
    
    /**
     * Обработка команды /help
     */
    private function handleHelp($chatId) {
        $helpText = "🤖 <b>Справка по боту</b>\n\n";
        $helpText .= "📋 <b>Доступные команды:</b>\n";
        $helpText .= "/start - Запуск бота\n";
        $helpText .= "/profile - Показать профиль\n";
        $helpText .= "/help - Показать эту справку\n\n";
        $helpText .= "🔘 <b>Кнопки меню:</b>\n";
        $helpText .= "🔑 Верификация - Подтвердить email\n";
        $helpText .= "👤 Профиль - Показать информацию о себе\n";
        $helpText .= "👥 Мои приглашённые - Список рефералов\n";
        $helpText .= "📜 Документы - Политика конфиденциальности\n\n";
        $helpText .= "💡 <b>Реферальная программа:</b>\n";
        $helpText .= "Приглашайте друзей по вашей реферальной ссылке и получайте бонусы!";
        
        sendMessage($chatId, $helpText, getMainMenu());
    }
    
    /**
     * Проверка, ожидает ли бот email от пользователя
     */
    private function isWaitingForEmail($telegramId) {
        // Простая реализация через файл или можно использовать Redis/Memcached
        $waitingFile = "temp/waiting_email_" . $telegramId . ".tmp";
        return file_exists($waitingFile);
    }
    
    /**
     * Установка флага ожидания email
     */
    private function setWaitingForEmail($telegramId) {
        $waitingDir = "temp";
        if (!is_dir($waitingDir)) {
            mkdir($waitingDir, 0755, true);
        }
        
        $waitingFile = $waitingDir . "/waiting_email_" . $telegramId . ".tmp";
        file_put_contents($waitingFile, time());
    }
    
    /**
     * Удаление флага ожидания email
     */
    private function removeWaitingForEmail($telegramId) {
        $waitingFile = "temp/waiting_email_" . $telegramId . ".tmp";
        if (file_exists($waitingFile)) {
            unlink($waitingFile);
        }
    }
}

/**
 * Получение и обработка входящих данных
 */
function processWebhook() {
    try {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) {
            error_log("Invalid JSON input: " . $input);
            return;
        }
        
        $bot = new TelegramBot();
        $bot->handleUpdate($update);
        
    } catch (Exception $e) {
        logError("Error processing webhook: " . $e->getMessage());
    }
}

// Запускаем обработку, если файл вызван напрямую
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    processWebhook();
}
?>
