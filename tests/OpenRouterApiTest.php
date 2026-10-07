<?php

namespace TearoomOne\Tests;

use TearoomOne\MetaKit;

/**
 * Exercises MetaKit::callApi() against a local OpenRouter stand-in
 * (tests/fixtures/openrouter-mock.php served by PHP's built-in server).
 */
class OpenRouterApiTest extends KirbyTestCase
{
    private static $server;
    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $port = self::freePort();
        $router = __DIR__ . '/fixtures/openrouter-mock.php';

        self::$server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        self::$baseUrl = 'http://127.0.0.1:' . $port;

        // Wait until the server accepts connections
        for ($i = 0; $i < 50; $i++) {
            $socket = @fsockopen('127.0.0.1', $port);
            if ($socket) {
                fclose($socket);
                return;
            }
            usleep(100000);
        }

        self::fail('Mock OpenRouter server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    public function testSuccessfulCallReturnsContentAndSendsExpectedRequest(): void
    {
        $result = $this->callApi('/ok', 'Write a title', 123);
        $request = json_decode($result, true);

        $this->assertSame('POST', $request['method']);
        $this->assertStringStartsWith('application/json', (string)$request['contentType']);
        $this->assertNotEmpty($request['referer']);
        $this->assertSame('test-model', $request['model']);
        $this->assertSame(123, $request['maxTokens']);
        $this->assertEquals(0.3, $request['temperature']);
        $this->assertSame('Write a title', $request['prompt']);
    }

    public function testProviderErrorIsFormattedWithContext(): void
    {
        $this->expectExceptionMessage(
            'OpenRouter API error: Provider returned error: Rate limit exceeded (model: test-model, provider: TestProvider, code: 429)'
        );

        $this->callApi('/provider-error');
    }

    public function testInvalidKeyReportsApiMessage(): void
    {
        $this->expectExceptionMessage('OpenRouter API error: No auth credentials found (model: test-model, code: 401)');

        $this->callApi('/ok', apiKey: 'wrong-key');
    }

    public function testSuccessfulStatusWithoutChoicesThrows(): void
    {
        $this->expectExceptionMessage('OpenRouter API error: Model is overloaded');

        $this->callApi('/no-choices');
    }

    public function testNonJsonErrorBodyIsCompacted(): void
    {
        $this->expectExceptionMessage('OpenRouter API error: <html> <body>Bad Gateway</body> </html>');

        $this->callApi('/html-error');
    }

    public function testUnreachableEndpointThrows(): void
    {
        $this->expectException(\Exception::class);

        $this->callApi('/ok', endpoint: 'http://127.0.0.1:' . self::freePort() . '/ok');
    }

    public function testMissingApiKeyThrowsBeforeRequest(): void
    {
        $this->expectExceptionMessage('OpenRouter API key is not configured');

        $this->callApi('/ok', apiKey: '');
    }

    public function testMissingModelThrowsBeforeRequest(): void
    {
        $this->expectExceptionMessage('OpenRouter model is not configured');

        $this->callApi('/ok', model: '  ');
    }

    private function callApi(
        string $path,
        string $prompt = 'Prompt',
        int $maxTokens = 50,
        string $apiKey = 'test-key',
        string $model = 'test-model',
        ?string $endpoint = null
    ): string {
        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Test Site'],
            [
                'tearoom1.meta-kit' => [
                    'api.endpoint'    => $endpoint ?? self::$baseUrl . $path,
                    'api.key'         => $apiKey,
                    'api.model'       => $model,
                    'api.temperature' => 0.3,
                ],
            ]
        );

        $metaKit = new MetaKit($kirby);
        $method = (new \ReflectionClass(MetaKit::class))->getMethod('callApi');

        return $method->invoke($metaKit, $prompt, $maxTokens);
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }
}
