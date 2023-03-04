<?php
require_once './bot_functions.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


define("bot_tkn", "" );
$chat_id = "";

$get_hook = file_get_contents('php://input'); // зазисываем полученый webhook
$data = json_decode($get_hook, true); // переводим данные из формата json в массив php. (true = получим именно в виде массива, а не объекта)

write_log($data, true); // Записываем последний полученный webhook



// ЗАХВАТЫВАЕМ СООБЩЕНИЕ В ПЕРЕМЕННУЮ ДЛЯ ДАЛЬНЕЙШЕГО СРАВНЕНИЯ
$received_message = (isset($data['message']['text'])) ? mb_strtolower($data['message']['text']) : ""; // ловим слово в переменную в чате

// ----------------------------------- ПОДГОТАВЛИВАЕМ ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ С НИМИ ---------------------------- //
$who_helped_telegram_id = ""; 
$who_helped_first_name = "";
$who_helped_last_name = "";
$who_helped_unique_name = "";

$whose_question_id = "";
$whose_question_first_name = "";
$whose_question_last_name = "";

$who_helped_full_name = "";
$whose_question_full_name = "";

if(isset($data)) {
    $who_helped_telegram_id = (!empty($data['message']['reply_to_message']['from']['id'])) ? $data['message']['reply_to_message']['from']['id'] : ""; // записываем значение только при его наличии, чтобы не сыпались ошибки.
    $who_helped_first_name = (!empty($data['message']['reply_to_message']['from']['first_name'])) ? $data['message']['reply_to_message']['from']['first_name'] : "";
    $who_helped_last_name = (!empty($data['message']['reply_to_message']['from']['last_name'])) ? $data['message']['reply_to_message']['from']['last_name'] : "";
    $who_helped_unique_name = (!empty($data['message']['reply_to_message']['from']['username'])) ? $data['message']['reply_to_message']['from']['username'] : "";
    
    $whose_question_id = $data['message']['from']['id'];
    $whose_question_first_name = $data['message']['from']['first_name'];
    $whose_question_last_name = (!empty($data['message']['from']['last_name'])) ? $data['message']['from']['last_name'] : "";
    
    $who_helped_full_name = $who_helped_first_name . " $who_helped_last_name";
    $whose_question_full_name = $whose_question_first_name . " $whose_question_last_name";
}

// -------------------------- УВЕЛИЧИВАЕМ КАРМУ (СОЗДАНИЕ НОВОГО ПОЛЬЗОВАТЕЛЯ) ПРИ REPLAY "+" -------------------------- //

if(are_rules_ok($data)) { // если есть replay и первый + и это не бот и не один и тот же пользователь, то ...
    
    if(query_interval_validation(6)) { // защита от спама
        log_last_message_time_with_interval($data, 6); // защита от спама
    
        if(is_user_exists($who_helped_telegram_id)) { // пользователь существует в базе?
    
            level_up($who_helped_telegram_id); // ПОВЫШАЕМ СТАТИСТИКУ НА +1
    
            $who_helped_score = get_score_by_id($who_helped_telegram_id); // записываем текущее значение счета пользователя
            $whose_question_score = get_score_by_id($whose_question_id);
            
            $bot_answer ="$who_helped_full_name($who_helped_score) + 1 к карме, от $whose_question_full_name ($whose_question_score)"; // ответ бота
    
            
            $array_querry = [ // массив для ответа бота в чат телеграм
                'chat_id' => $chat_id,
                'text' => $bot_answer,
                'parse_mode' => "html",
            ];
    
            bot_says($array_querry); // бот говорит в чат телеграм, по данным из массива $array_querry
    
        } else { 
            add_new_user($who_helped_telegram_id, $who_helped_first_name, $who_helped_last_name, $who_helped_unique_name); // добавляем нового пользователя
            level_up($who_helped_telegram_id); // повышаем счёт на +1 пользователю
    
            $who_helped_score = get_score_by_unique_name($who_helped_telegram_id); // записываем текущее значение счета пользователя
            $whose_question_score = get_score_by_id($whose_question_id);
            
            $bot_answer ="$who_helped_full_name($who_helped_score) + 1 к карме, от $whose_question_full_name ($whose_question_score)"; // ответ бота
    
            $array_querry = [ // массив для ответа бота в чат телеграм
                'chat_id' => $chat_id,
                'text' => $bot_answer,
                'parse_mode' => "html",
            ];
    
            bot_says($array_querry); // бот говорит в чат телеграм, по данным из массива $array_querry
        }
        
    bot_delete_message($data, bot_tkn, 5);
    }
}

// ----------------------------- ОТВЕТНЫЕ РЕАКЦИИ БОТА НА СООБЩЕНИЯ (КОМАНДЫ) В ЧАТЕ ---------------------------- //

