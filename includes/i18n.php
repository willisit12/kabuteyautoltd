<?php
/**
 * includes/i18n.php
 * Core Localization & Currency Manager
 */

class I18n {
    private static $translations = [];
    private static $currentLocale = 'en';
    private static $currentCurrency = 'USD';
    private static $rates = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'AED' => 3.67,
        'CNY' => 7.23,
        'GHS' => 15.50
    ];
    private static $cacheFile = __DIR__ . '/../cache/rates.json';

    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Handle Language State
        if (isset($_GET['lang'])) {
            self::$currentLocale = clean($_GET['lang']);
            $_SESSION['lang'] = self::$currentLocale;
        } elseif (isset($_SESSION['lang'])) {
            self::$currentLocale = $_SESSION['lang'];
        }

        // Handle Currency State
        if (isset($_GET['currency'])) {
            self::$currentCurrency = strtoupper(clean($_GET['currency']));
            $_SESSION['currency'] = self::$currentCurrency;
        } elseif (isset($_SESSION['currency'])) {
            self::$currentCurrency = $_SESSION['currency'];
        }

        self::syncRates();
        self::loadTranslations();
    }

    private static function syncRates() {
        $cacheDir = dirname(self::$cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $needsUpdate = true;
        if (file_exists(self::$cacheFile)) {
            $lastUpdated = filemtime(self::$cacheFile);
            if ((time() - $lastUpdated) < 86400) { // 24 hours
                $needsUpdate = false;
                $cachedData = json_decode(file_get_contents(self::$cacheFile), true);
                if ($cachedData) {
                    self::$rates = $cachedData;
                }
            }
        }

        if ($needsUpdate && isset($_ENV['EXCHANGE_RATE_API_KEY'])) {
            $apiKey = $_ENV['EXCHANGE_RATE_API_KEY'];
            $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD";
            
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $response = @file_get_contents($url, false, $ctx);
                if ($response) {
                    $data = json_decode($response, true);
                    if (isset($data['result']) && $data['result'] === 'success') {
                        self::$rates = $data['conversion_rates'];
                        file_put_contents(self::$cacheFile, json_encode(self::$rates));
                    }
                }
            } catch (Exception $e) {
                // Fallback to static rates if API fails
            }
        }
    }

    private static function loadTranslations() {
        $path = __DIR__ . "/../lang/" . self::$currentLocale . ".json";
        if (file_exists($path)) {
            $content = file_get_contents($path);
            self::$translations = json_decode($content, true) ?? [];
        }
    }

    public static function translate($key, $placeholders = []) {
        $text = self::$translations[$key] ?? $key;
        foreach ($placeholders as $k => $v) {
            $text = str_replace("{{$k}}", $v, $text);
        }
        return $text;
    }

    public static function convert($amount, $from = 'USD') {
        if (!isset(self::$rates[self::$currentCurrency])) return $amount;
        
        // Convert to USD base first
        $base = $amount / (self::$rates[$from] ?? 1.0);
        // Convert to target
        return $base * self::$rates[self::$currentCurrency];
    }

    public static function getCurrency() {
        return self::$currentCurrency;
    }

    public static function getLocale() {
        return self::$currentLocale;
    }
}

// Global helper functions
function __($key, $placeholders = []) {
    return I18n::translate($key, $placeholders);
}

I18n::init();
