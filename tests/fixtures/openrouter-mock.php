<?php

/**
 * Minimal OpenRouter stand-in for OpenRouterApiTest, served with `php -S`.
 * The path selects the scenario; successful calls echo the received request.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json');

if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer test-key') {
    http_response_code(401);
    echo json_encode(['error' => ['message' => 'No auth credentials found', 'code' => 401]]);
    return;
}

switch ($path) {
    case '/ok':
        echo json_encode([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'method'      => $_SERVER['REQUEST_METHOD'],
                        'contentType' => $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null,
                        'referer'     => $_SERVER['HTTP_HTTP_REFERER'] ?? $_SERVER['HTTP_REFERER'] ?? null,
                        'model'       => $body['model'] ?? null,
                        'maxTokens'   => $body['max_tokens'] ?? null,
                        'temperature' => $body['temperature'] ?? null,
                        'prompt'      => $body['messages'][0]['content'] ?? null,
                    ]),
                ],
            ]],
        ]);
        return;

    case '/provider-error':
        http_response_code(429);
        echo json_encode([
            'error' => [
                'message'  => 'Provider returned error',
                'code'     => 429,
                'metadata' => [
                    'provider_name' => 'TestProvider',
                    'raw'           => json_encode(['error' => ['message' => 'Rate limit exceeded']]),
                ],
            ],
        ]);
        return;

    case '/no-choices':
        echo json_encode(['error' => ['message' => 'Model is overloaded']]);
        return;

    case '/html-error':
        header('Content-Type: text/html');
        http_response_code(502);
        echo "<html>\n  <body>Bad   Gateway</body>\n</html>";
        return;
}

http_response_code(404);
echo json_encode(['error' => ['message' => 'Unknown scenario']]);
