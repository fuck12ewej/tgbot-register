<?php
/**
 * Утилитарные функции и валидация
 */

require_once 'config.php';

/**
 * Валидация email адреса
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Генерация токена для подтверждения email
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(VERIFICATION_TOKEN_LENGTH));
}

/**
 * Генерация реферального кода
 */
function generateRefCode() {
    return bin2hex(random_bytes(REF_CODE_LENGTH));
}

/**
 * Отправка запроса к Telegram Bot API
 */
function sendTelegramRequest($method, $data = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        error_log("Failed to send Telegram request: " . $method);
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Отправка сообщения пользователю
 */
function sendMessage($chatId, $text, $replyMarkup = null) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
    return sendTelegramRequest('sendMessage', $data);
}

/**
 * Отправка ответа на callback query
 */
function answerCallbackQuery($callbackQueryId, $text = '', $showAlert = false) {
    return sendTelegramRequest('answerCallbackQuery', [
        'callback_query_id' => $callbackQueryId,
        'text' => $text,
        'show_alert' => $showAlert
    ]);
}

/**
 * Создание inline клавиатуры
 */
function createInlineKeyboard($buttons) {
    return json_encode([
        'inline_keyboard' => $buttons
    ]);
}

/**
 * Создание reply клавиатуры
 */
function createReplyKeyboard($keyboard, $resize = true, $oneTime = false) {
    return json_encode([
        'keyboard' => $keyboard,
        'resize_keyboard' => $resize,
        'one_time_keyboard' => $oneTime
    ]);
}

/**
 * Удаление reply клавиатуры
 */
function removeReplyKeyboard($text = '') {
    return json_encode([
        'remove_keyboard' => true
    ]);
}

/**
 * Получение главного меню
 */
function getMainMenu() {
    return createReplyKeyboard(KEYBOARD['reply']['main_menu']);
}

/**
 * Получение inline меню
 */
function getInlineMenu() {
    $buttons = [
        [KEYBOARD['inline']['verification'], KEYBOARD['inline']['profile']],
        [KEYBOARD['inline']['referrals'], KEYBOARD['inline']['documents']]
    ];
    return createInlineKeyboard($buttons);
}

/**
 * Логирование ошибок
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Безопасное получение данных из POST/GET
 */
function getRequestData($key, $default = null) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data[$key])) {
        return $data[$key];
    }
    
    return $default;
}

/**
 * Проверка, является ли сообщение командой
 */
function isCommand($text) {
    return substr($text, 0, 1) === '/';
}

/**
 * Извлечение команды и параметров из текста
 */
function parseCommand($text) {
    $parts = explode(' ', trim($text), 2);
    $command = substr($parts[0], 1); // убираем '/'
    $params = isset($parts[1]) ? $parts[1] : '';
    
    return [
        'command' => $command,
        'params' => $params
    ];
}

/**
 * Форматирование даты
 */
function formatDate($timestamp) {
    return date('Y-m-d H:i', strtotime($timestamp));
}

/**
 * Форматирование профиля пользователя
 */
function formatUserProfile($user) {
    $emailStatus = $user['verified'] ? '✅ подтверждено' : '❌ не подтверждено';
    $email = $user['email'] ?: 'не указан';
    $username = $user['username'] ?: 'без username';
    
    return sprintf(
        MESSAGES['profile_template'],
        $user['id'],
        $username,
        $email,
        $emailStatus,
        BOT_USERNAME,
        $user['ref_code']
    );
}

/**
 * Форматирование списка рефералов
 */
function formatReferralsList($referrals) {
    if (empty($referrals)) {
        return MESSAGES['referrals_empty'];
    }
    
    $list = '';
    foreach ($referrals as $referral) {
        $username = $referral['username'] ? '@' . $referral['username'] : 'ID: ' . $referral['telegram_id'];
        $verified = $referral['verified'] ? '✅' : '⏳';
        $date = formatDate($referral['date_invited']);
        
        $list .= "{$username} {$verified} – {$date}\n";
    }
    
    return sprintf(MESSAGES['referrals_list'], $list);
}

/**
 * Проверка прав доступа (только для подтверждённых пользователей)
 */
function requireVerification($user, $chatId) {
    if (!$user['verified']) {
        sendMessage($chatId, MESSAGES['email_required'], getMainMenu());
        return false;
    }
    return true;
}

/**
 * Создание ссылки для подтверждения email
 */
function createVerificationLink($token) {
    return SITE_URL . "/email_verify.php?token=" . $token;
}

/**
 * Получение IP адреса пользователя
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Безопасная очистка текста
 */
function sanitizeText($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/**
 * Проверка на спам (простая защита)
 */
function isSpam($text, $userId) {
    // Простая проверка на повторяющиеся символы
    $repeatedChars = preg_match('/(.)\1{4,}/', $text);
    if ($repeatedChars) {
        return true;
    }
    
    // Проверка на слишком длинное сообщение
    if (strlen($text) > 1000) {
        return true;
    }
    
    return false;
}

/**
 * Генерация уникального токена для сессии
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Валидация Telegram ID
 */
function validateTelegramId($id) {
    return is_numeric($id) && $id > 0;
}

/**
 * Проверка формата username
 */
function validateUsername($username) {
    if (empty($username)) {
        return true; // username может быть пустым
    }
    
    // Username должен начинаться с @ и содержать только буквы, цифры и _
    return preg_match('/^@[a-zA-Z0-9_]{5,32}$/', $username);
}
?>
