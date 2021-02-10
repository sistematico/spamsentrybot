<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

require_once '../lib/api.php';

function processMessage($message)
{
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];

    if (isset($message['text'])) {
        $text = $message['text'];

        if (strpos($text, "/start") === 0) {
            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
                'keyboard' => array(array('Hello', 'Hi')),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        } else if ($text === "Hello" || $text === "Hi") {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
        } else if (strpos($text, "/stop") === 0) {
            // stop now
        } else {
            apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
        }
    } else {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
}

define('WEBHOOK_URL', $envs['WEBHOOK']);

if (php_sapi_name() == 'cli') {
    // if run from console, set or delete webhook
    apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
    exit;
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit; // receive wrong update, must not happen
}

if (isset($update["message"])) {
    processMessage($update["message"]);
}
