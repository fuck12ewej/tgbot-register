<?php
/**
 * Работа с базой данных MySQL
 */

require_once 'config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
    }
    
    /**
     * Получить пользователя по Telegram ID
     */
    public function getUser($telegramId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE telegram_id = ?");
            $stmt->execute([$telegramId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить пользователя по email
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user by email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить пользователя по реферальному коду
     */
    public function getUserByRefCode($refCode) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE ref_code = ?");
            $stmt->execute([$refCode]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user by ref code: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создать нового пользователя
     */
    public function createUser($telegramId, $username, $refCode = null) {
        try {
            // Генерируем уникальный реферальный код для нового пользователя
            $userRefCode = $this->generateUniqueRefCode();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (telegram_id, username, ref_code, ref_by, date_reg) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$telegramId, $username, $userRefCode, $refCode]);
            
            if ($result) {
                return $this->getUser($telegramId);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновить email пользователя
     */
    public function updateUserEmail($telegramId, $email) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET email = ? WHERE telegram_id = ?");
            return $stmt->execute([$email, $telegramId]);
        } catch (PDOException $e) {
            error_log("Error updating user email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Подтвердить email пользователя
     */
    public function verifyUserEmail($telegramId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET verified = 1 WHERE telegram_id = ?");
            return $stmt->execute([$telegramId]);
        } catch (PDOException $e) {
            error_log("Error verifying user email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Добавить реферала
     */
    public function addReferral($referrerId, $invitedId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO referrals (referrer_id, invited_id, date_invited) 
                VALUES (?, ?, NOW())
            ");
            return $stmt->execute([$referrerId, $invitedId]);
        } catch (PDOException $e) {
            error_log("Error adding referral: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить список приглашённых пользователей
     */
    public function getUserReferrals($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.date_invited, u.telegram_id, u.username, u.verified
                FROM referrals r
                JOIN users u ON r.invited_id = u.id
                WHERE r.referrer_id = ?
                ORDER BY r.date_invited DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting user referrals: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Проверить, есть ли уже реферальная связь
     */
    public function isReferralExists($referrerId, $invitedId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM referrals 
                WHERE referrer_id = ? AND invited_id = ?
            ");
            $stmt->execute([$referrerId, $invitedId]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking referral existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Генерировать уникальный реферальный код
     */
    private function generateUniqueRefCode() {
        do {
            $refCode = bin2hex(random_bytes(REF_CODE_LENGTH));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE ref_code = ?");
            $stmt->execute([$refCode]);
            $result = $stmt->fetch();
        } while ($result['count'] > 0);
        
        return $refCode;
    }
    
    /**
     * Получить статистику пользователя
     */
    public function getUserStats($userId) {
        try {
            // Общее количество приглашённых
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_referrals
                FROM referrals 
                WHERE referrer_id = ?
            ");
            $stmt->execute([$userId]);
            $totalReferrals = $stmt->fetch()['total_referrals'];
            
            // Количество подтверждённых рефералов
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as verified_referrals
                FROM referrals r
                JOIN users u ON r.invited_id = u.id
                WHERE r.referrer_id = ? AND u.verified = 1
            ");
            $stmt->execute([$userId]);
            $verifiedReferrals = $stmt->fetch()['verified_referrals'];
            
            return [
                'total_referrals' => $totalReferrals,
                'verified_referrals' => $verifiedReferrals
            ];
        } catch (PDOException $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            return ['total_referrals' => 0, 'verified_referrals' => 0];
        }
    }
    
    /**
     * Получить PDO объект для прямых запросов
     */
    public function getPdo() {
        return $this->pdo;
    }
}

// Создаём глобальный экземпляр базы данных
$db = new Database();
?>
