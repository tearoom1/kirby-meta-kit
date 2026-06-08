<?php

namespace TearoomOne;

/**
 * Helper class for building consistent API responses
 */
class ApiResponse
{
    /**
     * Create a success response
     */
    public static function success($data = null, ?string $message = null): array
    {
        $response = ['status' => 'success'];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            if (is_array($data) && isset($data['content'])) {
                // Flatten content key to top level for generate responses
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }

        return $response;
    }

    /**
     * Create an error response
     */
    public static function error(string $message, $data = null): array
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Create a response with generation results
     */
    public static function generated(string $content, ?string $message = null): array
    {
        $response = [
            'status' => 'success',
            'content' => $content
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Create a batch operation response
     */
    public static function batch(int $generated, int $skipped, int $failed, ?string $message = null, array $errors = []): array
    {
        $response = [
            'status' => 'success',
            'message' => $message ?? "Generated {$generated}, skipped {$skipped}, failed {$failed}",
            'generated' => $generated,
            'skipped' => $skipped,
            'failed' => $failed
        ];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $entity = 'Page'): array
    {
        return self::error("{$entity} not found");
    }

}
