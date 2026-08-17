---
name: project-architecture
description: Подробное описание архитектуры, бизнес-логики и инфраструктуры проекта Fast Search Vacancies. Обзор парсера (Python), бэкенда (Laravel) и фронтенда (Vue). Помогает понять как работает проект целиком.
---

# Архитектура и Устройство Проекта: Fast Search Vacancies

Этот документ содержит максимально подробное описание архитектуры, компонентов и бизнес-логики проекта. Предназначен для быстрой загрузки контекста в другие чаты или для онбординга новых разработчиков.

---

## 1. Обзор Проекта (Overview)
**Fast Search Vacancies** — это агрегатор вакансий из Telegram-каналов. Система автоматически парсит сообщения из заданных папок в Telegram (через пользовательский аккаунт), с помощью искусственного интеллекта (Gemini) извлекает структурированную информацию о вакансиях, проверяет совпадения по ключевым словам пользователей и рассылает подходящие вакансии пользователям через Telegram-бота. Также предоставляет Telegram Mini App (WebApp) для управления подписками.

---

## 2. Инфраструктура (Docker Compose)
Проект разворачивается с помощью Docker Compose и состоит из следующих сервисов:

*   **postgres** (`postgres:15-alpine`): Основная база данных.
*   **redis** (`redis:alpine`): Используется для кэширования и защиты от спама (дедупликация отправки одних и тех же сообщений пользователям на 24 часа).
*   **rabbitmq** (`rabbitmq:3-management`): Брокер сообщений для связи между парсером и бэкендом, а также для фоновых очередей Laravel. Содержит очереди: `new_messages` (новые посты), `channel_sync` (синхронизация каналов).
*   **backend** (Laravel PHP): Основной API-сервер, обслуживающий WebApp и Telegram Webhook.
*   **worker** (Laravel CLI): Кастомный консьюмер (`php artisan app:consume-telegram`), который непрерывно слушает RabbitMQ и передает сообщения в бэкенд.
*   **worker_notifications** (Laravel Queue): Стандартный воркер Laravel (`php artisan queue:work rabbitmq`) для асинхронной отправки уведомлений пользователям в Telegram.
*   **parser** (Python/Pyrogram): Скрипт, который авторизуется как обычный пользователь Telegram, слушает каналы в заданных папках и пересылает новые посты в RabbitMQ.
*   **frontend** (Vue.js + Nginx): Telegram WebApp приложение для пользователей.

---

## 3. Парсер (Python - `parser/`)
Парсер написан на Python с использованием библиотеки `Pyrogram`. Он работает в режиме "userbot" (через `api_id` и `api_hash`).

### Логика работы:
1.  **Авторизация**: Подключается с использованием сессии v3 (`/sessions/fast_search_session_v3`).
2.  **Синхронизация папок (`sync_folder`)**:
    *   При запуске получает список диалогов.
    *   Фильтрует их по названиям папок, указанным в `.env` (например, `FOLDER_NAMES=JOB-CHAN-TES`).
    *   Собирает ID всех каналов/чатов из этих папок.
    *   Отправляет список актуальных каналов в RabbitMQ (очередь `channel_sync`), чтобы бэкенд мог обновить информацию у себя.
3.  **Сбор сообщений (`polling_task`)**:
    *   Работает в бесконечном цикле (Polling loop).
    *   Обходит все разрешенные ID каналов и проверяет последние сообщения.
    *   Имитирует прочтение (`read_chat_history`) с рандомными задержками (Stealth Mode), чтобы аккаунт не заблокировали.
    *   Отправляет новые сырые сообщения в RabbitMQ (очередь `new_messages`) в формате JSON (текст, channel_id, message_id, link).

---

## 4. Бэкенд (Laravel - `backend/`)
Бэкенд реализован на Laravel и выполняет основную бизнес-логику системы.

### 4.1 Точки входа (Routes / API)
*   **Telegram Webhook** (`POST /api/v1/telegram/webhook`):
    *   Обрабатывает входящие обновления от бота.
    *   Если получает команду `/start`, регистрирует пользователя (через `RegisterTelegramUser`) и генерирует событие `TelegramUserRegistered`.
