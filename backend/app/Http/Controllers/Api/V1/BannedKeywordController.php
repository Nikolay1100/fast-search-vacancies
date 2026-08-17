<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Keyword\CreateBannedKeyword;
use App\Actions\Keyword\DeleteBannedKeyword;
use App\Actions\Keyword\ListBannedKeywords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Keyword\StoreBannedKeywordRequest;
use App\Models\BannedWord;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BannedKeywordController extends Controller
{
    /**
     * List all user stop words.
     */
    public function index(Request $request, ListBannedKeywords $action): JsonResponse
    {
        $bannedWords = $action($request->user());

        return ApiResponse::data($bannedWords);
    }

    /**
     * Add a new stop word.
     */
    public function store(StoreBannedKeywordRequest $request, CreateBannedKeyword $action): JsonResponse
    {
        $bannedWord = $action($request->user(), $request->validated());

        return ApiResponse::data($bannedWord);
    }

    /**
     * Remove a stop word.
     */
    public function destroy(Request $request, BannedWord $bannedKeyword, DeleteBannedKeyword $action): JsonResponse
    {
        // Check if this stop word is actually associated with the user
        $exists = $bannedKeyword->users()->where('users.id', $request->user()->id)->exists();

        if (!$exists) {
            return ApiResponse::error('Unauthorized action.', 403);
        }

        $action($request->user(), $bannedKeyword);

        return ApiResponse::data(['message' => 'Deleted successfully']);
    }
}
