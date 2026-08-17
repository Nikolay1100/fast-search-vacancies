<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Keyword\FindMatches;
use App\Models\Keyword;
use App\Models\User;
use App\Models\UserMatchedPost;
use App\Services\Messages\IncomingMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendTelegramNotificationJob;
use App\Services\AI\MessageMatchingService;
use Mockery\MockInterface;
use Tests\TestCase;

class HandleIncomingMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_does_not_send_duplicate_messages_to_the_same_user(): void
    {
        Queue::fake();
        Cache::flush();

        // Create a user with a keyword
        $user = User::factory()->create(['telegram_id' => '123456789']);
        $keyword = Keyword::create(['word' => 'laravel']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        // Mock AI service to return null, falling back to regex matcher
        $this->mock(MessageMatchingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractEntities')->andReturn(null);
        });

        // Resolve the service
        $incomingMessageService = app(IncomingMessageService::class);

        $text = "We are looking for a Laravel developer.";

        // First message comes from channel A
        $incomingMessageService->handle($text, [
            'channel_telegram_id' => -1001111111111,
            'message_id' => 101,
        ]);

        // Assert notification sent and saved
        Queue::assertPushed(SendTelegramNotificationJob::class, 1);
        $this->assertDatabaseHas('channel_messages', ['message_id' => 101]);
        $this->assertDatabaseHas('user_matched_posts', [
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
        ]);

        // Second message comes from channel B, EXACT SAME TEXT
        $incomingMessageService->handle($text, [
            'channel_telegram_id' => -1002222222222,
            'message_id' => 202,
        ]);

        // Assert notification NOT sent again
        Queue::assertPushed(SendTelegramNotificationJob::class, 1); // Still 1

        // But the message itself should be saved as it's a new message on a new channel
        $this->assertDatabaseHas('channel_messages', ['message_id' => 202]);

        // The match should NOT be saved for the user (we skipped it due to cache)
        $this->assertEquals(1, UserMatchedPost::where('user_id', $user->id)->count());

        // Third message comes from channel C, DIFFERENT TEXT but same keyword
        $differentText = "Another Laravel job available.";
        $incomingMessageService->handle($differentText, [
            'channel_telegram_id' => -1003333333333,
            'message_id' => 303,
        ]);

        // Assert notification SENT
        Queue::assertPushed(SendTelegramNotificationJob::class, 2);
        $this->assertEquals(2, UserMatchedPost::where('user_id', $user->id)->count());
    }

    public function test_it_handles_slight_variations_in_formatting(): void
    {
        Queue::fake();
        Cache::flush();

        $user = User::factory()->create(['telegram_id' => '123456789']);
        $keyword = Keyword::create(['word' => 'phpTestTestTest']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        $this->mock(MessageMatchingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractEntities')->andReturn(null);
        });

        $incomingMessageService = app(IncomingMessageService::class);

        $text1 = "phpTestTestTest Developer needed!";
        $text2 = "phpTestTeStTesT developer needed"; // same letters, different case and punctuation

        $incomingMessageService->handle($text1, ['channel_telegram_id' => -1001111111111, 'message_id' => 1]);
        Queue::assertPushed(SendTelegramNotificationJob::class, 1);

        $incomingMessageService->handle($text2, ['channel_telegram_id' => -1002222222222, 'message_id' => 2]);
        Queue::assertPushed(SendTelegramNotificationJob::class, 1); // 1 because formatting variations are now correctly deduplicated
    }

    public function test_debug_find_matches(): void
    {
        $user = User::factory()->create(['telegram_id' => '999888777']);
        $keyword = Keyword::create(['word' => 'laravel']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        $findMatches = app(FindMatches::class);
        $result = $findMatches("We are looking for a Laravel developer.");

        $this->assertNotEmpty($result, 'FindMatches should return at least one user');
        $this->assertEquals($user->id, $result->first()->id);
        $this->assertEquals($keyword->id, $result->first()->matched_keyword_id);
    }

    public function test_it_saves_keyword_id_when_saving_matched_post(): void
    {
        Queue::fake();
        Cache::flush();

        $user = User::factory()->create(['telegram_id' => '111222333']);
        $keyword = Keyword::create(['word' => 'symfony']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        $this->mock(MessageMatchingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractEntities')->andReturn(null);
        });

        $incomingMessageService = app(IncomingMessageService::class);
        $text = "We are looking for a Symfony developer.";

        $incomingMessageService->handle($text, [
            'channel_telegram_id' => -1004444444444,
            'message_id' => 404,
        ]);

        $this->assertDatabaseHas('user_matched_posts', [
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
        ]);
    }
}
