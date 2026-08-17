<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Channel\SyncChannels;
use App\Jobs\ProcessIncomingMessage;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeTelegramMessages extends Command
{
      protected $signature = 'app:consume-telegram';

      protected $description = 'Consume messages and sync channels from RabbitMQ';

      public function handle(SyncChannels $syncAction): int
      {
            $config = config('services.rabbitmq');

            $this->info("Connecting to RabbitMQ at {$config['host']}:{$config['port']}...");

            try {
                  $connection = new AMQPStreamConnection(
                        $config['host'],
                        $config['port'],
                        $config['user'],
                        $config['password']
                  );

                  $channel = $connection->channel();

                  $msgQueue = 'new_messages';
                  $channel->queue_declare($msgQueue, false, true, false, false);

                  $syncQueue = 'channel_sync';
                  $channel->queue_declare($syncQueue, false, true, false, false);

                  $this->info(" [*] Listening for messages and sync data...");

                  // 1. Message callback
                  $msgCallback = function (AMQPMessage $msg) {
                        $data = json_decode($msg->body, true);
                        if ($data) {
                              ProcessIncomingMessage::dispatchSync(
                                    $data['text'] ?? '',
                                    [
                                          'message_id' => $data['message_id'] ?? 0,
                                          'channel_telegram_id' => $data['channel_id'] ?? 0,
                                          'link' => $data['link'] ?? null,
                                    ]
                              );
                        }
                        $msg->ack();
                  };

                  // 2. Channel sync callback
                  $syncCallback = function (AMQPMessage $msg) use ($syncAction) {
                        $channels = json_decode($msg->body, true);
                        if (is_array($channels)) {
                              $this->info(" [S] Syncing " . count($channels) . " channels...");
                              $syncAction($channels);
                              $this->info(" [V] Sync complete.");
                        }
                        $msg->ack();
                  };

                  $channel->basic_qos(0, 1, false);
                  $channel->basic_consume($msgQueue, '', false, false, false, false, $msgCallback);
                  $channel->basic_consume($syncQueue, '', false, false, false, false, $syncCallback);

                  while ($channel->is_consuming()) {
                        $channel->wait();
                  }

            } catch (\Exception $e) {
                  $this->error("Error: " . $e->getMessage());
                  return 1;
            }

            return 0;
      }
}