*   **Telegram WebApp API** (`/api/v1/user/...`):
    *   Защищены middleware `tg_auth` (проверка подписи initData из Telegram WebApp).
    *   **Keywords**: Управление (CRUD) ключевыми словами. Контроль лимитов через `KeywordPolicy` (обычный юзер - 2 слова, премиум - 10).
    *   **Banned Keywords**: Управление локальными стоп-словами пользователя.
    *   **Vacancies & Channels**: Получение списка сохраненных вакансий. Доступ к каналам защищен `CheckSubscription` middleware (только для пользователей с подпиской).
    *   **Plans & Payments**: Получение тарифных планов и создание инвойсов на оплату (интеграция с Lava.top).
*   **Payment Webhooks** (`POST /api/v1/webhooks/{provider}`):
    *   Прием уведомлений об успешной оплате (например, от Lava.top).
    *   Проверка подписи (`X-Api-Key`), обновление статуса транзакции и активация подписки пользователя.

### 4.2 Обработка сообщений (Workers & Services)
1.  **Кастомный Консьюмер (`ConsumeTelegramMessages` - `app:consume-telegram`)**:
    *   Подключается к RabbitMQ напрямую через `PhpAmqpLib`.
    *   Слушает очередь `new_messages` и синхронно диспатчит джоб `ProcessIncomingMessage` для каждого поста.
    *   Слушает очередь `channel_sync` и обновляет список каналов в БД через `SyncChannels`.
2.  **Главный Сервис Обработки (`IncomingMessageService`)**:
    *   **Нормализация**: Очищает текст с помощью `App\Support\Text`.
    *   **AI Извлечение (`MessageMatchingService`)**:
        *   Отправляет текст в **Gemini API** (Google) с промптом рекрутера.
        *   AI извлекает: технологии, роль, грейд, формат работы, вилку ЗП и возвращает строгий JSON.
    *   **Матчинг (`FindMatches` / `FindMatchesByEntities`)**:
        *   Проверяет пост на наличие **Глобальных стоп-слов** (если найдено — пост бракуется для всех).
        *   Ищет пользователей, у которых в посте найдены их **ключевые слова**.
        *   Отфильтровывает тех пользователей, чьи **персональные стоп-слова** присутствуют в посте.
    *   **Сохранение**: Записывает пост и извлеченные AI данные в таблицу `ChannelMessage`.
    *   **Защита от дубликатов**: Считает MD5 хеш текста. Проверяет в Redis (`Cache::has("user_{$user->id}_msg_{$textHash}")`), не получал ли пользователь *точно такой же* текст (например, из-за кросспостинга в разных каналах) за последние 24 часа.
    *   **Архивирование и Рассылка**:
        *   Создает запись `UserMatchedPost` (для истории пользователя в WebApp).
        *   Если связка создана только что, отправляет уведомление через фоновый джоб `SendTelegramNotificationJob`.

### 4.3 Подписки и Платежи (Subscriptions & Payments)
*   **Роли и Статусы**: У пользователей есть поле `status_id` (`USER`, `MEMBER`, `ADMIN`). `MEMBER` — это пользователь с активной подпиской. Проверки доступа осуществляются через Middleware `CheckSubscription`.
*   **Интеграция Lava.top**:
    *   **Создание счета**: Пользователь запрашивает покупку плана (`Plan`). Бэкенд (через `PaymentService`) создает отложенную транзакцию (`PaymentTransaction` = `pending`) и через API Lava.top получает ссылку на оплату.
    *   **Обработка оплаты**: Lava.top присылает вебхук `payment.success`. Бэкенд валидирует секретный ключ, находит транзакцию, меняет статус на `success` и продлевает/создает активную подписку (`Subscription`) пользователю.

---

## 5. Фронтенд (Vue.js - `frontend/`)

Фронтенд представляет собой адаптивное веб-приложение, разработанное как **Telegram Mini App (TMA)** для мобильных устройств, но имеющее полноценный вид и на десктопе.

