<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PulseBotController extends Controller
{
    /** Chat history lives in the logged-in user's own session, so it can never leak to another user. */
    private const SESSION_KEY = 'pulsebot.history';
    private const MAX_MESSAGES = 50; // 25 exchanges (user + assistant)

    public function index(Request $request)
    {

    $history = $request->session()->get(self::SESSION_KEY, []);
    return view('pulsebot', compact('history'));
    
    }

    public function send(Request $request, OllamaService $ollama): JsonResponse
    {
        $request->merge(['message' => trim((string) $request->input('message', ''))]);

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Please type a message first.',
            'message.max'      => 'Your message is too long. Keep it under 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first('message')], 422);
        }

        $message = $validator->validated()['message'];
        $history = $request->session()->get(self::SESSION_KEY, []);

        // Only one generation at a time, so a laptop running a 1B model is not overwhelmed.
        try {
            $result = Cache::lock('pulsebot-generating', 90)->block(8, function () use ($ollama, $history, $message, $request) {
                return $ollama->chat($history, $message, $this->buildContext($request));
            });
        } catch (LockTimeoutException $e) {
            return response()->json(['error' => 'PulseBot is busy helping someone else. Try again in a few seconds.'], 429);
        }

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 503);
        }

        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        $request->session()->put(self::SESSION_KEY, array_slice($history, -self::MAX_MESSAGES));

        return response()->json(['reply' => $result['reply']]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }

    /**
     * TimeWise context for the prompt: the logged-in user's own open tasks only.
     * The user_id filter is what keeps one student's data away from another.
     */
    private function buildContext(Request $request): ?string
    {
        $tasks = DB::table('tasks')
            ->where('user_id', $request->user()->id)
            ->where('is_completed', false)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit(15)
            ->get(['title', 'priority', 'difficulty', 'due_date', 'description']);

        $lines = ['Today is '.now()->format('l, F j, Y, g:i A').'.'];

        if ($tasks->isEmpty()) {
            $lines[] = 'The student has no open tasks.';
        } else {
            $lines[] = 'Open tasks (earliest deadline first):';
            foreach ($tasks as $t) {
                $due  = $t->due_date ? date('M j, g:i A', strtotime($t->due_date)) : 'no deadline';
                $desc = $t->description ? ' | notes: '.$this->clean($t->description, 80) : '';
                $lines[] = '- '.$this->clean($t->title, 80)
                    .' | priority: '.$this->clean($t->priority, 20)
                    .' | difficulty: '.$this->clean($t->difficulty, 20)
                    .' | due: '.$due.$desc;
            }
        }

        return implode("\n", $lines);
    }

    /** Task text is user-written, so flatten it to one short line before it enters the prompt. */
    private function clean(?string $text, int $max): string
    {
        return mb_substr(trim(preg_replace('/\s+/', ' ', (string) $text)), 0, $max);
    }
}
