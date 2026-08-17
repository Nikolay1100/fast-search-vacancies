<?php

declare(strict_types=1);

namespace App\Http\Response;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
      /**
       * Return a successful data response.
       */
      public static function data(mixed $data, mixed $meta = [], mixed $links = []): JsonResponse
      {
            $response = ['data' => $data];

            if (!empty($meta)) {
                  $response['meta'] = $meta;
            }

            if (!empty($links)) {
                  $response['links'] = $links;
            }

            return response()->json($response);
      }

      /**
       * Return a single error response.
       */
      public static function error(string $title, int $status = 400, ?string $detail = null, ?string $code = null): JsonResponse
      {
            return response()->json([
                  'errors' => [
                        [
                              'status' => (string) $status,
                              'title' => $title,
                              'detail' => $detail,
                              'code' => $code,
                        ]
                  ]
            ], $status);
      }

      /**
       * Return validation errors response.
       */
      public static function validationErrors(array $errors): JsonResponse
      {
            $formattedErrors = [];

            foreach ($errors as $field => $messages) {
                  foreach ($messages as $message) {
                        $formattedErrors[] = [
                              'status' => '422',
                              'title' => 'Validation Error',
                              'detail' => $message,
                              'source' => ['pointer' => "/{$field}"],
                        ];
                  }
            }

            return response()->json([
                  'errors' => $formattedErrors
            ], 422);
      }
}
