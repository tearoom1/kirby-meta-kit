<?php

namespace TearoomOne\Tests;

/**
 * Serves tests/fixtures/openrouter-mock.php with PHP's built-in server
 * for the lifetime of a test class; the URL path selects the scenario.
 */
trait MockApiServer
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

        self::fail('Mock API server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }
}
