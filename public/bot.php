<?php

// Text: curl -X POST "https://api.telegram.org/bot[TOKEN]/sendMessage" -d "chat_id=-1001325544995&text=my sample text"
// Video: curl -X -d "chat_id=-1001325544995&video=/var/www/bots.lucasbrum.net/spamsentrybot/public/vid/no.mp4" https://api.telegram.org/bot[TOKEN]/sendVideo

$envs = parse_ini_file('../.env.local');

define('BLACKLIST', $envs['BLACKLIST'] ? dirname(__DIR__) . DIRECTORY_SEPARATOR . $envs['BLACKLIST'] : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'txt/blacklist.txt');
define('DATABASE', $envs['DATABASE'] ? dirname(__DIR__) . DIRECTORY_SEPARATOR . $envs['DATABASE'] : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db/banco.db');

define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);

define('LOGPATH', '../logs/bot.log');

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
define('BOT_URL',  $actual_link);

define('VIDEOS', __DIR__ . DIRECTORY_SEPARATOR . 'vid' . DIRECTORY_SEPARATOR);
define('AUDIOS', __DIR__ . DIRECTORY_SEPARATOR . 'aud' . DIRECTORY_SEPARATOR);

try {
    $file_db = new \PDO("sqlite:" . DATABASE);
} catch (\PDOException $e) {
    // handle the exception here
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'log.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'api.php';
//require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'db.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'bl.php';


function filterMessage(string $message):bool
{
    $blacklist = explode(PHP_EOL, file_get_contents(BLACKLIST));
    foreach($blacklist as $bl) {
        if (stripos(strtolower($message),$bl) !== false) return true;
    }
    return false;
}

function processDelete($message_id, $chat_id, $reply_id, $isAdmin = false)
{
    if ($isAdmin && $reply_id) {
        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));
        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
    }
}

function warnUser($message_id, $chat_id, $reply_id, $isAdmin = false)
{
    if ($isAdmin && $reply_id) {
        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));
        apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
    } 
}

function processMessage($message)
{
    // process incoming message
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $reply_id = $message['reply_to_message']['message_id'] ?? false;
    $user_id = $message['from']['id'];

    //$getChat = apiRequest("getChat", array('chat_id' => $chat_id));
    //$groupName = basename($getChat['invite_link']);

    $member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));
    $isAdmin = $member['status'] === 'creator' || $member['status'] === 'administrator';
    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = (isset($message['reply_to_message']['from']['username']) ? $message['reply_to_message']['from']['username'] : $message['reply_to_message']['from']['first_name'] . ' ' . $message['reply_to_message']['from']['last_name']);

    if (isset($update['callback_query']) && isset($callback_id)) {
        apiRequest("answerCallbackQuery", array('callback_id' => $callback_id, 'text' => 'Uhullll', 'show_alert' => true));
    }

    if (isset($message['text'])) {
        $text = $message['text'];

        switch ($text) {
            case (strpos($text, '/del') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                processDelete($message_id, $chat_id, $reply_id, $isAdmin);
                break;

            case (strpos($text, '/warn') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                warnUser($message_id, $chat_id, $reply_id, $isAdmin);
                break;

            case (strpos($text, '/ban') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                apiRequest('sendSticker', array('chat_id' => $chat_id, 'sticker' => 'CAACAgEAAxkBAAEB371gJb_kkLwJ8bU0Z2_MM41hn8ZRsQACPAADnjOcH14Lzxv4uFR0HgQ'));
                break;

            case (strpos($text, '/blacklist') === 0):
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => BLACKLIST));
                break;

            case (strpos($text, '/database') === 0):
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => blacklistAdd($file_db)));
                break;

            case (strpos($text, '/gline') === 0):
                apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'BAN Global?', 'reply_markup' => array(
                    'keyboard' => array(array('Sim', 'Não')),
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true)));
                break;
            case (strpos($text, '/kline') === 0):
                apiRequestJson(
                    "sendMessage", 
                    array(
                        'chat_id' => $chat_id, 
                        'text' => 'Olá, '. $message['from']['first_name'],
                        'reply_markup' => array(
                            'inline_keyboard' => array(
                                array(
                                    array('text'=>'Sim','callback_data'=>'sim'),
                                    array('text'=>'Não','callback_data'=>'nao')
                                )
                            ),
                            'one_time_keyboard' => true,
                            'selective' => true
                        )
                    )
                );
                break;
            case (strpos($text, '/pc') === 0):
                $fp = new CURLFile(realpath(VIDEOS . 'pc.mp4'));
                apiRequestFile('sendVideo', array('chat_id' => $chat_id, 'video' => $fp));
                break;
            default:
                if (filterMessage($text)) {
                    apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
                }
                break;
        }
    }
}

$file_db = null;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update)
    exit;

if (isset($update["message"])) {
    if (isset($update['callback_query'])) {
        $callback_id = $update['callback_query']['id'];
        $callback_user = $update['callback_query']['from']['id'];
        $callback_content = $update['callback_query']['data'];
    }
    processMessage($update["message"]);
}
