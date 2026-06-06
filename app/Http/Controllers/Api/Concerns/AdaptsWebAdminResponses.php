<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait AdaptsWebAdminResponses
{
    protected function adaptWebResponse(mixed $response, int $successStatus = 200): JsonResponse|StreamedResponse|Response
    {
        if ($response instanceof StreamedResponse || $response instanceof Response && ! $response instanceof RedirectResponse) {
            return $response;
        }

        if ($response instanceof RedirectResponse) {
            $errors = session('errors');
            if ($errors instanceof MessageBag && $errors->isNotEmpty()) {
                return response()->json([
                    'message' => $errors->first() ?? 'Validation failed.',
                    'errors' => $errors->getMessages(),
                ], 422);
            }

            $error = session('error');
            if ($error) {
                return response()->json(['message' => is_string($error) ? $error : 'Operation failed.'], 422);
            }

            $info = session('info');
            if ($info) {
                return response()->json(['message' => is_string($info) ? $info : 'OK']);
            }

            $success = session('success');

            return response()->json([
                'message' => is_string($success) ? $success : 'Success.',
            ], $successStatus);
        }

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return response()->json(['message' => 'Success.'], $successStatus);
    }
}
