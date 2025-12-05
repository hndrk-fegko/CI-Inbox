<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Encryption\EncryptionInterface;
use Carbon\Carbon;

/**
 * Two-Factor Authentication Service
 * 
 * Implements TOTP (Time-based One-Time Password) authentication
 * compatible with Google Authenticator, Authy, 1Password, etc.
 * 
 * RFC 6238: TOTP Algorithm
 * RFC 4226: HOTP Algorithm (base for TOTP)
 */
class TwoFactorAuthService
{
    private LoggerService $logger;
    private EncryptionInterface $encryption;
    
    /**
     * TOTP Configuration
     */
    private const TOTP_PERIOD = 30; // 30 second validity window
    private const TOTP_DIGITS = 6; // 6-digit codes
    private const TOTP_ALGORITHM = 'sha1'; // SHA-1 for compatibility
    private const SECRET_LENGTH = 32; // 160-bit secret (32 base32 chars)
    private const BACKUP_CODE_COUNT = 10; // Number of backup codes
    private const BACKUP_CODE_LENGTH = 8; // 8-character codes
    private const DRIFT_WINDOW = 1; // Allow +/- 1 period for clock drift
    
    /**
     * Base32 alphabet for secret encoding
     */
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    public function __construct(
        LoggerService $logger,
        EncryptionInterface $encryption
    ) {
        $this->logger = $logger;
        $this->encryption = $encryption;
    }
    
    /**
     * Generate a new TOTP secret for a user
     * 
     * @return array ['secret' => string, 'qr_uri' => string, 'backup_codes' => array]
     */
    public function generateSecret(User $user): array
    {
        // Generate random secret
        $secret = $this->generateRandomSecret();
        
        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
        
        // Build QR code URI (otpauth:// format)
        $qrUri = $this->buildOtpAuthUri($user->email, $secret);
        
        $this->logger->info('Generated 2FA secret for user', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
            'backup_codes' => $backupCodes,
            'backup_codes_hashed' => array_map(fn($code) => hash('sha256', $code), $backupCodes)
        ];
    }
    
    /**
     * Enable 2FA for a user after verification
     */
    public function enable(User $user, string $secret, array $backupCodesHashed, string $code): bool
    {
        // Verify the code first
        if (!$this->verifyCode($secret, $code)) {
            $this->logger->warning('Failed 2FA enable attempt - invalid code', [
                'user_id' => $user->id
            ]);
            return false;
        }
        
        // Store encrypted secret and backup codes
        $user->totp_secret = $this->encryption->encrypt($secret);
        $user->totp_enabled = true;
        $user->totp_verified_at = Carbon::now();
        $user->backup_codes = json_encode($backupCodesHashed);
        $user->save();
        
        $this->logger->info('2FA enabled for user', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return true;
    }
    
    /**
     * Disable 2FA for a user
     */
    public function disable(User $user, string $code): bool
    {
        // Verify current code or backup code
        if (!$this->verify($user, $code)) {
            $this->logger->warning('Failed 2FA disable attempt - invalid code', [
                'user_id' => $user->id
            ]);
            return false;
        }
        
        $user->totp_secret = null;
        $user->totp_enabled = false;
        $user->totp_verified_at = null;
        $user->backup_codes = null;
        $user->save();
        
        $this->logger->info('2FA disabled for user', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return true;
    }
    
    /**
     * Verify a TOTP code for a user
     */
    public function verify(User $user, string $code): bool
    {
        if (!$user->totp_enabled || empty($user->totp_secret)) {
            return true; // 2FA not enabled, always passes
        }
        
        // Decrypt secret
        $secret = $this->encryption->decrypt($user->totp_secret);
        
        // Try TOTP code first
        if ($this->verifyCode($secret, $code)) {
            $user->last_2fa_at = Carbon::now();
            $user->save();
            
            $this->logger->debug('2FA verification successful', [
                'user_id' => $user->id
            ]);
            
            return true;
        }
        
        // Try backup code
        if ($this->verifyBackupCode($user, $code)) {
            $this->logger->info('2FA backup code used', [
                'user_id' => $user->id
            ]);
            return true;
        }
        
        $this->logger->warning('2FA verification failed', [
            'user_id' => $user->id
        ]);
        
        return false;
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(User $user): bool
    {
        return (bool)$user->totp_enabled;
    }
    
    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(User $user): array
    {
        $backupCodes = $this->generateBackupCodes();
        $hashedCodes = array_map(fn($code) => hash('sha256', $code), $backupCodes);
        
        $user->backup_codes = json_encode($hashedCodes);
        $user->save();
        
        $this->logger->info('Backup codes regenerated', [
            'user_id' => $user->id
        ]);
        
        return $backupCodes;
    }
    
    /**
     * Get remaining backup code count
     */
    public function getBackupCodeCount(User $user): int
    {
        if (empty($user->backup_codes)) {
            return 0;
        }
        
        $codes = json_decode($user->backup_codes, true);
        return is_array($codes) ? count($codes) : 0;
    }
    
    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================
    
    /**
     * Generate random Base32 secret
     */
    private function generateRandomSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Generate backup codes
     */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODE_COUNT; $i++) {
            $code = '';
            for ($j = 0; $j < self::BACKUP_CODE_LENGTH; $j++) {
                $code .= random_int(0, 9);
            }
            // Format: XXXX-XXXX
            $codes[] = substr($code, 0, 4) . '-' . substr($code, 4);
        }
        return $codes;
    }
    
    /**
     * Build otpauth:// URI for QR code
     */
    private function buildOtpAuthUri(string $email, string $secret): string
    {
        $issuer = 'CI-Inbox';
        $label = rawurlencode("$issuer:$email");
        
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=%s&digits=%d&period=%d',
            $label,
            $secret,
            rawurlencode($issuer),
            strtoupper(self::TOTP_ALGORITHM),
            self::TOTP_DIGITS,
            self::TOTP_PERIOD
        );
    }
    
