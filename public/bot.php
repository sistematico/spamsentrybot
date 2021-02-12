<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('LOGPATH', $envs['LOGPATH']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
define('BOT_URL',  $actual_link);

require_once '../lib/log.php';
require_once '../lib/api.php';
require_once '../lib/db.php';

function identifyAt($string, $member, $chat)
{
    //curl -X POST "https://api.telegram.org/bot1658737482:AAGo_s34pSQ6acWrfXZM5gWZyJYiBdIblks/sendMessage" -d "chat_id=-1001325544995&text=my sample text"
    // {"ok":true,"result":{"message_id":325,"from":{"id":1658737482,"is_bot":true,"first_name":"SPAM Sentry Bot","username":"spamsentrybot"},"chat":{"id":-1001325544995,"title":"Packet Loss Developers","type":"supergroup"},"date":1613148035,"text":"my sample text"}}[lucas@majestic ~]$ 

    if (strpos($a, 'are') !== false) {
        echo 'true';
    }
}

function filterMessage($member, $message_id, $chat_id, $reply_id)
{
    $msg_id = $reply_id !== null ? $reply_id : $message_id; 

    if (strpos($a, 'are') !== false) {
        echo 'true';
    }
}

function processDelete($member, $message_id, $chat_id, $reply_id)
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
                if ($member['status'] === 'creator' || $member['status'] === 'administrator') {
                    apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));

                    $msg = "Chat ID: {$chat_id}\n";
                    $msg .= "Message ID: {$message_id}\n";
                    $msg .= "User ID: {$user_id}\n";
                    $msg .= "Reply ID: {$reply_id}\n";
                    $msg .= "URL: " . BOT_URL;
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $msg));     
                }           
                break;
            case (strpos($text, '/del') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));

                processDelete($member, $message_id, $chat_id, $reply_id);
                break;
            case (strpos($text, '/logs') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));

                $log = readLog();
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "Logs: {$log['log']}\n\n{$log['linhas']} linhas."));
                break;
            case (strpos($text, '/ban') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));

                //apiRequest("sendVideo", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "video" => CABRON_URL . 'vid/fogo.mp4'));
                apiRequest('sendSticker', array('chat_id' => $chat_id, 'sticker' => 'CAACAgEAAxkBAAEB371gJb_kkLwJ8bU0Z2_MM41hn8ZRsQACPAADnjOcH14Lzxv4uFR0HgQ'));
                break;
            case (strpos($text, '/lol') === 0):
                apiRequest('sendVideo', array('chat_id' => $chat_id, 'video' => BOT_URL . 'vid/no.mp4'));
                break;
            case (strpos($text, '/id') === 0):
                $id = explode(' ', $text)[1];
                if (isset($id) && !empty($id)) {
                    $info = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $id));
                    error_log("--------   ID   ----------", 3, "../logs/bot.log");
                    error_log(implode(',', $info), 3, "../logs/bot.log");
                    error_log("--------  FIM ID  --------", 3, "../logs/bot.log");
                    apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "ID: {$id}"));
                }
                break;
            default:
                break;
        }
    }
}

$file_db = null;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update)
    exit;

if (isset($update["message"]))
    processMessage($update["message"]);