### 5.1 Технологический стек и сборка
*   **Ядро и реактивность**: Vue 3 (Composition API с синтаксисом `<script setup>`).
*   **Сборщик**: Vite. Проксирует запросы `/api` на бэкенд `http://backend` во время разработки.
*   **Стилизация**: Tailwind CSS v4. Все глобальные цвета и переменные темы вынесены в директиву `@theme` внутри [style.css](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/style.css). Цветовая схема динамически подстраивается под тему Telegram WebApp (`--color-tg-bg`, `--color-tg-text`, `--color-tg-button` и др.).
*   **UI-компоненты**: PrimeVue v4 с темой Aura (синхронизируется с системной темой темный/светлый режим).
*   **Управление стейтом**: Pinia. Хранит состояние навигации ([navigation.js](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/stores/navigation.js)) и профиль пользователя ([user.js](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/stores/user.js)).
*   **Иконки**: Lucide Icons (`lucide-vue-next`).

### 5.2 Интеграция с Telegram WebApp
*   **Аутентификация**: Axios-клиент ([client.js](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/api/client.js)) автоматически перехватывает запросы и прикрепляет к ним заголовок `X-Telegram-Init-Data` с сырой строкой `window.Telegram.WebApp.initData`.
*   **UX и Haptic Feedback**: При переключении разделов навигации, добавлении/удалении ключевых слов и переходе по ссылкам вызывается нативный виброотклик Telegram (`window.Telegram.WebApp.HapticFeedback`).
*   **Поведение окон**: На старте вызываются методы `tg.ready()` и `tg.expand()` для открытия Mini App на весь экран. Настройки цвета заголовка и фона передаются клиенту Telegram для бесшовного слияния интерфейсов.
*   **Ссылки**: Ссылки на каналы Telegram открываются через метод `tg.openTelegramLink(url)`, а внешние ссылки — через `tg.openLink(url)`.

### 5.3 Разделы и Виды (Views)
Вместо стандартного Vue Router используется кастомное переключение вьюшек в [App.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/App.vue) по стейту из Pinia:
*   **Dashboard ([DashboardView.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/views/DashboardView.vue))**: Главный экран мониторинга. Выводит состояние сканирования и базовую статистику совпадений и ключевых слов.
*   **Archive ([ArchiveView.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/views/ArchiveView.vue))**: Хронология подходящих вакансий. Поддерживает раскрытие полного текста вакансий по клику, отображение хэштегов совпавших ключевых слов, дату матчинга и подгрузку старых совпадений (Older Matches) через пагинацию.
*   **Filters / Keywords ([KeywordsView.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/views/KeywordsView.vue))**: Две колонки side-by-side: ключевые слова (положительный фильтр) и стоп-слова (отрицательный фильтр).
*   **Channels ([ChannelsView.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/views/ChannelsView.vue))**: Список источников данных. Отображает активные/приостановленные Telegram-каналы с динамическими аватарами от DiceBear API.
*   **Premium / Plans ([PlansView.vue](file:///c:/xampp/htdocs/fast-search-vacancies/frontend/src/views/PlansView.vue))**: Витрина тарифов с интеграцией оплаты. По кнопке "Purchase" запрашивает платежную ссылку у API бэкенда и открывает ее в Telegram.

---

## 6. База Данных (Основные сущности)
*   `User`: Пользователи Telegram бота (содержит `status_id` для ролей).
*   `Keyword`: Ключевые слова, на которые подписаны пользователи.
*   `BannedWord`: Стоп-слова (могут быть глобальными `is_global = true` или персональными).
*   `ChannelMessage`: Сохраненные оригинальные посты из каналов + извлеченный AI JSON.
*   `UserMatchedPost`: Связка пользователя и поста (архив вакансий пользователя).
*   `Plan`: Доступные тарифные планы (цена, длительность, ID оффера в Lava.top).
*   `Subscription`: Активные и истекшие подписки пользователей (даты `starts_at`, `ends_at`).
*   `PaymentTransaction`: Лог транзакций пополнений/оплат (отслеживание статусов `pending`, `success`).

## 7. Краткий Pipeline одной вакансии:
1. Вакансия публикуется в Telegram канале.
2. `Parser` читает её через Pyrogram и кидает в RabbitMQ.
3. `Worker` (Laravel) забирает из RabbitMQ.
4. `IncomingMessageService` просит Gemini (AI) разобрать вакансию на JSON (роль, скиллы, зп).
5. Сервис проверяет совпадения по ключам пользователей (учитывая стоп-слова).
6. Сервис сохраняет пост в БД и мнеотправляет `SendTelegramNotificationJob`.
7. `Worker_notifications` исполняет джоб и бот отправляет сообщение юзеру в ЛС.