    /**
     * Verify TOTP code
     */
    private function verifyCode(string $secret, string $code): bool
    {
        // Normalize code (remove spaces, dashes)
        $code = preg_replace('/[^0-9]/', '', $code);
        
        if (strlen($code) !== self::TOTP_DIGITS) {
            return false;
        }
        
        // Current timestamp counter
        $timeCounter = floor(time() / self::TOTP_PERIOD);
        
        // Check current period and allow clock drift
        for ($i = -self::DRIFT_WINDOW; $i <= self::DRIFT_WINDOW; $i++) {
            $expectedCode = $this->generateTOTP($secret, $timeCounter + $i);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for a given counter
     */
    private function generateTOTP(string $secret, int $counter): string
    {
        // Decode Base32 secret
        $secretBytes = $this->base32Decode($secret);
        
        // Pack counter as 8-byte big-endian
        $counterBytes = pack('N*', 0, $counter);
        
        // Generate HMAC-SHA1
        $hash = hash_hmac(self::TOTP_ALGORITHM, $counterBytes, $secretBytes, true);
        
        // Dynamic truncation (RFC 4226)
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $otp = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::TOTP_DIGITS);
        
        return str_pad((string)$otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }
    
    /**
     * Decode Base32 string
     */
    private function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $input = rtrim($input, '=');
        
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $val = strpos(self::BASE32_CHARS, $char);
            
            if ($val === false) {
                continue; // Skip invalid characters
            }
            
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        
        return $output;
    }
    
    /**
     * Verify backup code
     */
    private function verifyBackupCode(User $user, string $code): bool
    {
        if (empty($user->backup_codes)) {
            return false;
        }
        
        // Normalize code
        $code = preg_replace('/[^0-9]/', '', $code);
        $formattedCode = substr($code, 0, 4) . '-' . substr($code, 4);
        $hashedCode = hash('sha256', $formattedCode);
        
        $backupCodes = json_decode($user->backup_codes, true);
        
        if (!is_array($backupCodes)) {
            return false;
        }
        
        // Find and remove used code
        $index = array_search($hashedCode, $backupCodes, true);
        
        if ($index === false) {
            return false;
        }
        
        // Remove used code
        unset($backupCodes[$index]);
        $user->backup_codes = json_encode(array_values($backupCodes));
        $user->last_2fa_at = Carbon::now();
        $user->save();
        
        return true;
    }
}
