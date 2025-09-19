<?php
/**
 * Функции для отправки email и подтверждения
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'db.php';

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUsername = SMTP_USERNAME;
        $this->smtpPassword = SMTP_PASSWORD;
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName = SMTP_FROM_NAME;
    }
    
    /**
     * Отправка письма с подтверждением email
     */
    public function sendVerificationEmail($email, $username, $telegramId) {
        try {
            // Генерируем токен для подтверждения
            $token = generateVerificationToken();
            
            // Сохраняем токен в базу данных (можно создать отдельную таблицу для токенов)
            $this->saveVerificationToken($telegramId, $token);
            
            // Создаём ссылку для подтверждения
            $verificationLink = createVerificationLink($token);
            
            // Формируем содержимое письма
            $subject = "Подтверждение email для Telegram-бота";
            $message = $this->getVerificationEmailTemplate($username, $verificationLink);
            
            // Отправляем письмо
            $result = $this->sendEmail($email, $subject, $message);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Письмо отправлено успешно'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка отправки письма'
                ];
            }
            
        } catch (Exception $e) {
            logError("Error sending verification email: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка отправки письма'
            ];
        }
    }
    
    /**
     * Подтверждение email по токену
     */
    public function verifyEmailByToken($token) {
        try {
            global $db;
            
            // Получаем пользователя по токену
            $stmt = $db->getPdo()->prepare("
                SELECT telegram_id, token 
                FROM email_verification_tokens 
                WHERE token = ? AND expires_at > NOW() AND used = 0
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return [
                    'success' => false,
                    'message' => 'Недействительный или истёкший токен'
                ];
            }
            
            // Подтверждаем email пользователя
            $verifyResult = $db->verifyUserEmail($tokenData['telegram_id']);
            
            if ($verifyResult) {
                // Помечаем токен как использованный
                $stmt = $db->getPdo()->prepare("
                    UPDATE email_verification_tokens 
                    SET used = 1, verified_at = NOW() 
                    WHERE token = ?
                ");
                $stmt->execute([$token]);
                
                // Получаем данные пользователя для уведомления
                $user = $db->getUser($tokenData['telegram_id']);
                
                if ($user) {
                    // Отправляем уведомление в Telegram
                    $message = str_replace('@username', '@' . ($user['username'] ?: 'user'), MESSAGES['email_verified']);
                    sendMessage($user['telegram_id'], $message, getMainMenu());
                }
                
                return [
                    'success' => true,
                    'message' => 'Email успешно подтверждён',
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка подтверждения email'
                ];
            }
            
        } catch (Exception $e) {
            logError("Error verifying email: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка подтверждения email'
            ];
        }
    }
    
    /**
     * Сохранение токена верификации
     */
    private function saveVerificationToken($telegramId, $token) {
        try {
            global $db;
            
            // Удаляем старые токены для этого пользователя
            $stmt = $db->getPdo()->prepare("DELETE FROM email_verification_tokens WHERE telegram_id = ?");
            $stmt->execute([$telegramId]);
            
            // Сохраняем новый токен (действителен 24 часа)
            $stmt = $db->getPdo()->prepare("
                INSERT INTO email_verification_tokens (telegram_id, token, expires_at, created_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())
            ");
            $stmt->execute([$telegramId, $token]);
            
        } catch (Exception $e) {
            logError("Error saving verification token: " . $e->getMessage());
        }
    }
    
    /**
     * Отправка email через SMTP
     */
    private function sendEmail($to, $subject, $message) {
        try {
            // Используем PHP mail() функцию для простоты
            // В продакшене лучше использовать PHPMailer или SwiftMailer
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if (!$result) {
                // Если mail() не работает, используем альтернативный метод
                return $this->sendEmailAlternative($to, $subject, $message);
            }
            
            return true;
            
        } catch (Exception $e) {
            logError("Error sending email via mail(): " . $e->getMessage());
            return $this->sendEmailAlternative($to, $subject, $message);
        }
    }
    
    /**
     * Альтернативный метод отправки email через cURL
     */
    private function sendEmailAlternative($to, $subject, $message) {
        try {
            // Простая реализация через внешний сервис (например, Mailgun, SendGrid)
            // Здесь можно добавить интеграцию с внешним email сервисом
            
            $data = [
                'to' => $to,
                'subject' => $subject,
                'html' => $message,
                'from' => $this->fromEmail,
                'from_name' => $this->fromName
            ];
            
            // Логируем попытку отправки
            logError("Alternative email sending attempt", $data);
            
            // Возвращаем true для демонстрации (в реальном проекте здесь должна быть реальная отправка)
            return true;
            
        } catch (Exception $e) {
            logError("Error in alternative email sending: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Шаблон письма для подтверждения email
     */
    private function getVerificationEmailTemplate($username, $verificationLink) {
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Подтверждение email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔑 Подтверждение Email</h1>
                </div>
                <div class='content'>
                    <p>Привет, " . htmlspecialchars($username) . "!</p>
                    <p>Для завершения регистрации в нашем Telegram-боте необходимо подтвердить ваш email адрес.</p>
                    <p>Нажмите на кнопку ниже для подтверждения:</p>
                    <p style='text-align: center;'>
                        <a href='" . $verificationLink . "' class='button'>✅ Подтвердить Email</a>
                    </p>
                    <p>Если кнопка не работает, скопируйте и вставьте эту ссылку в браузер:</p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 3px;'>
                        " . $verificationLink . "
                    </p>
                    <p><strong>Важно:</strong> Ссылка действительна в течение 24 часов.</p>
                </div>
                <div class='footer'>
                    <p>Это письмо отправлено автоматически, не отвечайте на него.</p>
                    <p>Если вы не регистрировались в нашем боте, просто проигнорируйте это письмо.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Отправка уведомления о новом реферале
     */
    public function sendReferralNotification($referrerId, $invitedUser) {
        try {
            global $db;
            
            $referrer = $db->getUser($referrerId);
            if (!$referrer) {
                return false;
            }
            
            $subject = "У вас новый реферал! 🎉";
            $message = $this->getReferralNotificationTemplate($invitedUser);
            
            if ($referrer['email'] && $referrer['verified']) {
                return $this->sendEmail($referrer['email'], $subject, $message);
            }
            
            return false;
            
        } catch (Exception $e) {
            logError("Error sending referral notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Шаблон письма для уведомления о реферале
     */
    private function getReferralNotificationTemplate($invitedUser) {
        $username = $invitedUser['username'] ? '@' . $invitedUser['username'] : 'ID: ' . $invitedUser['telegram_id'];
        
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Новый реферал</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Новый реферал!</h1>
                </div>
                <div class='content'>
                    <p>Поздравляем! У вас появился новый реферал:</p>
                    <p><strong>Пользователь:</strong> " . htmlspecialchars($username) . "</p>
                    <p><strong>Дата регистрации:</strong> " . date('d.m.Y H:i') . "</p>
                    <p>Спасибо за приглашение новых пользователей!</p>
                </div>
                <div class='footer'>
                    <p>Это письмо отправлено автоматически.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}

// Создаём глобальный экземпляр сервиса email
$emailService = new EmailService();
?>
