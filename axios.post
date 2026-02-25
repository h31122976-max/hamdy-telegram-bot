import express from "express";
import TelegramBot from "node-telegram-bot-api";
import axios from "axios";
import fs from "fs";

/* ==========================================================
   🔐 [1] الإعدادات الأساسية (بياناتك)
   ========================================================== */
const token = "8779972033:AAG9XpGSlgTYyjLkjsx4_tW6RjbV8B-UkUI";
const GEMINI_KEY = "AIzaSyAnpVnpNcsd2ABNyd9JPbstEa8sowP40Uo";
const ADMIN_ID = "7017497200";

const bot = new TelegramBot(token, { polling: true });
const app = express();

/* ==========================================================
   🗄️ [2] نظام قاعدة البيانات المطور
   ========================================================== */
const DB_PATH = "./mega_database.json";
const db = {
  get: () => JSON.parse(fs.readFileSync(DB_PATH)),
  save: (data) => fs.writeFileSync(DB_PATH, JSON.stringify(data, null, 2))
};
if (!fs.existsSync(DB_PATH)) db.save({});

/* ==========================================================
   📜 [3] مكتبة المحتوى (للأذكار والبيانات)
   ========================================================== */
const AZKAR = {
    sabah: [
        "أَصْبَحْنَا وَأَصْبَحَ المُلْكُ لِلَّهِ وَالحَمْدُ لِلَّهِ (مرة واحدة)",
        "اللّهُ لاَ إِلَـهَ إِلاَّ هُوَ الْحَيُّ الْقَيُّومُ (آية الكرسي)",
        "اللهم بك أصبحنا وبك أمسينا (مرة واحدة)",
        "سبحان الله وبحمده (100 مرة)"
    ],
    masaa: [
        "أَمْسَيْنَا وَأَمْسَى المُلْكُ لِلَّهِ (مرة واحدة)",
        "اللهم بك أمسينا وبك أصبحنا (مرة واحدة)",
        "أعوذ بكلمات الله التامات من شر ما خلق (3 مرات)"
    ]
};

/* ==========================================================
   🛠️ [4] دوال مساعدة (Helper Functions)
   ========================================================== */
const addXP = (uid, amount) => {
    let users = db.get();
    if (!users[uid]) return;
    users[uid].xp = (users[uid].xp || 0) + amount;
    users[uid].points = (users[uid].points || 0) + Math.floor(amount / 2);
    const nextLevel = (users[uid].level || 1) * 1000;
    if (users[uid].xp >= nextLevel) {
        users[uid].level = (users[uid].level || 1) + 1;
        bot.sendMessage(uid, `🎉 تهانينا! صعدت للمستوى ${users[uid].level}`);
    }
    db.save(users);
};

/* ==========================================================
   👑 [5] لوحة تحكم الأدمن (Admin Dashboard Logic)
   ========================================================== */
const sendAdminPanel = (cid) => {
    const users = db.get();
    const totalUsers = Object.keys(users).length;
    let totalPoints = 0;
    Object.values(users).forEach(u => totalPoints += (u.points || 0));

    const panelMsg = `⚙️ *لوحة تحكم الإمبراطور (الأدمن)*\n\n` +
        `📊 إحصائيات سريعة:\n` +
        `• عدد المشتركين: ${totalUsers}\n` +
        `• إجمالي نقاط البوت: ${totalPoints}\n` +
        `• حالة السيرفر: متصل ✅\n\n` +
        `استخدم الأزرار لإدارة البوت:`;

    bot.sendMessage(cid, panelMsg, {
        parse_mode: "Markdown",
        reply_markup: {
            inline_keyboard: [
                [{ text: "📢 إرسال إذاعة (للجميع)", callback_data: "admin_broadcast" }],
                [{ text: "🚫 حظر مستخدم", callback_data: "admin_ban" }, { text: "🔓 فك حظر", callback_data: "admin_unban" }],
                [{ text: "💰 إضافة نقاط", callback_data: "admin_add_pts" }, { text: "📥 سحب الداتا", callback_data: "admin_get_db" }],
                [{ text: "🔙 العودة للقائمة الرئيسية", callback_data: "main_menu" }]
            ]
        }
    });
};

