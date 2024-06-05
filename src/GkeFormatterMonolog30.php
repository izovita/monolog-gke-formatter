<?php

namespace MacPaw\MonologGkeFormatter;

use GuzzleHttp\Psr7\ServerRequest;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class GkeFormatterMonolog30 extends JsonFormatter
{
    protected const BACKTRACE_DEFAULT_CALL = 6;
    protected int $deepToBacktrace;
    protected bool $httpRequestContext;
    protected bool $sourceLocationContext;

    public function __construct(
        int  $batchMode = self::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $httpRequestContext = false,
        bool $sourceLocationContext = false,
        int  $deepToBacktrace = self::BACKTRACE_DEFAULT_CALL
    )
    {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra);
        $this->httpRequestContext = $httpRequestContext;
        $this->sourceLocationContext = $sourceLocationContext;
        $this->deepToBacktrace = $deepToBacktrace;
    }

    /**
     * @param LogRecord $record
     *
     * @return string
     */
    public function format(LogRecord $record): string
    {
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->deepToBacktrace);

        $normalized = $this->normalize(array_merge(
            $record->extra,
            $this->sourceLocationContext && isset($debug[$this->deepToBacktrace - 2])
                ? [
                'sourceLocation' => [
                    'file' => $debug[$this->deepToBacktrace - 2]['file'],
                    'line' => $debug[$this->deepToBacktrace - 2]['line'],
                    'function' => $this->getFunction($debug),
                ]
            ]
                : [],
            $this->httpRequestContext && false !== strpos(PHP_SAPI, "cgi")
                ? $this->createRequestContext()
                : [],
            [
                'message' => $record->message,
                'thread' => $record->channel,
                'severity' => $record->level->getName(),
                'serviceContext' => $record->context,
                'timestamp' => $record->datetime->getTimestamp(),
            ]
        ));

        return $this->toJson($normalized, true) . ($this->appendNewline ? "\n" : '');
    }

    /**
     * @return mixed[]
     */
    private function createRequestContext(): array
    {
        $request = ServerRequest::fromGlobals();

        return [
            'httpRequest' => [
                'requestMethod' => $request->getMethod(),
                'requestUrl' => $request->getUri()->__toString(),
                'requestSize' => $request->getBody()->getSize(),
                'protocol' => $request->getProtocolVersion(),
                'referer' => $request->getHeaderLine('Referer'),
                'userAgent' => $request->getHeaderLine('User-Agent'),
                'remoteIp' => $request->getHeaderLine('X-Forwarded-For'),
            ],
        ];
    }

    /**
     * @param mixed[] $debug
     *
     * @return string
     */
    private function getFunction(array $debug): string
    {
        $cursor = $debug[$this->deepToBacktrace - 1];

        return isset($cursor['class'], $cursor['function'])
            ? $cursor['class'] . $cursor['type'] . $cursor['function']
            : $cursor['function'] ?? '';
    }
}
