<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vacancy\ListVacancies;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VacancyResource;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VacancyController extends Controller
{
    /**
     * List user matched vacancies.
     */
    public function index(Request $request, ListVacancies $action): JsonResponse
    {
        $vacancies = $action($request->user());
        $resource = VacancyResource::collection($vacancies);
        $paginatedData = $resource->response()->getData(true);

        return ApiResponse::data(
            $paginatedData['data'],
            $paginatedData['meta'] ?? [],
            $paginatedData['links'] ?? []
        );
    }
}
