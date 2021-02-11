<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

require_once '../lib/api.php';

function processMessage($message)
{
    // process incoming message
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $user_id = $message['user_id'];
    $user_id2 = $message['user']['id'];
    $reply_id = $message['reply_to_message']['message_id'];
    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = $message['reply_to_message']['from']['username'];
    $role = $message['user']['id']['status'];

    if (isset($message['text'])) {
        $text = $message['text'];

        switch ($text) {
            case (strpos($text, "/start") === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Olá {$username}!\n\nEm que posso te ajudar!?"));
                break;
            case (strpos($text, "/del") === 0):
                if ($role !== 'creator' || $role !== 'administrator')
                    break;
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                //apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Tomei a liberdade de apagar esta mensagem.\n\nID: ${reply_id}\n\nAdmin: ${user_id}${user_id2}\n\nRole: ${role}\n\nUsuário original: $originalUsername"));
                apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));                
                break;
            case (strpos($text, "/debug") === 0):
                if (isset($originalUsername)) {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Username: $originalUsername"));
                } else {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Username: $username"));
                }
                break;
            case (strpos($text, "/json") === 0):
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "DUMP: " . var_dump($message)));
                break;
            default:
                break;
        }

        // if (strpos($text, "/start") === 0) {
        //     apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
        //         'keyboard' => array(array('Hello', 'Hi')),
        //         'one_time_keyboard' => true,
        //         'resize_keyboard' => true
        //     )));
        // } else if ($text === "Hello" || $text === "Hi") {
        //     apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
        // } else if (strpos($text, "/stop") === 0) {
        //     // stop now
        // } else {
        //     //apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
        // }
    }
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update)
    exit;

if (isset($update["message"]))
    processMessage($update["message"]);
