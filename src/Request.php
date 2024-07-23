<?php

/**
 * Simper Requset object
 */
readonly class Request
{
    private function __construct(
        public string $method,
        public string $path,
        public string $query,
        public string $body,
    )
    {
    }

    public static function fromString(string $request): self
    {
        $request = explode("\r\n\r\n", $request);
        $headers = explode("\r\n", $request[0]);
        $body = $request[1] ?? '';
        $headerLine = array_shift($headers);
        preg_match('/(GET|POST) (\/\S*) HTTP\/1.\d/', $headerLine, $matches);
        $method = strtolower($matches[1]);
        $path = $matches[2];
        $query = parse_url($path);

        return new self(
            $method,
            $query['path'] ?? '',
            $query['query'] ?? '',
            $body ?? '',
        );
    }
}
