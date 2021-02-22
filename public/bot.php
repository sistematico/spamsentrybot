<?php

// Text: curl -X POST "https://api.telegram.org/bot[TOKEN]/sendMessage" -d "chat_id=-1001325544995&text=my sample text"
// Video: curl -X -d "chat_id=-1001325544995&video=/var/www/bots.lucasbrum.net/spamsentrybot/public/vid/no.mp4" https://api.telegram.org/bot[TOKEN]/sendVideo

$envs = parse_ini_file('../.env.local');
define('LOGPATH', '../logs/bot.log');
define('BOT_TOKEN', $envs['TOKEN']);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBHOOK_URL', $envs['WEBHOOK']);
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
define('BOT_URL',  $actual_link);

define('VIDEOS', __DIR__ . DIRECTORY_SEPARATOR . 'vid' . DIRECTORY_SEPARATOR);
define('AUDIOS', __DIR__ . DIRECTORY_SEPARATOR . 'aud' . DIRECTORY_SEPARATOR);

require_once '../lib/log.php';
require_once '../lib/api.php';
require_once '../lib/db.php';

function filterMessage(string $message):bool
{
    $blacklist = ['wa.me','t.me','whatsapp.com'];

    foreach($blacklist as $text) {
        if (stripos(strtolower($message),$text) !== false) return true;
    }
    return false;
}

function filterDelete($message_id, $chat_id)
{
    apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
}

function processDelete($message_id, $chat_id, $reply_id, $isAdmin = false)
{
    if ($isAdmin) {
        if ($reply_id) {
            apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $reply_id));
            apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
        } else {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Uso incorreto, responda a mensagem que deseja apagar com a palavra /del"));
        }
    } else {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Comando somente para admins."));
    }
}

