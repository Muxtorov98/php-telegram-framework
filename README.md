# 🤖 Telegram Shop Bot (PHP / Laravel Capsule / Docker)

Bu loyiha — Telegram orqali mahsulotlarni ko‘rish, savatga qo‘shish va buyurtma berish imkonini beruvchi **modulli Telegram Shop Bot** tizimi.

---

## ⚙️ Texnologiyalar

- **PHP 8.2+**
- **Telegram Bot SDK** (`irazasyed/telegram-bot-sdk`)
- **Illuminate Database (Capsule ORM)** – Laravel ORM
- **PostgreSQL**
- **Docker & Docker Compose**
- **Long Polling (Webhook’siz)**

---

## 📁 Loyihaning tuzilishi

```
app/
 ├── Console/
 │   ├── Commands/
 │   │   ├── MigrateCommand.php
 │   │   ├── SetBotCommands.php
 │   │   └── ResetBotCommands.php
 │   └── ConsoleKernel.php
 ├── Core/
 │   ├── Bot.php
 │   ├── Router.php
 │   ├── Polling.php
 │   ├── FormFlowManager.php
 │   ├── Attributes/
 │   │   ├── Handler.php
 │   │   ├── FormStep.php
 │   │   └── Permission.php
 │   └── Helpers/
 │       ├── SessionHelper.php
 │       └── FileHelper.php
 ├── Handlers/
 │   ├── Users/
 │   │   ├── StartHandler.php
 │   │   ├── RegisterHandler.php
 │   │   └── AuthHandler.php
 │   └── Shop/
 │       ├── ProductViewHandler.php
 │       ├── CartHandler.php
 │       └── OrderHandler.php
 ├── Services/
 │   ├── LoggerService.php
 │   ├── PaginationHelper.php
 │   ├── ProductService.php
 │   ├── OrderService.php
 │   └── CartService.php
 └── Data/
     └── Config.php
```

---

## 🚀 Ishga tushirish

### 1️⃣ Muhitni sozlash
`.env` fayl yarating (yoki `example.env` dan nusxa oling):

```env
BOT_TOKEN=YOUR_TELEGRAM_BOT_TOKEN
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=shop_bot
DB_USERNAME=postgres
DB_PASSWORD=postgres
CHAT_IDS=1234567
ADMIN_IDS=1234567,9999999
MANAGER_IDS=8888888,7777777
APP_DEBUG=true
```

---

### 2️⃣ Docker Compose bilan ishga tushirish
```bash
docker compose up -d --build
```

---

### 3️⃣ Ma’lumotlar bazasi migratsiyalari
```bash
docker compose exec telegram-bot php bin/console migrate
```

#### Migratsiya komandalar:
| Komanda | Tavsif |
|----------|--------|
| `migrate` | Barcha migratsiyalarni ishga tushiradi |
| `migrate down` | So‘nggi migratsiyalarni orqaga qaytaradi |
| `migrate refresh` | Hammasini o‘chirib, qayta yaratadi |

---

### 4️⃣ Botni ishga tushirish
```bash
docker compose exec telegram-bot php bin/console bot:run
```
Bu `Polling` orqali botni ishga tushiradi:
```
🤖 Bot started via long polling...
```

---

## 🧩 Komandalar (sidebar menyular)

Har bir rol uchun alohida menyu o‘rnatiladi:

### 👤 User menyu
```
/start
/products
/cart
/my_orders
/help
```

### 👑 Admin menyu
```
/start
/add_product
/products
/cart
/orders
/notify_users
/help
```

### 🧑‍💼 Manager menyu
```
/start
/orders
/products
/help
```

Menyularni yangilash:
```bash
docker compose exec telegram-bot php bin/console set:commands
```

Eski menyularni tozalash:
```bash
docker compose exec telegram-bot php bin/console reset:commands
```

---

## 🧱 Asosiy komponentlar

### 🔹 FormFlowManager
- Har bir `chat_id` uchun `step` va `answers` ma’lumotlarini sessiyada saqlaydi
- `context` bo‘yicha formani boshqaradi
- `reset()` orqali tozalaydi

### 🔹 Router
- `#[Handler]` attributlari orqali avtomatik handlerlarni topadi
- `#[Permission]` orqali rolga asoslangan ruxsatni tekshiradi
- Fayl turlari (photo, document va boshqalar) uchun `file download` imkonini beradi
- `callback_query` bilan JSON yoki `prefix:data` formatlarini avtomatik ajratadi

### 🔹 Polling
- Long polling loop
- Har ishga tushganda avtomatik `SetBotCommands` chaqiradi
- Har bir `update` uchun `Bot::run()` orqali `Router`ni chaqiradi

---

## 💼 Shop modullari

### 🛍 `ProductViewHandler`
- `/products` orqali mahsulotlarni chiqaradi
- Inline tugmalar: `▶️ keyingi`, `◀️ oldingi`, `🛒 savatga qo‘shish`
- PaginationHelper orqali page boshqariladi

### 🛒 `CartHandler`
- `/cart` yoki `🛒 Savatim` orqali savatni ko‘rsatadi
- Inline tugmalar: `✅ Buyurtma berish`, `🗑 Tozalash`
- Session orqali cart saqlanadi (`$_SESSION['cart'][$chatId]`)

### 📦 `OrderHandler`
- `✅ Buyurtma berish` tugmasi orqali savatdagi mahsulotlardan order yaratadi
- Orderlar `orders` jadvalida saqlanadi
- `/my_orders` komandasi orqali foydalanuvchi buyurtmalarini ko‘rishi mumkin

---

## 🧹 Avtomatik menyu yangilanishi

`Polling` ishga tushganda har safar:
```php
(new \App\Console\Commands\SetBotCommands())->handle();
```
– shu sababli bot restart qilinganda menyular yangilanadi.

---

## 🛠 CLI komandalar

| Komanda | Tavsif |
|----------|--------|
| `php bin/console migrate` | Migratsiyalarni ishga tushirish |
| `php bin/console set:commands` | Telegram menyularni o‘rnatish |
| `php bin/console reset:commands` | Eski menyularni tozalash |
| `php bin/console bot:run` | Botni ishga tushirish (polling) |

---

## 📜 Loglar
Barcha loglar:
```
/storage/logs/bot.log
```

LoggerService quyidagi holatlarni rangli formatda yozadi:
- ✅ SUCCESS
- ⚠️ WARNING
- ❌ ERROR
- ℹ️ INFO

---

## 📦 Keyingi rejalashtirilgan modullar
- 🔐 JWT asosida autentifikatsiya
- 🧾 Excel / CSV buyurtma export
- 🧠 AI product recommendation
- 📊 Admin panel (Next.js + API)

---

## 👨‍💻 Muallif
**Tulqin Muxtorov**  
GitHub: [muxtorov98](https://github.com/muxtorov98)

---

> 🚀 *“Chat orqali sotuv — bu kelajakdagi e-commerce!”*
