<?php
// Replace with your actual Telegram Bot Token from BotFather
define('BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN');

// Get the incoming update from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$chatId = $update['message']['chat']['id'];
$text = trim($update['message']['text'] ?? '');

// Handle /start command
if ($text === '/start') {
    sendMessage($chatId, "Welcome! Send me a valid EIIN number, and I will fetch the teacher details for you.");
    exit;
}

// Validate if the input is a valid numeric EIIN
if (!preg_match('/^\d+$/', $text)) {
    sendMessage($chatId, "Please send a valid numeric EIIN number (digits only).");
    exit;
}

// Run the API logic
$eiin = $text;
$url = 'https://emis.gov.bd/emis/Portal/GetTeacherDetails';
$csrf = 'FYdlvws4yxuNHAUXRaOXLRG1WGYsclc-uNAWxja4RHm7YCERV2tTPjgluf620W_IkrhILwj5Gew6EjPvoM3j7qdJRNZoJw1Tjwc8ovOZo841';
$cookie = '__RequestVerificationToken_L2VTaXM1=1DqrczM4sGONP9yE0-nyvq0oDK5LZ1QnI1ZWFlQevd89sInfRE7ojeto05oC8WystS2ZrXbkhxJ1dFZbxv1_fJFcgvtiMU_2jOV6y02BxSg1; CSRF-TOKEN=' . $csrf;

$postData = http_build_query([
    'instituteId' => '',
    'EIIN' => $eiin,
    'isTeacher' => ''
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Linux; Android 15; N76)',
        'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        'X-CSRF-Token: ' . $csrf,
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://emis.gov.bd',
        'Referer: https://emis.gov.bd/EMIS/portalone',
        'Cookie: ' . $cookie
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    sendMessage($chatId, "Error connecting to the government portal: " . $error);
    exit;
}

$decoded = json_decode($response, true);
$formattedResponse = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (strlen($formattedResponse) > 4000) {
    $formattedResponse = substr($formattedResponse, 0, 4000) . "\n... (truncated)";
}

sendMessage($chatId, "```json\n" . $formattedResponse . "\n```", 'Markdown');

// Helper function to send messages back to Telegram
function sendMessage($chatId, $message, $parseMode = '') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];
    if ($parseMode) {
        $data['parse_mode'] = $parseMode;
    }
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}
?>
