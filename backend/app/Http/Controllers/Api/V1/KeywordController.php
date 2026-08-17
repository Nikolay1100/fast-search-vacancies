<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Keyword\CreateKeyword;
use App\Actions\Keyword\DeleteKeyword;
use App\Actions\Keyword\ListKeywords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Keyword\StoreKeywordRequest;
use App\Models\Keyword;
use App\Http\Response\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KeywordController extends Controller
{
    /**
     * List all user keywords.
     */
    public function index(Request $request, ListKeywords $action): JsonResponse
    {
        $keywords = $action($request->user());

        return ApiResponse::data($keywords);
    }

    /**
     * Add a new keyword.
     */
    public function store(StoreKeywordRequest $request, CreateKeyword $action): JsonResponse
    {
        if ($request->user()->cannot('create', Keyword::class)) {
            return ApiResponse::error('Keyword limit reached. Please upgrade to a premium subscription to add more keywords.', 403);
        }

        $keyword = $action($request->user(), $request->validated());

        return ApiResponse::data($keyword);
    }

    /**
     * Remove a keyword.
     */
    public function destroy(Request $request, Keyword $keyword, DeleteKeyword $action): JsonResponse
    {
        try {
            $action($request->user(), $keyword);
            return ApiResponse::data(['message' => 'Deleted successfully']);
        } catch (AuthorizationException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }
}
