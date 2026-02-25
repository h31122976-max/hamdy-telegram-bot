<?php

// --- [1] الإعدادات الأساسية (بياناتك ثابتة) ---
$botToken = "8779972033:AAG9XpGSlgTYyjLkjsx4_tW6RjbV8B-UkUI";
$apiURL = "https://api.telegram.org/bot$botToken/";
$geminiKey = "AIzaSyAnpVnpNcsd2ABNyd9JPbstEa8sowP40Uo";
$adminID = "7017497200"; 
$dailySecretCode = "رمضان_كريم";

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if(isset($update["message"]) || isset($update["callback_query"])){

    // تحديد البيانات بناءً على نوع التفاعل
    if(isset($update["callback_query"])){
        $callback_id = $update["callback_query"]["id"];
        $chat_id = $update["callback_query"]["message"]["chat"]["id"];
        $user_id = $update["callback_query"]["from"]["id"];
        $user_name = $update["callback_query"]["from"]["first_name"];
        $text = $update["callback_query"]["data"];
        
        // [3️⃣] استجابة سريعة للأزرار
        file_get_contents($apiURL."answerCallbackQuery?callback_query_id=".$callback_id);
    } else {
        $chat_id = $update["message"]["chat"]["id"];
        $user_id = $update["message"]["from"]["id"];
        $user_name = $update["message"]["from"]["first_name"];
        $text = $update["message"]["text"];
    }

    // --- [2] إدارة قاعدة البيانات والدوال ---
    if(!file_exists("users.json")){ file_put_contents("users.json", json_encode([])); }
    $users = json_decode(file_get_contents("users.json"), true);

    function saveAll($data){ file_put_contents("users.json", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }

    // [6️⃣] تحسين نظام Level (XP صحيح)
    function givePoints(&$users, $user_id, $amount){
        $users[$user_id]["points"] += $amount;
        $needed = $users[$user_id]["level"] * 200;
        if($users[$user_id]["points"] >= $needed){
            $users[$user_id]["level"] += 1;
        }
        saveAll($users);
    }

    function getRank($points) {
        if ($points < 500) return "🛡️ مبتدئ";
        if ($points < 1500) return "✨ مجتهد";
        if ($points < 5000) return "📖 قارئ";
        return "👑 خادم القرآن";
    }

    function sendMessage($chat_id, $text, $keyboard = null){
        global $apiURL;
        $url = $apiURL."sendMessage?chat_id=$chat_id&text=".urlencode($text)."&parse_mode=Markdown";
        if($keyboard) $url .= "&reply_markup=".json_encode($keyboard);
        return file_get_contents($url);
    }

    // [1️⃣] حماية سبام للـ AI
    function antiSpam(&$users, $user_id, $seconds=10){
        if(!isset($users[$user_id]["last_ai"])) $users[$user_id]["last_ai"] = 0;
        if(time() - $users[$user_id]["last_ai"] < $seconds){
            return false;
        }
        $users[$user_id]["last_ai"] = time();
        saveAll($users);
        return true;
    }

    // تسجيل المستخدم الجديد
    if(!isset($users[$user_id])){
        $users[$user_id] = [
            "name" => $user_name,
            "points" => 10,
            "level" => 1,
            "last_daily" => 0,
            "last_spin" => 0,
            "khatma" => 0,
            "last_ai" => 0
        ];
        saveAll($users);
    }

    // --- [3] نظام القوائم والأوامر ---

    // القائمة الرئيسية
    if(strpos($text, "/start") === 0){
        $msg = "👑 *أهلاً بك في بوت حمدي أحمد المطور* 👑\n\nصلي على النبي ﷺ واستكشف خدماتنا:";
        $keyboard = ['inline_keyboard' => [
            [['text' => '🕋 قسم العبادة والرمضانيات', 'callback_data' => 'religious_section']],
            [['text' => '🤖 شات AI الذكي', 'callback_data' => 'ai_info'], ['text' => '💰 الجوائز والمهام', 'callback_data' => 'points_menu']],
            [['text' => '🏆 المتصدرين', 'callback_data' => '/top'], ['text' => '🏅 ملفي الشخصي', 'callback_data' => 'profile']]
        ]];
        if($user_id == $adminID) $keyboard['inline_keyboard'][] = [['text' => '⚙️ لوحة التحكم', 'callback_data' => 'admin_panel']];
        sendMessage($chat_id, $msg, $keyboard);
    }

    // [1️⃣ & 2️⃣] قسم الـ AI مع الحماية ومعالجة الأخطاء
    elseif(strpos($text, "/ai") === 0){
        if(!antiSpam($users, $user_id, 10)){
            sendMessage($chat_id, "⏳ استنى 10 ثواني قبل ما تستخدم الذكاء الاصطناعي تاني.");
            exit;
        }

        $prompt = trim(str_replace("/ai", "", $text));
        if(!$prompt){ sendMessage($chat_id, "🤖 اسأل سؤالك بعد كلمة /ai"); exit; }

        $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
        $options = ["http" => [
            "header" => "Content-type: application/json\r\n",
            "method" => "POST",
            "content" => json_encode($data),
            "timeout" => 15
        ]];

        $result = @file_get_contents("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=".$geminiKey, false, stream_context_create($options));

        if(!$result){
            sendMessage($chat_id, "⚠️ الذكاء الاصطناعي غير متاح حالياً.");
            exit;
        }

        $res = json_decode($result, true);
        if(isset($res["candidates"][0]["content"]["parts"][0]["text"])){
            sendMessage($chat_id, $res["candidates"][0]["content"]["parts"][0]["text"]);
        } else {
            sendMessage($chat_id, "⚠️ حصل خطأ في معالجة الرد، حاول تاني.");
        }
    }

    // [4️⃣] نظام هدية يومية حقيقي
    elseif($text == "daily_gift" || $text == "/daily"){
        if(time() - $users[$user_id]["last_daily"] < 86400){
            $diff = 86400 - (time() - $users[$user_id]["last_daily"]);
            sendMessage($chat_id, "🎁 استلمت هديتك بالفعل. ارجع بعد: " . gmdate("H:i:s", $diff));
        } else {
            $reward = rand(10, 50);
            givePoints($users, $user_id, $reward);
            $users[$user_id]["last_daily"] = time();
            saveAll($users);
            sendMessage($chat_id, "🎁 مبروك! حصلت على $reward نقطة هدية يومية.");
        }
    }

    // [5️⃣] عجلة الحظ
    elseif($text == "spin_wheel" || $text == "/spin"){
        if(time() - $users[$user_id]["last_spin"] < 86400){
            sendMessage($chat_id, "🎡 جرب حظك بكرة يا زميلي!");
        } else {
            $win = rand(1, 50);
            givePoints($users, $user_id, $win);
            $users[$user_id]["last_spin"] = time();
            saveAll($users);
            sendMessage($chat_id, "🎡 العجلة وقفت على $win نقطة! مبروك.");
        }
    }

    // قسم العبادة
    elseif($text == "religious_section"){
        $p = $users[$user_id]["points"];
        $msg = "🌙 *قسم العبادة والرمضانيات* 🌙\n👤 رتبتك: *".getRank($p)."*\n\nاستخدم الخدمات الإيمانية:";
        $keyboard = ['inline_keyboard' => [
            [['text' => '📖 الختمة اليومية', 'callback_data' => 'khatma_menu'], ['text' => '📋 مهام العبادة', 'callback_data' => 'daily_tasks']],
            [['text' => '📻 إذاعة القرآن', 'url' => 'https://www.mp3quran.net/ar/radio'], ['text' => '🕋 القبلة', 'callback_data' => 'qibla_find']],
            [['text' => '🔙 رجوع', 'callback_data' => '/start']]
        ]];
        sendMessage($chat_id, $msg, $keyboard);
    }

    // البروفايل
    elseif($text == "profile" || $text == "/points"){
        $p = $users[$user_id]["points"];
        $msg = "👤 *ملفك الشخصي:*\n\n🏅 النقاط: $p\n🎖 الرتبة: ".getRank($p)."\n🎖 المستوى: ".$users[$user_id]["level"];
        sendMessage($chat_id, $msg);
    }

    // قائمة الجوائز
    elseif($text == "points_menu"){
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎁 هدية يومية', 'callback_data' => 'daily_gift'], ['text' => '🎡 عجلة الحظ', 'callback_data' => 'spin_wheel']],
            [['text' => '🔙 رجوع', 'callback_data' => '/start']]
        ]];
        sendMessage($chat_id, "💰 *قسم الجوائز والمهام*:", $keyboard);
    }

    // لوحة التحكم للمدير
    elseif($text == "admin_panel" && $user_id == $adminID){
        $count = count($users);
        sendMessage($chat_id, "⚙️ *لوحة تحكم المدير*\n\n👥 عدد المستخدمين: $count", ['inline_keyboard' => [[['text' => '📢 إذاعة رسالة', 'callback_data' => 'brd']], [['text' => '🔙 رجوع', 'callback_data' => '/start']]]]);
    }
}
?>
