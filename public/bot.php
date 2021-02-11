<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

require_once '../lib/api.php';

function processMessage($message)
{
    // process incoming message
    $message_id = $message['message_id'];
    //$reply_id = $message['reply_to_message']['id'];
    $reply_id = $message['reply_to_message']['message_id'];
    //$reply_id3 = $message['message_id']['reply_to_message'];
    $chat_id = $message['chat']['id'];
    $username = '@' . $message['from']['username'];
    $originalUsername = '@' . $message['reply_to_message']['from']['username'];

    if (isset($message['text'])) {
        $text = $message['text'];

        switch ($text) {
            case (strpos($text, "/start") === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Olá {$username}!\n\nEm que posso te ajudar!?"));
                break;

            case (strpos($text, "/del") === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Tomei a liberdade de apagar esta mensagem.\n\nID: ${reply_id}\n\nUsuário original: $originalUsername"));
                apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));                
                //apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Olá {$username}{$username2}!\n\nEm que posso te ajudar!?"));
                break;

            case 'Oláa':
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Olá {$username}!\n\nMessage ID: ${message_id}\n\nEm que posso te ajudar!?"));
                break;
            case 'Debug':
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Username: $username"));
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
