<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

// require_once '../lib/db.php';
require_once '../lib/api.php';

function processMessage($message)
{
    // process incoming message
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $user_id = $message['from']['id'];
    $reply_id = $message['reply_to_message']['message_id'];
    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = (isset($message['reply_to_message']['from']['username']) ? $message['reply_to_message']['from']['username'] : $message['reply_to_message']['from']['first_name'] . ' ' . $message['reply_to_message']['from']['last_name'] );
    $role = $message['from']['id']['status'];

    error_log("Message Dump: " . implode(",", array_keys($message)), 3, "../logs/bot.log");

    if (isset($message['text'])) {
        $text = $message['text'];
        $member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));

        switch ($text) {
            case '/spam':
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Olá {$username}(ID: {$user_id})!\n\nEm que posso te ajudar!?"));
                break;
            case (strpos($text, '/del') === 0):
                if ($role !== 'creator' || $role !== 'administrator')
                    break;
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
                //apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Tomei a liberdade de apagar esta mensagem.\n\nID: ${reply_id}\n\nAdmin: ${user_id}${user_id2}\n\nRole: ${role}\n\nUsuário original: $originalUsername"));
                apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));                
                break;
            case (strpos($text, '/debug') === 0):
                if (isset($originalUsername)) {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Username: $originalUsername"));
                } else {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Username: $username"));
                }
                break;
            case (strpos($text, '/json') === 0):
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "DUMP: " . implode(",",$message)));
                break;
            case (strpos($text, '/member') === 0):
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Member: " . implode(",",array_keys($member))));
                break;
            case (strpos($text, '/logs') === 0):
                $logfile = fopen("../logs/bot.log", "r") or die("Unable to open file!");
                $log = fread($logfile,filesize("../logs/bot.log"));
                fclose($logfile);
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Logs: " . $log));
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