function warnUser($user, $message_id, $chat_id, $reply_id, $isAdmin = false)
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

    //$member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));
    $member = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));
    $member = $member['result'];
    $isAdmin = ($member['status'] === 'creator' || $member['status'] === 'administrator' ? true : false);

    $username = (isset($message['from']['username']) ? $message['from']['username'] : $message['from']['first_name'] . ' ' . $message['from']['last_name']);
    $originalUsername = (isset($message['reply_to_message']['from']['username']) ? $message['reply_to_message']['from']['username'] : $message['reply_to_message']['from']['first_name'] . ' ' . $message['reply_to_message']['from']['last_name']);

    if (isset($message['text'])) {
        $text = $message['text'];

        switch ($text) {
            case (strpos($text, '/debug') === 0):
                //if ($isAdmin) {
                    apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));

                    $msg = "Chat ID: {$chat_id}\n";
                    $msg .= "Message ID: {$message_id}\n";
                    $msg .= "User ID: {$user_id}\n";
                    $msg .= "Reply ID: {$reply_id}\n";
                    $msg .= "Username: {$username}\n";
                    $msg .= "Original Username: {$originalUsername}\n";
                    $msg .= "Member Status: {$member['status']}\n";
                    $msg .= "Member Is Bot: {$member['is_bot']}\n";
                    $msg .= "Member First: {$member['first_name']}\n";
                    $msg .= "Member User: {$member['username']}\n";
                    $msg .= "Member Arr: " . implode(',', $member) . "\n";
                    $msg .= "URL: " . BOT_URL;
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $msg));
                //}
                break;
            case (strpos($text, '/del') === 0):
                apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                processDelete($message_id, $chat_id, $reply_id, $isAdmin);
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

            case (strpos($text, '/gline') === 0):
                apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'BAN Global?', 'reply_markup' => array(
                    'keyboard' => array(array('Sim', 'Não')),
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true)));

                //apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                //apiRequest('sendSticker', array('chat_id' => $chat_id, 'sticker' => 'CAACAgEAAxkBAAEB371gJb_kkLwJ8bU0Z2_MM41hn8ZRsQACPAADnjOcH14Lzxv4uFR0HgQ'));
                break;

            case (strpos($text, '/kline') === 0):
                //apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'BAN Global?', 'reply_markup' => array('inline_keyboard' => array(array('Sim', 'Não')))));

                apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Olá, '. $message['from']['first_name'].
                '.........', 
                'reply_markup' => array('inline_keyboard' => array(
                                                             //linha 1
                                                             array(
                                                                 array('text'=>'Sim','callback_data'=>'sim'), //botão 1
                                                                 array('text'=>'Sim','callback_data'=>'nao')//botão 2
                                                              )
                                                            //   //linha 2
                                                            //  array(
                                                            //      array('text'=>'Lotofácil','url'=>'http://g1.globo.com/loterias/lotofacil.html'), //botão 3
                                                            //      array('text'=>'Lotomania','url'=>'http://g1.globo.com/loterias/lotomania.html')//botão 4
                                                            //   )
        
                                                            ),
                                                            'one_time_keyboard' => true,
                                                            'selective' => true
                                        )));
                break;

            case (strpos($text, '/ping') === 0):
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "\u{1F64C}"));
            break;
            case (strpos($text, '/lol') === 0):
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> text'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> video'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'animation' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> animation'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendVideo -> video'));
                // apiRequest('sendVideo', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'APIREQUESTJSON -> sendVideo -> video'));
                // apiRequestJson('sendVideo', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));

                $fp = new CURLFile(realpath(VIDEOS . 'no.mp4'));

                apiRequestFile('sendVideo', array('chat_id' => $chat_id, 'video' => $fp));
                apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => '@' . $fp));
                apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => $fp));
                break;

            case (strpos($text, '/no') === 0):
                $fp = new CURLFile(realpath(VIDEOS . 'no.mp4'));
                apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => $fp));
                break;

            case (strpos($text, '/pc') === 0):
                apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => VIDEOS . 'pc.mp4'));
                break;

            case (strpos($text, '/pc2') === 0):
                apiRequestFile('sendAnimation', array('chat_id' => $chat_id, 'animation' => VIDEOS . 'pc.mp4'));
                break;

            case (strpos($text, '/lol4') === 0):
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> text'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> video'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'animation' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendMessgae -> animation'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'sendVideo -> video'));
                // apiRequest('sendVideo', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));
                // apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => 'APIREQUESTJSON -> sendVideo -> video'));
                // apiRequestJson('sendVideo', array('chat_id' => $chat_id, 'video' => '@' . VIDEOS . 'no.mp4'));

                $fp = new CURLFile(realpath(VIDEOS . 'no.mp4'));

                //apiRequestFile('sendVideo', array('chat_id' => $chat_id, 'video' => $fp));
                apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => '@' . $fp));
                //apiRequestFile('sendVideoNote', array('chat_id' => $chat_id, 'video' => $fp));
                break;
            case (strpos($text, '/kkk') === 0):
                apiRequest('sendAnimation', array('chat_id' => $chat_id, 'animation' => '@' . VIDEOS . 'no.mp4'));
                break;
            case (strpos($text, '/id') === 0):
                $id = explode(' ', $text)[1];
                //if (isset($id) && !empty($id)) {
                $info = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $user_id));
                error_log("--------   ID   ----------" . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr1 " . implode(',', $info[0]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr2 " . implode(',', $info[1]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr3 " . implode(',', $info['member']) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr4 " . implode(',', $info) . PHP_EOL, 3, "../logs/bot.log");
                error_log("--------  FIM ID  --------" . PHP_EOL, 3, "../logs/bot.log");
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "Checando o ID: {$id}...\n\nDigite /logs para mostrar."));
                //}
                break;
            case (strpos($text, '/iduser') === 0):
                $id = explode(' ', $text)[1];
                //if (isset($id) && !empty($id)) {
                $info = apiRequest("getChatMember", array('chat_id' => $chat_id, "user_id" => $id));
                error_log("--------   ID   ----------" . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr1 " . implode(',', $info[0]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr2 " . implode(',', $info[1]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr3 " . implode(',', $info['member']) . PHP_EOL, 3, "../logs/bot.log");
                error_log("--------  FIM ID  --------" . PHP_EOL, 3, "../logs/bot.log");
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "Checando o ID: {$id}...\n\nDigite /logs para mostrar."));
                //}
                break;
            case (strpos($text, '/idchat') === 0):
                $id = explode(' ', $text)[1];
                $info = apiRequest("getChat", array('chat_id' => $id));
                error_log("--------   ID   ----------" . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr1 " . implode(',', $info[0]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr2 " . implode(',', $info[1]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr3 " . implode(',', $info['member']) . PHP_EOL, 3, "../logs/bot.log");
                error_log("--------  FIM ID  --------" . PHP_EOL, 3, "../logs/bot.log");
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "Checando o ID: {$id}...\n\nDigite /logs para mostrar."));
                break;

            case (strpos($text, '/idorig') === 0):
                $id = explode(' ', $text)[1];
                $info = apiRequest("getChatMember", array('chat_id' => $id));
                error_log("--------   ID   ----------" . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr01 " . $info[0] . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr02 " . $info[1] . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr03 " . $info['member'] . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr2 " . implode(',', $info[1]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr3 " . implode(',', $info['member']) . PHP_EOL, 3, "../logs/bot.log");
                error_log("Extr4 " . implode(',', $info[0]) . PHP_EOL, 3, "../logs/bot.log");
                error_log("--------  FIM ID  --------" . PHP_EOL, 3, "../logs/bot.log");
                apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "Checando o ID: {$id}...\n\nDigite /logs para mostrar."));
                break;
            default:
                if (filterMessage($text)) {
                    apiRequest("deleteMessage", array('chat_id' => $chat_id, "message_id" => $message_id));
                    apiRequest("sendChatAction", array('chat_id' => $chat_id, 'action' => 'typing'));
                    apiRequest('sendMessage', array('chat_id' => $chat_id, 'text' => "\u{1F621} @{$username}"));
                }
                break;
        }
    }

    if (isset($update['callback_query']) && isset($callback_id)) {
        apiRequest("answerCallbackQuery", array('callback_id' => $callback_id, 'text' => 'Uhullll {$callback_user}', 'show_alert' => true));
    }
}

$file_db = null;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update)
    exit;

if (isset($update['callback_query'])) {
    $callback_id = $update['callback_query']['id'];
    $callback_user = $update['callback_query']['from']['id'];
    $callback_content = $update['callback_query']['data'];
}

if (isset($update["message"]))
    processMessage($update["message"]);
