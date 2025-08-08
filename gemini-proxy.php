<?php
// Keep the key private; only same-origin pages can call this
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST'] ?? '';
if ($origin && parse_url($origin, PHP_URL_HOST) !== $host) {
  http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
}

$apiKey = getenv('GEMINI_API_KEY') ?: ($_SERVER['GEMINI_API_KEY'] ?? ($_ENV['GEMINI_API_KEY'] ?? ''));
if (!$apiKey) { http_response_code(500); echo json_encode(['error'=>'API key not configured']); exit; }

// Read JSON from client: { model: "...", ...original Gemini payload... }
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) { http_response_code(400); echo json_encode(['error'=>'bad json']); exit; }

// Pull model (default if omitted) and lightly validate
$model = $in['model'] ?? 'gemini-2.5-flash';
if (!preg_match('/^[\w.\-:]+$/', $model)) { http_response_code(400); echo json_encode(['error'=>'invalid model']); exit; }

// Optional allowlist (uncomment to lock down)
// $allowed = ['gemini-2.5-flash','gemini-2.5-pro','gemini-1.5-flash','gemini-1.5-pro'];
// if (!in_array($model, $allowed, true)) { http_response_code(400); echo json_encode(['error'=>'model not allowed']); exit; }

// Forward everything else as-is (remove "model" key before forwarding)
unset($in['model']);
$url = 'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent?key='.urlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($in, JSON_UNESCAPED_SLASHES),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 60,
]);
$out  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 502;
curl_close($ch);

http_response_code($code);
echo $out ?: json_encode(['error'=>'empty upstream response']);
