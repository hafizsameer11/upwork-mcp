<?php

namespace App\Services\Ai;

use App\Models\AiAnalysisLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiService
{
    public function chat(array $messages, array $options = []): array
    {
        $apiKey = config('upwork.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = $options['model'] ?? config('upwork.openai.model');
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.4,
        ];

        if (($options['json'] ?? true) !== false) {
            $payload['response_format'] = $options['response_format'] ?? ['type' => 'json_object'];
        }

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI chat failed: '.$response->body());
        }

        $json = $response->json();
        $content = $json['choices'][0]['message']['content'] ?? '{}';

        if (($options['json'] ?? true) === false) {
            $decoded = ['content' => $content];
        } else {
            $decoded = json_decode($content, true) ?? ['raw' => $content];
        }

        AiAnalysisLog::query()->create([
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'action' => $options['action'] ?? 'chat',
            'model' => $model,
            'prompt_tokens' => $json['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $json['usage']['completion_tokens'] ?? null,
            'request' => ['messages' => $messages],
            'response' => $decoded,
        ]);

        return $decoded;
    }

    /** Plain-text assistant reply (for job chat). */
    public function reply(array $messages, array $options = []): string
    {
        $result = $this->chat($messages, array_merge($options, [
            'json' => false,
            'temperature' => $options['temperature'] ?? 0.5,
        ]));

        return (string) ($result['content'] ?? '');
    }

    public function embed(string $text): array
    {
        $apiKey = config('upwork.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = config('upwork.openai.embedding_model');
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $model,
                'input' => mb_substr($text, 0, 8000),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI embedding failed: '.$response->body());
        }

        return $response->json('data.0.embedding') ?? [];
    }
}
