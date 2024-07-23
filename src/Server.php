<?php

/**
 * Provides CGI server
 */
class Server
{
    private Socket $server;

    public function __construct(
        public readonly string $address,
        public readonly int    $port,
        public readonly string $public,
    )
    {
    }

    public function run()
    {
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $server = $this->server;
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($server, $this->address, $this->port);
        socket_listen($server);

        echo "Server started on {$this->address}:{$this->port}\n";

        while (true) {
            $client = socket_accept($server);
            $request = socket_read($client, 4096);
            $requestObject = Request::fromString($request);
            $this->handleRequest($client, $requestObject);
            socket_close($client);
        }
    }


    private function handleRequest(false|Socket $client, Request $request): void
    {
        $env = [
            'REQUEST_METHOD' => $request->method,
            'CONTENT_LENGTH' => strlen($request->body),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'QUERY_STRING' => $request->query,
        ];

        $cmd = $this->public .$request->path;
        $params = str_replace('+', ' ', $request->query);

        $descriptor_spec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        echo "Running: $cmd \n";
        $process = proc_open($cmd . ' ' . $params, $descriptor_spec, $pipes, $this->public, $env);
        if (!is_resource($process)) {
            echo 'Error opening CGI program: ' . $cmd . "\n";
            return;
        }

        if ($request->body) {
            fwrite($pipes[0], $request->body);
        }
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        if ($errors) {
            echo $errors;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        // Sending response to browser
        $statusCode = str_contains($output, 'Location') ? '302 Found' : '200 OK';
        $response = "HTTP/1.1 $statusCode\r\n$output";
        socket_write($client, $response);
    }

    public function __destruct()
    {
        socket_close($this->server);
    }
}
