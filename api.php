<?php

define('CACHE_DIR', __DIR__ . '/cache/');
define('LOG_DIR', __DIR__ . '/logs/');
define('CACHE_EXPIRATION', 86400); // 24 horas

if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);
if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);

function cacheGet(string $key) {
    $file = CACHE_DIR . md5($key) . '.cache';

    if (!file_exists($file)) {
        return false;
    }

    if (filemtime($file) + CACHE_EXPIRATION < time()) {
        unlink($file);
        return false;
    }

    $content = file_get_contents($file);
    return json_decode($content, true);
}

function cacheSet(string $key, $value): void {
    $file = CACHE_DIR . md5($key) . '.cache';
    file_put_contents($file, json_encode($value));
}

function logQuery(string $password, array $result): void {
    $logFile = LOG_DIR . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');

    $len = strlen($password);
    if ($len > 4) {
        $masked = substr($password, 0, 2) . str_repeat('*', $len - 4) . substr($password, -2);
    } else {
        $masked = str_repeat('*', $len);
    }

    $status = $result['leaked'] ? "LEAKED ({$result['count']})" : "SAFE";
    $error = $result['error'] ?? '';

    $logLine = "[$timestamp] Password: $masked | Status: $status" . ($error ? " | Error: $error" : "") . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

function checkPasswordLeak(string $password): array {
    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);

    $cached = cacheGet($sha1);
    if ($cached !== false) {
        return $cached;
    }

    $url = "https://api.pwnedpasswords.com/range/$prefix";
    $response = @file_get_contents($url);
    if ($response === false) {
        return ["leaked" => false, "error" => "Erro ao consultar API"];
    }

    $lines = explode("\n", $response);
    $found = ["leaked" => false];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        list($hashSuffix, $count) = explode(":", $line);
        if ($hashSuffix === $suffix) {
            $found = ["leaked" => true, "count" => intval($count)];
            break;
        }
    }

    cacheSet($sha1, $found);
    return $found;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Parâmetro 'password' obrigatório"]);
        exit;
    }

    $result = checkPasswordLeak($data['password']);
    logQuery($data['password'], $result);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método HTTP não permitido. Use POST."]);
}
