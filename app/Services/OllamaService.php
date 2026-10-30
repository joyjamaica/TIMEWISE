<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OllamaService
{
    public const SYSTEM_PROMPT = <<<'TXT'
You are PulseBot, a friendly study and productivity assistant inside TimeWise, a student planner.
Help with: explaining academic topics and programming concepts, practice questions, lesson summaries,
study strategies, task prioritization, breaking assignments into steps, and realistic study schedules.
Be concise and clear. Use short paragraphs or simple lists. If you are unsure, say so rather than guessing.
Only use the TimeWise data provided to you; never invent tasks or deadlines.
TXT;

    /**
     * @param  array<int, array{role:string, content:string}>  $history  prior user/assistant turns
     * @return array{ok:bool, reply:?string, error:?string}
     */
    public function chat(array $history, string $userMessage, ?string $context = null): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return $this->fail('Please type a message first.');
        }

        $cfg = config('services.ollama');

        $system = self::SYSTEM_PROMPT;
        if ($context) {
            $system .= "\n\nStudent's TimeWise data:\n".$context;
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $this->trimHistory($history),
            [['role' => 'user', 'content' => $userMessage]]
        );

        try {
            $request = Http::baseUrl(rtrim($cfg['url'], '/'))
                ->connectTimeout(5)
                ->timeout($cfg['timeout'])
                ->acceptJson();

            if (!empty($cfg['token'])) {
                $request = $request->withToken($cfg['token']);
            }

            $response = $request->post('/api/chat', [
                'model'      => $cfg['model'],
                'messages'   => $messages,
                'stream'     => false,
                'keep_alive' => '10m',
                'options'    => [
                    'num_ctx'     => $cfg['num_ctx'],
                    'temperature' => 0.5,
                ],
            ]);

            if ($response->failed()) {
                Log::warning('Ollama request failed', ['status' => $response->status()]);
                return $this->fail('PulseBot is unavailable right now. Please try again in a moment.');
            }

            $reply = trim((string) data_get($response->json(), 'message.content', ''));

            if ($reply === '') {
                return $this->fail('PulseBot did not return an answer. Please try rephrasing.');
            }

            return ['ok' => true, 'reply' => $reply, 'error' => null];
        } catch (Throwable $e) {
            // Connection refused, timeout, DNS, etc. Log details, show nothing sensitive.
            Log::error('Ollama error: '.$e->getMessage());
            return $this->fail('PulseBot is offline right now. Please try again later.');
        }
    }

    /** Keep only the last 8 turns, and cap each message's length. */
    private function trimHistory(array $history): array
    {
        $clean = [];
        foreach (array_slice($history, -8) as $m) {
            $role = $m['role'] ?? '';
            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => mb_substr((string) ($m['content'] ?? ''), 0, 2000)];
        }
        return $clean;
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'reply' => null, 'error' => $message];
    }
}
