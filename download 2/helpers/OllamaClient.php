<?php
declare(strict_types=1);

/**
 * OllamaClient — يدعم Ollama Cloud (api.ollama.com) والـ API المحلي.
 * المفتاح OLLAMA_API_KEY من .env يفعل وضع السحابة تلقائيا.
 */
class OllamaClient
{
    private string $endpoint;
    private string $model;
    private string $apiKey;
    private int    $timeout;
    private bool   $isCloud;

    public function __construct(
        string $endpoint = '',
        string $model    = 'llama3.2',
        string $apiKey   = '',
        int    $timeout  = 30
    ) {
        $this->apiKey   = $apiKey;
        $this->model    = $model;
        $this->timeout  = $timeout;
        $this->isCloud  = $apiKey !== '';

        if ($this->isCloud && $endpoint === '') {
            $endpoint = 'https://api.ollama.com';
        } elseif ($endpoint === '') {
            $endpoint = 'http://localhost:11434';
        }

        $this->endpoint = rtrim($endpoint, '/');
    }

    public function chat(string $prompt, float $temperature = 0.3, int $maxTokens = 800): string
    {
        return $this->isCloud
            ? $this->chatCloud($prompt, $temperature, $maxTokens)
            : $this->generateLocal($prompt, $temperature, $maxTokens);
    }

    private function chatCloud(string $prompt, float $temperature, int $maxTokens): string
    {
        $url  = $this->endpoint . '/api/chat';
        $body = json_encode([
            'model'    => $this->model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'stream'   => false,
            'options'  => ['temperature' => $temperature, 'num_predict' => $maxTokens],
        ]);
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->apiKey];
        $res     = $this->curlPost($url, $body, $headers);
        $data    = json_decode($res, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            throw new RuntimeException('Ollama Cloud: JSON غير صالح');
        $text = $data['message']['content'] ?? ($data['response'] ?? null);
        if (!$text) throw new RuntimeException('Ollama Cloud: لا يوجد رد');
        return trim($text);
    }

    private function generateLocal(string $prompt, float $temperature, int $maxTokens): string
    {
        $url     = $this->endpoint . '/api/generate';
        $body    = json_encode([
            'model'   => $this->model, 'prompt' => $prompt, 'stream' => false,
            'options' => ['temperature' => $temperature, 'num_predict' => $maxTokens],
        ]);
        $headers = ['Content-Type: application/json'];
        if (function_exists('curl_init')) {
            $res = $this->curlPost($url, $body, $headers);
        } elseif (ini_get('allow_url_fopen')) {
            $res = $this->fgcPost($url, $body);
        } else {
            throw new RuntimeException('لا يوجد وسيلة HTTP متاحة');
        }
        $data = json_decode($res, true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('Ollama: JSON غير صالح');
        $text = $data['response'] ?? null;
        if (!$text) throw new RuntimeException('Ollama: لا يوجد رد');
        return trim($text);
    }

    private function curlPost(string $url, string $body, array $headers): string
    {
        if (!function_exists('curl_init')) throw new RuntimeException('curl غير متاح');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err)          throw new RuntimeException("curl error: {$err}");
        if ($code !== 200) throw new RuntimeException("Ollama HTTP {$code}: " . substr((string)$res, 0, 300));
        return (string)$res;
    }

    private function fgcPost(string $url, string $body): string
    {
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'header' => "Content-Type: application/json\r\n",
            'content' => $body, 'timeout' => $this->timeout,
        ]]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) throw new RuntimeException('file_get_contents فشل');
        return $res;
    }

    public static function fromSettings(): self
    {
        $endpoint = Config::get('OLLAMA_ENDPOINT') ?: setting('ollama_endpoint', '');
        $model    = Config::get('OLLAMA_MODEL')    ?: setting('ollama_model', 'llama3.2');
        $apiKey   = Config::get('OLLAMA_API_KEY')  ?: '';
        return new self($endpoint, $model, $apiKey, 30);
    }
}
