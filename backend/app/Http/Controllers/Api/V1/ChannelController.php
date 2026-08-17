<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Channel\ListChannels;
use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ChannelController extends Controller
{
    /**
     * List all channels.
     */
    public function index(ListChannels $action): JsonResponse
    {
        $channels = $action();

        return ApiResponse::data($channels);
    }
}
