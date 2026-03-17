<?php

namespace app\services;

use app\models\ZenAccount;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Клиент для работы с Dify Workflow API.
 *
 * Пример:
 * $service = new DifyWorkflowApiService();
 * $result = $service->runWorkflow(['query' => 'Hello'], 'post-123');
 */
class DifyWorkflowApiService extends BaseObject
{
    public const RESPONSE_MODE_BLOCKING = 'blocking';
    public const RESPONSE_MODE_STREAMING = 'streaming';

    public string $baseUrl = '';
    public string $apiKey = '';
    public int $connectTimeout = 15;
    public int $timeout = 180;

    public function init(): void
    {
        parent::init();

        $params = Yii::$app->params ?? [];

        if ($this->baseUrl === '') {
            $this->baseUrl = (string) ($params['difyApiBaseUrl'] ?? 'https://dify-generator.ru/v1');
        }
        $this->baseUrl = rtrim($this->baseUrl, '/');

        if ($this->apiKey === '') {
            $this->apiKey = (string) ($params['difyApiKey'] ?? '');
        }
    }

    public static function forAccount(ZenAccount $account): self
    {
        return new self([
            'apiKey' => (string) ($account->workflow_key ?: ''),
        ]);
    }

    public function runWorkflow(array $inputs, string $user, array $files = [], ?string $traceId = null): array
    {
        return $this->requestJson(
            'POST',
            '/workflows/run',
            $this->buildRunPayload($inputs, $user, self::RESPONSE_MODE_BLOCKING, $files, $traceId),
            $traceId
        );
    }

    public function runSpecificWorkflow(string $workflowId, array $inputs, string $user, array $files = [], ?string $traceId = null): array
    {
        return $this->requestJson(
            'POST',
            '/workflows/' . rawurlencode($workflowId) . '/run',
            $this->buildRunPayload($inputs, $user, self::RESPONSE_MODE_BLOCKING, $files, $traceId),
            $traceId
        );
    }

    public function runPipelineUrl(string $pipelineUrl, array $inputs, string $user, array $files = [], ?string $traceId = null): array
    {
        return $this->runSpecificWorkflow(
            $this->extractWorkflowIdFromUrl($pipelineUrl),
            $inputs,
            $user,
            $files,
            $traceId
        );
    }

    public function triggerPipelineUrl(string $pipelineUrl, array $inputs, string $user, array $files = [], ?string $traceId = null): int
    {
        return $this->triggerSpecificWorkflow(
            $this->extractWorkflowIdFromUrl($pipelineUrl),
            $inputs,
            $user,
            $files,
            $traceId
        );
    }

    /**
     * Выполняет workflow в streaming-режиме и передаёт каждый SSE event в callback.
     *
     * @param callable $onEvent function(array $event): void
     */
    public function streamWorkflow(array $inputs, string $user, callable $onEvent, array $files = [], ?string $traceId = null): void
    {
        $this->requestStream(
            'POST',
            '/workflows/run',
            $this->buildRunPayload($inputs, $user, self::RESPONSE_MODE_STREAMING, $files, $traceId),
            $onEvent,
            $traceId
        );
    }

    /**
     * Выполняет конкретную версию workflow в streaming-режиме.
     *
     * @param callable $onEvent function(array $event): void
     */
    public function streamSpecificWorkflow(string $workflowId, array $inputs, string $user, callable $onEvent, array $files = [], ?string $traceId = null): void
    {
        $this->requestStream(
            'POST',
            '/workflows/' . rawurlencode($workflowId) . '/run',
            $this->buildRunPayload($inputs, $user, self::RESPONSE_MODE_STREAMING, $files, $traceId),
            $onEvent,
            $traceId
        );
    }

    public function streamPipelineUrl(string $pipelineUrl, array $inputs, string $user, callable $onEvent, array $files = [], ?string $traceId = null): void
    {
        $this->streamSpecificWorkflow(
            $this->extractWorkflowIdFromUrl($pipelineUrl),
            $inputs,
            $user,
            $onEvent,
            $files,
            $traceId
        );
    }

    public function getWorkflowRun(string $workflowRunId): array
    {
        return $this->requestJson('GET', '/workflows/run/' . rawurlencode($workflowRunId));
    }

    public function stopTask(string $taskId, string $user): array
    {
        return $this->requestJson('POST', '/workflows/tasks/' . rawurlencode($taskId) . '/stop', [
            'user' => $user,
        ]);
    }

