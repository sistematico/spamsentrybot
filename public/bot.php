<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('LOGPATH', $envs['LOGPATH']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

require_once '../lib/log.php';
require_once '../lib/api.php';
require_once '../lib/db.php';

function delete($member, $message_id, $chat_id, $reply_id = null)
{
    if ($member['status'] === 'creator' || $member['status'] === 'administrator') {
        if ($reply_id !== null) {
            apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));                
            apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
        } else {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Uso incorreto, responda a mensagem que deseja apagar com a palavra /del"));
        }
    } else {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Comando somente para admins."));
    }
}

function processMessage($message)
{
    // process incoming message
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $reply_id = (isset($message['reply_to_message']['message_id']) ? $message['reply_to_message']['message_id'] : null);
    $user_id = $message['from']['id'];

    $member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));

    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = (isset($message['reply_to_message']['from']['username']) ? $message['reply_to_message']['from']['username'] : $message['reply_to_message']['from']['first_name'] . ' ' . $message['reply_to_message']['from']['last_name'] );

    if (isset($message['text'])) {
        $text = $message['text'];

        switch ($text) {
            case (strpos($text, '/debug') === 0):
                // apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                $msg = "Chat ID: {$chat_id}";
                $msg .= "Message ID: {$message_id}";
                $msg .= "User ID: {$user_id}";
                $msg .= "Reply ID: {$reply_id}";
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $msg));                
                break;
            case (strpos($text, '/del') === 0):
                delete($member, $message_id, $chat_id, $reply_id);
                break;
            case (strpos($text, '/logs') === 0):
                $log = readLog();
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Logs: {$log['log']}\n\n{$log['linhas']} linhas."));
                break;
            case (strpos($text, '/ban') === 0):
                //requisicao("sendVideo", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "video" => CABRON_URL . 'vid/fogo.mp4'));
                apiRequest("sendSticker", array('chat_id' => $chat_id, "sticker" => 'CAACAgEAAxkBAAEB371gJb_kkLwJ8bU0Z2_MM41hn8ZRsQACPAADnjOcH14Lzxv4uFR0HgQ'));
                break;
            case (strpos($text, '/lol') === 0):
                apiRequest("sendAnimation", array('chat_id' => $chat_id, "animation" => '../vid/no.mp4'));
                break;
            default:
                break;
        }
    }
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update)
    exit;

if (isset($update["message"]))
    processMessage($update["message"]);
