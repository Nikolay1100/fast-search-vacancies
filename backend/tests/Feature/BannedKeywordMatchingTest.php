<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Keyword\FindMatches;
use App\Actions\Keyword\CreateBannedKeyword;
use App\Actions\Keyword\DeleteBannedKeyword;
use App\Actions\Keyword\ListBannedKeywords;
use App\Models\BannedWord;
use App\Models\Keyword;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BannedKeywordMatchingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Test that user is matched by keyword but excluded if personal stop word matches.
     */
    public function test_user_matched_by_keyword_but_excluded_by_personal_stop_word(): void
    {
        // 1. Create a user
        $user = User::factory()->create([
            'telegram_id' => 123456789,
        ]);

        // 2. Create keyword and associate with user
        $keyword = Keyword::create(['word' => 'PHP']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        // 3. Verify matching works initially
        $findMatches = app(FindMatches::class);
        $matchedUsers = $findMatches('Need a Senior PHP developer for a startup.');

        $this->assertCount(1, $matchedUsers);
        $this->assertEquals($user->id, $matchedUsers->first()->id);

        // 4. Add a personal stop word "junior" using action
        $createAction = app(CreateBannedKeyword::class);
        $bannedWord = $createAction($user, ['word' => 'junior']);

        $this->assertDatabaseHas('banned_words', [
            'word' => 'junior',
            'is_global' => false,
        ]);
        $this->assertDatabaseHas('banned_keywords_user', [
            'banned_word_id' => $bannedWord->id,
            'user_id' => $user->id,
        ]);

        // 5. Test text matching: "Junior PHP developer" should exclude this user
        $matchedUsersAfterStopWord = $findMatches('Junior PHP developer needed.');
        $this->assertCount(0, $matchedUsersAfterStopWord);

        // 6. Test text matching: "Senior PHP developer" should still match this user
        $matchedUsersStillMatched = $findMatches('Senior PHP developer needed.');
        $this->assertCount(1, $matchedUsersStillMatched);

        // 7. Delete the stop word using action
        $deleteAction = app(DeleteBannedKeyword::class);
        $deleteAction($user, $bannedWord);

        $this->assertDatabaseMissing('banned_keywords_user', [
            'banned_word_id' => $bannedWord->id,
            'user_id' => $user->id,
        ]);
        // The word should be cleaned up from the banned_words table since no one uses it
        $this->assertDatabaseMissing('banned_words', [
            'id' => $bannedWord->id,
        ]);

        // 8. Verify user matches again after stop word deletion
        $matchedUsersRestored = $findMatches('Junior PHP developer needed.');
        $this->assertCount(1, $matchedUsersRestored);
    }

    /**
     * Test that user is excluded if a global stop word matches.
     */
    public function test_user_excluded_by_global_stop_word(): void
    {
        $user = User::factory()->create([
            'telegram_id' => 123456789,
        ]);

        $keyword = Keyword::create(['word' => 'PHP']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        // Create global banned word
        BannedWord::create([
            'word' => 'casino',
            'is_global' => true,
        ]);

        $findMatches = app(FindMatches::class);

        // Match with keyword but has global banned word -> should return empty
        $matchedUsers = $findMatches('PHP job in a casino company.');
        $this->assertCount(0, $matchedUsers);
    }

    /**
     * Test that keywords inside URLs are ignored to avoid false positives.
     */
    public function test_keywords_in_urls_are_ignored(): void
    {
        $user = User::factory()->create([
            'telegram_id' => 123456789,
        ]);

        // User has keyword 'php'
        $keyword = Keyword::create(['word' => 'php']);
        $user->keywords()->attach($keyword->id, ['is_active' => true]);

        $findMatches = app(FindMatches::class);

        // Text only contains 'php' as part of a URL -> should NOT match
        $matchedUsers = $findMatches('Check out this link: https://example.com/php/jobs');
        $this->assertCount(0, $matchedUsers);

        // Text contains 'php' outside the URL as well -> should match
        $matchedUsersWithText = $findMatches('Check out this link: https://example.com/php/jobs for a php role');
        $this->assertCount(1, $matchedUsersWithText);
    }
}