    public function triggerSpecificWorkflow(string $workflowId, array $inputs, string $user, array $files = [], ?string $traceId = null): int
    {
        return $this->requestStatusCode(
            'POST',
            '/workflows/' . rawurlencode($workflowId) . '/run',
            $this->buildRunPayload($inputs, $user, self::RESPONSE_MODE_BLOCKING, $files, $traceId),
            $traceId
        );
    }

    public function uploadFile(string $filePath, string $user): array
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException('Файл для загрузки не найден: ' . $filePath);
        }

        $mimeType = function_exists('mime_content_type')
            ? (mime_content_type($filePath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return $this->requestMultipart('POST', '/files/upload', [
            'file' => curl_file_create($filePath, $mimeType, basename($filePath)),
            'user' => $user,
        ]);
    }

    public function getEndUser(string $endUserId): array
    {
        return $this->requestJson('GET', '/end-users/' . rawurlencode($endUserId));
    }

    public function getWorkflowLogs(array $query = []): array
    {
        return $this->requestJson('GET', '/workflows/logs', null, null, $query);
    }

    public function getInfo(): array
    {
        return $this->requestJson('GET', '/info');
    }

    public function getParameters(): array
    {
        return $this->requestJson('GET', '/parameters');
    }

    public function getSite(): array
    {
        return $this->requestJson('GET', '/site');
    }

    public function createRemoteFile(string $type, string $url): array
    {
        return [
            'type' => $type,
            'transfer_method' => 'remote_url',
            'url' => $url,
        ];
    }

    public function createLocalFile(string $type, string $uploadFileId): array
    {
        return [
            'type' => $type,
            'transfer_method' => 'local_file',
            'upload_file_id' => $uploadFileId,
        ];
    }

    public function extractWorkflowIdFromUrl(string $pipelineUrl): string
    {
        if (preg_match(
            '/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/',
            $pipelineUrl,
            $matches
        )) {
            return $matches[0];
        }

        throw new \InvalidArgumentException('Не удалось извлечь workflow_id из Dify pipeline URL.');
    }

    protected function buildRunPayload(array $inputs, string $user, string $responseMode, array $files = [], ?string $traceId = null): array
    {
        $payload = [
            'inputs' => $inputs,
            'response_mode' => $responseMode,
            'user' => $user,
        ];

        if ($files !== []) {
            $payload['files'] = array_values($files);
        }

        if ($traceId !== null && $traceId !== '') {
            $payload['trace_id'] = $traceId;
        }

        return $payload;
    }

    protected function requestJson(string $method, string $path, ?array $body = null, ?string $traceId = null, array $query = []): array
    {
        $this->ensureConfigured();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];

        if ($traceId !== null && $traceId !== '') {
            $headers[] = 'X-Trace-Id: ' . $traceId;
        }

        $payload = null;
        if ($body !== null) {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                throw new \RuntimeException('Не удалось сериализовать JSON-запрос для Dify API.');
            }
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->sendCurlRequest($method, $this->buildUrl($path, $query), $headers, $payload);
        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            throw new \RuntimeException('Dify API вернул невалидный JSON: ' . $response['body']);
        }

        if ($response['httpCode'] >= 400) {
            throw new \RuntimeException($this->buildHttpErrorMessage($response['httpCode'], $data));
        }

        return $data;
    }

    protected function requestStatusCode(string $method, string $path, ?array $body = null, ?string $traceId = null, array $query = []): int
    {
        $this->ensureConfigured();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];

        if ($traceId !== null && $traceId !== '') {
            $headers[] = 'X-Trace-Id: ' . $traceId;
        }

        $payload = null;
        if ($body !== null) {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                throw new \RuntimeException('Не удалось сериализовать JSON-запрос для Dify API.');
            }
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->sendCurlRequest($method, $this->buildUrl($path, $query), $headers, $payload);

        if ($response['httpCode'] >= 400) {
            $data = json_decode($response['body'], true);
            if (is_array($data)) {
                throw new \RuntimeException($this->buildHttpErrorMessage($response['httpCode'], $data));
            }

            throw new \RuntimeException('Dify API вернул HTTP ' . $response['httpCode'] . ': ' . trim($response['body']));
        }

        return $response['httpCode'];
    }

    protected function requestMultipart(string $method, string $path, array $body): array
    {
        $this->ensureConfigured();

        $response = $this->sendCurlRequest($method, $this->buildUrl($path), [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ], $body);

        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            throw new \RuntimeException('Dify API вернул невалидный JSON: ' . $response['body']);
        }

        if ($response['httpCode'] >= 400) {
            throw new \RuntimeException($this->buildHttpErrorMessage($response['httpCode'], $data));
        }

        return $data;
    }

    /**
     * @param callable $onEvent function(array $event): void
     */
    protected function requestStream(string $method, string $path, array $body, callable $onEvent, ?string $traceId = null): void
    {
        $this->ensureConfigured();

        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \RuntimeException('Не удалось сериализовать JSON-запрос для Dify API.');
        }

        $buffer = '';
        $rawResponse = '';
        $ch = curl_init($this->buildUrl($path));

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $this->buildStreamHeaders($traceId),
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$buffer, &$rawResponse, $onEvent) {
                $buffer .= $chunk;
                $rawResponse .= $chunk;

                while (preg_match("/\r\n\r\n|\n\n|\r\r/", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
                    $separator = $matches[0][0];
                    $separatorPos = $matches[0][1];
                    $eventChunk = substr($buffer, 0, $separatorPos);
                    $buffer = (string) substr($buffer, $separatorPos + strlen($separator));
                    $event = $this->parseSseChunk($eventChunk);
                    if ($event !== null) {
                        $onEvent($event);
                    }
                }

                return strlen($chunk);
            },
        ]);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($result === false || $curlError !== '') {
            throw new \RuntimeException('Ошибка запроса к Dify API: ' . ($curlError ?: 'curl_exec вернул false.'));
        }

        $buffer = trim($buffer);
        if ($buffer !== '') {
            $event = $this->parseSseChunk($buffer);
            if ($event !== null) {
                $onEvent($event);
            }
        }

        if ($httpCode >= 400) {
            $data = json_decode($rawResponse, true);
            if (is_array($data)) {
                throw new \RuntimeException($this->buildHttpErrorMessage($httpCode, $data));
            }

            throw new \RuntimeException('Dify API вернул HTTP ' . $httpCode . ': ' . trim($rawResponse));
        }
    }

    protected function sendCurlRequest(string $method, string $url, array $headers, $body = null): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            throw new \RuntimeException('Ошибка запроса к Dify API: ' . ($curlError ?: 'curl_exec вернул false.'));
        }

        return [
            'httpCode' => $httpCode,
            'body' => $raw,
        ];
    }

    protected function buildUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        if ($query !== []) {
            $query = array_filter($query, static function ($value) {
                return $value !== null && $value !== '';
            });
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    protected function buildStreamHeaders(?string $traceId = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: text/event-stream',
            'Content-Type: application/json',
        ];

        if ($traceId !== null && $traceId !== '') {
            $headers[] = 'X-Trace-Id: ' . $traceId;
        }

        return $headers;
    }

    protected function parseSseChunk(string $chunk): ?array
    {
        $chunk = trim($chunk);
        if ($chunk === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $chunk) ?: [];
        $dataLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, ':') === 0) {
                continue;
            }

            if (strpos($line, 'data:') === 0) {
                $dataLines[] = ltrim(substr($line, 5));
            }
        }

        if ($dataLines === []) {
            return null;
        }

        $data = implode("\n", $dataLines);
        if ($data === '[DONE]') {
            return ['event' => 'done'];
        }

        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return [
                'event' => 'raw',
                'data' => $data,
            ];
        }

        return $decoded;
    }

    protected function buildHttpErrorMessage(int $httpCode, array $data): string
    {
        $messageParts = ['Dify API вернул HTTP ' . $httpCode . '.'];

        if (!empty($data['code'])) {
            $messageParts[] = 'Code: ' . $data['code'] . '.';
        }

        if (!empty($data['message'])) {
            $messageParts[] = 'Message: ' . $data['message'];
        } elseif (!empty($data['error'])) {
            $messageParts[] = 'Error: ' . $data['error'];
        }

        return implode(' ', $messageParts);
    }

    protected function ensureConfigured(): void
    {
        if ($this->baseUrl === '') {
            throw new InvalidConfigException('Dify base URL не задан.');
        }

        if ($this->apiKey === '') {
            throw new InvalidConfigException('Dify API key не задан.');
        }
    }
}
