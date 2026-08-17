import os
import json
import pika
import time
import asyncio
import random
from pyrogram import Client, filters, idle
from pyrogram.raw import functions, types
from dotenv import load_dotenv

load_dotenv()

# Настройки
api_id = os.getenv("API_ID")
api_hash = os.getenv("API_HASH")
raw_folder_names = os.getenv("FOLDER_NAMES", "JOB-CHAN-TES")
folder_names = [f.strip() for f in raw_folder_names.split(",") if f.strip()]

# RabbitMQ
rabbit_host = os.getenv("RABBIT_HOST", "rabbitmq")
rabbit_user = os.getenv("RABBIT_USER", "fastsearch")
rabbit_pass = os.getenv("RABBIT_PASS", "secret_password")
rabbit_queue = os.getenv("RABBIT_QUEUE", "new_messages")
sync_queue = "channel_sync"

def get_rabbit():
    try:
        credentials = pika.PlainCredentials(rabbit_user, rabbit_pass)
        conn = pika.BlockingConnection(pika.ConnectionParameters(host=rabbit_host, credentials=credentials, heartbeat=600))
        chan = conn.channel()
        chan.queue_declare(queue=rabbit_queue, durable=True)
        chan.queue_declare(queue=sync_queue, durable=True)
        chan.queue_declare(queue="default", durable=True)
        return conn, chan
    except:
        return None, None

# Сессия v3
session_path = "/sessions/fast_search_session_v3"
app = Client(session_path, api_id=api_id, api_hash=api_hash)

allowed_channel_ids = set()
last_processed_ids = {} 

async def sync_folder(client):
    global allowed_channel_ids
    print(f" [*] Sync started for folders: {', '.join(folder_names)}")
    try:
        async for _ in client.get_dialogs(): pass
        
        from pyrogram.raw import functions
        dialog_filters = await client.invoke(functions.messages.GetDialogFilters())
        targets = [f for f in dialog_filters if hasattr(f, "title") and f.title in folder_names]
        
        allowed_channel_ids.clear()
        sync_data = []
        for target in targets:
            if hasattr(target, "include_peers"):
                for peer in target.include_peers:
                    cid = None
                    if hasattr(peer, "channel_id"): cid = int(f"-100{peer.channel_id}")
                    elif hasattr(peer, "chat_id"): cid = int(f"-{peer.chat_id}")
                    elif hasattr(peer, "user_id"): cid = peer.user_id
                    if cid: allowed_channel_ids.add(cid)

        for cid in list(allowed_channel_ids):
            try:
                chat = await client.get_chat(cid)
                sync_data.append({"channel_id": cid, "name": chat.title or "Unknown"})
                print(f"   [+] OK: {chat.title}")
            except:
                sync_data.append({"channel_id": cid, "name": f"Channel {cid}"})
        
        conn, chan = get_rabbit()
        if chan:
            chan.basic_publish(exchange='', routing_key=sync_queue, body=json.dumps(sync_data))
            conn.close()
    except Exception as e:
        print(f" [!] Sync error: {e}")

async def process_post(message):
    cid = message.chat.id
    msg_id = message.id
    
    if cid in last_processed_ids and msg_id <= last_processed_ids[cid]:
        return

    title = (message.chat.title or message.chat.first_name or "Unknown")
    last_processed_ids[cid] = msg_id
    
    html_text = ""
    if message.text: html_text = message.text.html
    elif message.caption: html_text = message.caption.html

    conn, chan = get_rabbit()
    if chan:
        try:
            raw_obj = json.loads(str(message))
        except:
            raw_obj = {"info": "raw_dump_failed"}

        data = {
            "text": html_text,
            "channel_id": cid,
            "channel_name": title,
            "message_id": msg_id,
            "link": f"https://t.me/{message.chat.username}/{msg_id}" if message.chat.username else None,
            "raw_data": raw_obj
        }
        chan.basic_publish(exchange='', routing_key=rabbit_queue, body=json.dumps(data))
        conn.close()
        print(f"   [v] Forwarded: {title} ({msg_id})")

async def polling_task(client):
    print(" [!] Polling loop starting (with Read Events)...")
    while True:
        ids_to_check = list(allowed_channel_ids)
        for cid in ids_to_check:
            try:
                max_id = 0
                async for message in client.get_chat_history(cid, limit=2):
                    await process_post(message)
                    if message.id > max_id:
                        max_id = message.id
                
                # ИМИТАЦИЯ ПРОЧТЕНИЯ
                if max_id > 0:
                    # Небольшая пауза перед прочтением (0.5 - 2 сек), как будто человек смотрит в экран
                    await asyncio.sleep(random.uniform(0.5, 2.0))
                    await client.read_chat_history(cid, max_id)
                    # print(f"   [R] Marked as read: {cid}")

                await asyncio.sleep(random.uniform(0.4, 1.0))
            except Exception as e:
                print(f" [!] Error with {cid}: {e}")
        
        # Рандомное ожидание из вашего конфига (65 - 85 сек)
        wait_time = random.uniform(65.0, 85.0)
        print(f" [~] Cycle complete. Waiting {wait_time:.1f}s")
        await asyncio.sleep(wait_time)

@app.on_message()
async def on_push_message(client, message):
    if message.chat.id in allowed_channel_ids:
        await process_post(message)

async def main():
    await app.start()
    await sync_folder(app)
    asyncio.create_task(polling_task(app))
    print(" [!] Parser is READY (Stealth Mode: Read Events ON).")
    await idle()

if __name__ == "__main__":
    app.run(main())
