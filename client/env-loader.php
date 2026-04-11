<?php
/**
 * Environment Configuration Loader
 */

if (!isset($GLOBALS['_ENV_DEBUG'])) {
    $GLOBALS['_ENV_DEBUG'] = [
        'loaded_file' => null,
        'loaded_vars' => [],
        'total_vars'  => 0,
        'errors'      => [],
        'debug_mode'  => false
    ];
}

if (!function_exists('loadEnv')) {
    function loadEnv($filePath = null, $debug = false) {

        if (!$filePath) {
            $filePath = dirname(__DIR__) . '/.env';
        }

        $GLOBALS['_ENV_DEBUG']['debug_mode'] = $debug;

        if (!file_exists($filePath)) {
            $error = ".env file not found: " . $filePath;
            $GLOBALS['_ENV_DEBUG']['errors'][] = $error;
            if ($debug) error_log($error);
            return false;
        }

        $GLOBALS['_ENV_DEBUG']['loaded_file'] = $filePath;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes
                if (
                    (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
                ) {
                    $value = substr($value, 1, -1);
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");

                $displayValue = (stripos($key, 'password') !== false ||
                                 stripos($key, 'token') !== false ||
                                 stripos($key, 'secret') !== false)
                                 ? '***MASKED***'
                                 : $value;

                $GLOBALS['_ENV_DEBUG']['loaded_vars'][$key] = $displayValue;
                $count++;
            }
        }

        $GLOBALS['_ENV_DEBUG']['total_vars'] = $count;

        return true;
    }
}

// Auto load only once
if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);
    loadEnv();
}

if (!function_exists('getEnv')) {
    function getEnv($key, $default = null) {
        return isset($_ENV[$key]) && $_ENV[$key] !== ''
            ? $_ENV[$key]
            : $default;
    }
}

if (!function_exists('getEnvDebugInfo')) {
    function getEnvDebugInfo() {
        return [
            'file'        => $GLOBALS['_ENV_DEBUG']['loaded_file'],
            'total_vars'  => $GLOBALS['_ENV_DEBUG']['total_vars'],
            'vars_loaded' => $GLOBALS['_ENV_DEBUG']['loaded_vars'],
            'errors'      => $GLOBALS['_ENV_DEBUG']['errors'],
            'timestamp'   => date('Y-m-d H:i:s')
        ];
    }
}

if (!function_exists('checkRequiredEnvVars')) {
    function checkRequiredEnvVars($required = []) {
        $missing = [];

        foreach ($required as $key) {
            if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            return [
                'ok' => false,
                'missing' => $missing,
                'message' => "Missing: " . implode(', ', $missing)
            ];
        }

        return ['ok' => true];
    }
}

if (!function_exists('getMqttConfig')) {
    function getMqttConfig() {
        return [
            'broker'   => getEnv('MQTT_BROKER'),
            'username' => getEnv('MQTT_USERNAME'),
            'password' => getEnv('MQTT_PASSWORD'),
        ];
    }
}

if (!function_exists('getTwilioConfig')) {
    function getTwilioConfig() {
        return [
            'account_sid' => getEnv('TWILIO_ACCOUNT_SID'),
            'auth_token'  => getEnv('TWILIO_AUTH_TOKEN'),
            'from_number' => getEnv('TWILIO_FROM_NUMBER'),
        ];
    }
}

if (!function_exists('getDatabaseConfig')) {
    function getDatabaseConfig() {
        return [
            'host' => getEnv('DB_HOST', 'localhost'),
            'user' => getEnv('DB_USER', 'root'),
            'pass' => getEnv('DB_PASS', ''),
            'name' => getEnv('DB_NAME', 'flowguard'),
        ];
    }
}

// MQTT Topics Configuration
if (!function_exists('getMqttTopics')) {
    function getMqttTopics() {
        $base = getEnv('MQTT_TOPIC_BASE', 'esp32_1');
        return [
            'base'           => $base,
            'dht22'          => $base . '/dht22',
            'soil'           => $base . '/soil',
            'flow'           => $base . '/flow',
            'relay_status'   => $base . '/relay/status',
            'relay_command'  => $base . '/relay',
        ];
    }
}
?>