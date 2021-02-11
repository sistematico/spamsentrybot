<?php

$envs = parse_ini_file('../.env.local');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

// require_once '../lib/db.php';
require_once '../lib/api.php';

function delete($ctx)
{

}

function processMessage($message)
{
    // process incoming message
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $reply_id = $message['reply_to_message']['message_id'];
    $user_id = $message['from']['id'];

    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = (isset($message['reply_to_message']['from']['username']) ? $message['reply_to_message']['from']['username'] : $message['reply_to_message']['from']['first_name'] . ' ' . $message['reply_to_message']['from']['last_name'] );

    if (isset($message['text'])) {
        $text = $message['text'];
        $member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));
        $role = $member['status'];

        switch ($text) {
            case (strpos($text, '/debug') === 0):
                // apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Chat ID: {$chat_id}"));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Message ID: {$message_id}"));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "User ID: {$user_id}"));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Reply ID: {$reply_id}"));
                break;
            case (strpos($text, '/del') === 0):
                if ($member['status'] === 'creator' || $member['status'] === 'administrator') {
                    if (isset($reply_id) && !empty(isset($reply_id))) {
                        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));                
                        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
                    } else {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Uso incorreto, responda a mensagem que deseja apagar com a palavra /del"));
                    }
                } else {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Comando somente para admins."));
                }
                break;
            case (strpos($text, '/member') === 0):
                //apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Member: " . implode(",",$muser) . $member['user'] . $member['status']));
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Role: {$role}"));
                break;
            case (strpos($text, '/logs') === 0):
                // Conta as linhas...
                $linecount = 0;
                $handle = fopen("../logs/bot.log", "r");
                while(!feof($handle)){
                  $line = fgets($handle);
                  $linecount++;
                }                
                fclose($handle);
                $linhas = $linecount;

                // Lê os logs
                $logfile = fopen("../logs/bot.log", "r") or die("Unable to open file!");
                $log = fread($logfile,filesize("../logs/bot.log"));
                fclose($logfile);

                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Logs: " . $log . "\n\n{$linhas} linhas."));
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