/* ==========================================================
   📩 [6] معالج الرسائل والأوامر
   ========================================================== */
bot.on("message", async (msg) => {
    const cid = msg.chat.id;
    const uid = msg.from.id.toString();
    const text = msg.text;
    if (!text) return;

    let users = db.get();
    if (!users[uid]) {
        users[uid] = { name: msg.from.first_name, points: 10, xp: 0, level: 1, is_banned: false };
        db.save(users);
    }

    if (users[uid].is_banned && uid !== ADMIN_ID) return bot.sendMessage(cid, "❌ أنت محظور من استخدام البوت.");

    // أوامر خاصة بالمدير فقط
    if (uid === ADMIN_ID) {
        if (text === "/admin" || text === "لوحة التحكم") {
            return sendAdminPanel(cid);
        }
        
        // نظام الإذاعة المباشر
        if (text.startsWith("اذاعة ")) {
            const announcement = text.replace("اذاعة ", "");
            let sent = 0;
            for (let id in users) {
                try {
                    await bot.sendMessage(id, `📢 *إشعار من الإدارة:*\n\n${announcement}`, { parse_mode: "Markdown" });
                    sent++;
                } catch (e) {}
            }
            return bot.sendMessage(cid, `✅ تم إرسال الإذاعة لـ ${sent} مستخدم.`);
        }
    }

    // الأوامر العامة
    if (text === "/start") {
        const u = users[uid];
        bot.sendMessage(cid, `🌟 أهلاً بك يا ${u.name}\nنقاطك: ${u.points}\nمستواك: ${u.level}`, {
            reply_markup: {
                inline_keyboard: [
                    [{ text: "🕋 قسم العبادة", callback_data: "rel_menu" }],
                    uid === ADMIN_ID ? [{ text: "⚙️ لوحة الأدمن", callback_data: "admin_panel" }] : []
                ]
            }
        });
    }
});

/* ==========================================================
   🔘 [7] معالج أزرار لوحة التحكم
   ========================================================== */
bot.on("callback_query", async (q) => {
    const cid = q.message.chat.id;
    const uid = q.from.id.toString();
    const data = q.data;
    let users = db.get();

    // التحقق من صلاحية الأدمن
    if (data.startsWith("admin_") && uid !== ADMIN_ID) {
        return bot.answerCallbackQuery(q.id, { text: "⚠️ عذراً، هذا القسم للمدير فقط!", show_alert: true });
    }

    bot.answerCallbackQuery(q.id);

    if (data === "admin_panel") {
        sendAdminPanel(cid);
    }

    if (data === "admin_broadcast") {
        bot.sendMessage(cid, "📝 أرسل رسالة الإذاعة مسبوقة بكلمة (اذاعة )، مثال:\nاذاعة السلام عليكم");
    }

    if (data === "admin_get_db") {
        bot.sendDocument(cid, DB_PATH, { caption: "📂 نسخة من قاعدة البيانات الحالية." });
    }

    if (data === "rel_menu") {
        bot.sendMessage(cid, "🕋 *قسم العبادة:*", {
            reply_markup: {
                inline_keyboard: [
                    [{ text: "☀️ أذكار الصباح", callback_data: "show_sabah" }],
                    [{ text: "🌙 أذكار المساء", callback_data: "show_masaa" }]
                ]
            }
        });
    }

    if (data === "show_sabah") {
        bot.sendMessage(cid, `☀️ *أذكار الصباح:*\n\n${AZKAR.sabah.join("\n")}`);
        addXP(uid, 10);
    }
});

/* ==========================================================
   🌐 [8] تشغيل السيرفر
   ========================================================== */
app.get("/", (req, res) => res.send("Bot is Running..."));
app.listen(3000, () => console.log("🚀 Server Online and Admin Panel Ready"));