// ВЫВОДИМ ИНФОРМАЦИЮ О ДОСТУПНЫХ КОМАНДАХ
if($received_message == "/help") {
    
    if(query_interval_validation(8)) { // защита от спама
        log_last_message_time_with_interval($data, 8);     // защита от спама
    
        $bot_answer = "Список команд: \n`+` - повышайте рейтинги друг друга. Если вам помогли, то в replay сообщении первым символом поставьте + \n\n/stats - top 3 активных участников \n/stats @уникальное_имя_пользователя - статистика пользователя";
    
        $array_querry = [
            'chat_id' => $chat_id,
            'text' => $bot_answer,
            'parse_mode' => "html",
        ];
    
        bot_says($array_querry);
        bot_delete_message($data, bot_tkn, 7);
    }
}

// ВЫВОДИМ СТАТИСТИКУ КОНКРЕТНОГО ПОЛЬЗОВАТЕЛЯ
if(substr($received_message, 0, 8) == "/stats @") { // ЕСЛИ STATS @ ИМЯ ПОЛЬЗОВАТЕЛЯ

    if(query_interval_validation(5)) {
        log_last_message_time_with_interval($data, 5);
        
        $unique_name = "";
        preg_match('/@(\S+)/', $received_message, $unique_name); // /- начало выражения, @ - начинается на @, (\S+) - захват символов которые не пробел, \s - символ проблема, / - конец вырежения. 
        
        $unique_name = ltrim($unique_name[0], '@');
        
        $score = get_score_by_unique_name($unique_name);
    
        $bot_answer = "$unique_name ($score)";
        $array_querry = [
            'chat_id' => $chat_id,
            'text' => $bot_answer,
            'parse_mode' => "html",
        ];
    
        bot_says($array_querry);
        bot_delete_message($data, bot_tkn, 4);
    }
}

// ВЫВОДИМ СТАТИСТИКУ САМЫХ АКТИВНЫХ УЧАСТНИКОВ 1 2 3 МЕСТА
if($received_message == "/stats") { 
    
    if(query_interval_validation(5)) {
        log_last_message_time_with_interval($data, 5);
        
        $sorted_users = get_all_sorted_users_by_score();
        
        (!empty($data['message']['reply_to_message']['from']['id'])) ? $data['message']['reply_to_message']['from']['id'] : "";
        
        $first_unique_name = (!empty($sorted_users["0"])) ? $sorted_users["0"]["telegram_user_unique_name"] : "";
        $first_name = (!empty($sorted_users["0"])) ? $sorted_users['0']['telegram_user_first_name'] ." ". $sorted_users['0']['telegram_user_last_name'] : "";
        $first_score = (!empty($sorted_users["0"])) ? $sorted_users['0']['score'] : "";
        
        $second_unique_name = (!empty($sorted_users["1"])) ? $sorted_users["1"]["telegram_user_unique_name"] : "";
        $second_name = (!empty($sorted_users["1"])) ? $sorted_users['1']['telegram_user_first_name'] ." ". $sorted_users['1']['telegram_user_last_name'] : "";
        $second_score = (!empty($sorted_users["1"])) ? $sorted_users["1"]["score"] : "";
        
        $third_unique_name = (!empty($sorted_users["2"])) ? $sorted_users["2"]["telegram_user_unique_name"] : "";
        $third_name = (!empty($sorted_users["2"])) ? $sorted_users['2']['telegram_user_first_name'] ." ". $sorted_users['2']['telegram_user_last_name'] : "";
        $third_score = (!empty($sorted_users["2"])) ? $sorted_users["2"]["score"] : "";
     
        $bot_answer = "1. $first_name ($first_unique_name),  score: $first_score \n2. $second_name ($second_unique_name),  score: $second_score \n3. $third_name ($third_unique_name),  score: $third_score ";
        
        $array_querry = [
            'chat_id' => $chat_id,
            'text' => $bot_answer,
            'parse_mode' => "html",
        ];
    
        bot_says($array_querry); 
        bot_delete_message($data, bot_tkn, 4);
    }
}


// ------- СРАВНИВАЕМ ЛИЧНЫЕ ДАННЫЕ ПОЛЬЗОВАТЕЛЯ ИЗ БАЗЫ, С ТЕМИ КОТОРЫЕ ПРИШЛИ ПО WEBHOOK, И МЕНЯЕМ В СЛУЧАЕ НЕРАВЕНСТВ -------------- //

if(is_user_exists($who_helped_telegram_id)) {
    $user_db_data = get_user_by_telegram_id($who_helped_telegram_id);
    
    $user_db_unique_name = $user_db_data['telegram_user_unique_name'];
    $user_db_user_first_name = $user_db_data['telegram_user_first_name'];
    $user_db_user_last_name = $user_db_data['telegram_user_last_name'];
    
    if($user_db_unique_name !== $who_helped_unique_name) {
        update_telegram_user_unique_name($who_helped_telegram_id, $who_helped_unique_name);
    }
    if($user_db_user_first_name !== $who_helped_first_name) {
        update_telegram_user_first_name($who_helped_telegram_id, $who_helped_first_name);
    }
    if($user_db_user_last_name !== $who_helped_last_name) {
        update_telegram_user_last_name($who_helped_telegram_id, $who_helped_last_name);
    }
}


?>