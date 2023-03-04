<?php

$setting = [
    'host' => '',
    'db' => '',
    'user' => '',
    'pass' => '',
];
    
function connecting() {
    global $setting;
    $pdo = new PDO("mysql:host=". $setting['host'] ."; dbname=". $setting['db'], $setting['user'], $setting['pass']);
    
    return $pdo;
}


function set_webhook($web_site_url, $token) {
    $ch = curl_init('https://api.telegram.org/bot'. $token .'/setWebhook?url='. $web_site_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);

    echo $result;
}

function delete_webhook($token) {
    $url = 'https://api.telegram.org/bot'. $token. '/deleteWebhook';
    file_get_contents($url);
    echo "done";
}

function write_log($string, $clear = false) {
    $log_file_name = "./message.txt";
    $now = date("Y-m-d H:i:s");
    
    if($clear == false) {
        $now;
        file_put_contents($log_file_name, $now ." ". print_r($string, true) . "\r\n", FILE_APPEND);
    } else {
        file_put_contents($log_file_name, '');
        $now;
        file_put_contents($log_file_name, $now ." ". print_r($string, true) . "\r\n", FILE_APPEND);
    }
}

function log_last_message_time_with_interval($data = false, $interval = 0) { // защита от спама
    if(isset($data)) {
        $log_file_path = "./time_log.txt";
        $time_now = strtotime("now"); // unix время
        
        if(file_exists($log_file_path)) {
            $file_content = file($log_file_path);
            $time_created_logfile = $file_content[0];

            $delta = $time_now - $time_created_logfile;
            
            if($delta > $interval){
                file_put_contents($log_file_path, '');
                file_put_contents($log_file_path, $time_now, FILE_APPEND);
            }
            
            return false;
        } else {
            file_put_contents($log_file_path, '');
            file_put_contents($log_file_path, $time_now, FILE_APPEND);
        }
    }
}



function query_interval_validation($interval = 0) { // защита от спама
    $file_path = './time_log.txt';
    $time_now = strtotime("now"); // unix время
    $last_query_time = "";
    
    if(file_exists($file_path)) {
        $file_content = file($file_path);
        $last_query_time = $file_content[0];
        $delta = $time_now - $last_query_time;
        
        if($delta > $interval){
            return true;
        }
        
        return false;
    } else {
        file_put_contents($file_path, '');
        file_put_contents($file_path, $time_now, FILE_APPEND);
    }
}

function bot_says($getQuerry) {
    $ch = curl_init("https://api.telegram.org/bot". bot_tkn ."/sendMessage?". http_build_query($getQuerry));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}


function bot_delete_message($data, $token, $interval = 1) {
    if($data) {
    $chat_id = $data['message']['chat']['id'];
    $bot_last_msg_id = $data['message']['message_id'] + 1;
    sleep($interval);
    
    $url = "https://api.telegram.org/bot". $token ."/deleteMessage?chat_id=". $chat_id ."&message_id=". $bot_last_msg_id;
    file_get_contents($url);
    }
    return false;
}


function is_user_exists($telegram_user_id) {
    $pdo = connecting();
    $sql = "SELECT * FROM data_table WHERE telegram_user_id=:tl_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['tl_id' => $telegram_user_id]);
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if(!empty($result)) {
        return true;
    }

    return false;
}

function is_unique_name_exist($unique_name) {
    $pdo = connecting();
    $sql = "SELECT * FROM data_table WHERE telegram_user_unique_name=:value";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $unique_name]);
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if(!empty($result)) {
        return true;
    }

    return false;
}


function add_new_user($user_id, $first_name, $last_name, $unique_name) {
    $pdo = connecting();
    $sql = "INSERT INTO data_table (`telegram_user_id`, `telegram_user_first_name`, `telegram_user_last_name`, `telegram_user_unique_name`) VALUES (:value1, :value2, :value3, :value4)";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value1' => $user_id, 'value2' => $first_name, 'value3' => $last_name, 'value4' => $unique_name]);
}

function level_up($user_id) {
    $pdo = connecting();

    $sql = 'SELECT score FROM data_table WHERE telegram_user_id=:value';
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $user_id]);
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    $up_score= $result['score'] + 1;

    $sql = "UPDATE data_table SET score=:value WHERE telegram_user_id=$user_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $up_score]);
}

function get_score_by_id($user_id) {
    $score = "";
    
    if(is_user_exists($user_id)) {
       $pdo = connecting();

        $sql = 'SELECT score FROM data_table WHERE telegram_user_id=:value';
        $statement = $pdo->prepare($sql);
        $statement->execute(['value' => $user_id]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        $score = $result['score'];
    } else {
        $score = 0;
    }
    
    return $score;
}

function get_score_by_unique_name($unique_name) {
    $score = "";
    
    if(is_unique_name_exist($unique_name)) {
       $pdo = connecting();

        $sql = 'SELECT score FROM data_table WHERE telegram_user_unique_name=:value';
        $statement = $pdo->prepare($sql);
        $statement->execute(['value' => $unique_name]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        $score = $result['score'];
    } else {
        $score = 0;
    }
    
    return $score;
}

function get_all_sorted_users_by_score() {
    $pdo = connecting();
    
    $sql = "SELECT * FROM data_table";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    usort($result, function($a, $b) {
    return $b['score'] <=> $a['score'];
    }); 
    
    return $result;
}


function get_user_by_telegram_id($telegram_user_id) {
    $pdo = connecting();
    
    $sql = "SELECT * FROM data_table WHERE telegram_user_id=:tl_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['tl_id' => $telegram_user_id]);
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    
    return $result;
}

function update_telegram_user_unique_name($user_id, $value){
    $pdo = connecting();
    
    $sql = "UPDATE data_table SET telegram_user_unique_name=:value WHERE telegram_user_id=$user_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $value]);
};
function update_telegram_user_first_name($user_id, $value){
    $pdo = connecting();
    
    $sql = "UPDATE data_table SET telegram_user_first_name=:value WHERE telegram_user_id=$user_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $value]);
};
function update_telegram_user_last_name($user_id, $value){
    $pdo = connecting();
    
    $sql = "UPDATE data_table SET telegram_user_last_name=:value WHERE telegram_user_id=$user_id";
    $statement = $pdo->prepare($sql);
    $statement->execute(['value' => $value]);
};



function are_rules_ok($data) {
    if(isset($data['message']['reply_to_message']['text']) && substr($data['message']['text'], 0, 1) == "+" && $data['message']['reply_to_message']['from']['id'] !== $data['message']['from']['id'] && $data['message']['reply_to_message']['from']['is_bot'] == NULL) {
        return true;
    }
    return false;
}


?>