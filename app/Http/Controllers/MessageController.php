<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    private const MAX_LENGTH = 2000;

    public function index(Request $request, string $requestId)
    {
        $target = $this->authorizedRequest($request, $requestId);
        if ($target instanceof \Illuminate\Http\JsonResponse) {
            return $target;
        }

        $limit = min(max((int) $request->input('per_page', 30), 1), 100);
        $query = Message::where('request_id', $target->id)->orderBy('created_at');
        if ($request->filled('after_id')) {
            $after = Message::where('request_id', $target->id)->find($request->input('after_id'));
            if ($after) {
                $query->where('created_at', '>', $after->created_at)->where('id', '!=', $after->id);
            }
        }
        $page = $query->paginate($limit);
        $actor = $this->actor($request);
        $this->markReadForRequest($target, $actor);

        $page->getCollection()->transform(fn (Message $message) => $this->present($message, $actor));

        return response()->json($page);
    }

    public function store(Request $request, string $requestId)
    {
        $target = $this->authorizedRequest($request, $requestId);
        if ($target instanceof \Illuminate\Http\JsonResponse) {
            return $target;
        }
        if (! in_array($target->status->value, [RequestStatus::PENDING->value, RequestStatus::ANALYZING->value, RequestStatus::WAITING->value, RequestStatus::SOLVING->value], true)) {
            return response()->json(['message' => 'Este requerimento está encerrado para novas mensagens.'], 409);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:'.self::MAX_LENGTH, 'not_regex:/^\s*$/u'],
        ]);
        $actor = $this->actor($request);
        $key = 'message:'.$this->actorType($actor).':'.$actor->getKey();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['message' => 'Muitas mensagens em pouco tempo. Aguarde e tente novamente.'], 429);
        }
        RateLimiter::hit($key, 60);

        $message = DB::transaction(function () use ($target, $actor, $validated) {
            $message = Message::create(['request_id' => $target->id, 'sender_id' => $actor->getKey(), 'sender_type' => $this->actorType($actor), 'content' => trim($validated['content'])]);
            if ($actor instanceof StaffAdmin && !$target->first_response_at) {
                $target->update(['first_response_at' => $message->created_at ?? now()]);
            }
            return $message;
        });

        return response()->json($this->present($message, $actor), 201);
    }

    public function markRead(Request $request, string $requestId)
    {
        $target = $this->authorizedRequest($request, $requestId);
        if ($target instanceof \Illuminate\Http\JsonResponse) {
            return $target;
        }
        $this->markReadForRequest($target, $this->actor($request));

        return response()->json(['message' => 'Mensagens marcadas como lidas.']);
    }

    private function authorizedRequest(Request $request, string $id): RequestModel|\Illuminate\Http\JsonResponse
    {
        $actor = $this->actor($request);
        if (! $actor) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }
        $target = RequestModel::with('user.course')->find($id);
        if (! $target) {
            return response()->json(['message' => 'Requerimento não encontrado.'], 404);
        }
        if ($actor instanceof User && $actor->role === User::ROLE_STUDENT && $target->user_id !== $actor->getKey()) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if ($actor instanceof StaffAdmin) {
            if ($actor->role === 'staff') {
                return response()->json(['message' => 'Acesso não autorizado.'], 403);
            }
            if ($actor->role === 'coordenacao' && $target->user?->course_id !== $actor->course_id) {
                return response()->json(['message' => 'Acesso não autorizado.'], 403);
            }
        }

        return $target;
    }

    private function actor(Request $request): User|StaffAdmin|null
    {
        return Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
    }

    private function actorType(User|StaffAdmin $actor): string
    {
        return $actor instanceof User ? 'student' : 'staff_admin';
    }

    private function markReadForRequest(RequestModel $target, User|StaffAdmin $actor): void
    {
        $type = $this->actorType($actor);
        Message::where('request_id', $target->id)
            ->where(fn ($q) => $q->where('sender_id', '!=', $actor->getKey())->orWhere('sender_type', '!=', $type))
            ->whereDoesntHave('reads', fn ($q) => $q->where('reader_id', $actor->getKey())->where('reader_type', $type))
            ->each(fn (Message $message) => MessageRead::firstOrCreate([
                'message_id' => $message->id, 'reader_id' => $actor->getKey(), 'reader_type' => $type,
            ], ['read_at' => now()]));
    }

    private function present(Message $message, User|StaffAdmin $actor): array
    {
        $sender = $message->sender_type === 'student' ? User::find($message->sender_id) : StaffAdmin::find($message->sender_id);

        return [
            'id' => (string) $message->id,
            'content' => $message->content,
            'sender' => ['id' => (string) $message->sender_id, 'name' => $sender?->name ?? 'Usuário', 'role' => $message->sender_type === 'student' ? 'student' : ($sender?->role ?? 'staff')],
            'is_mine' => $message->sender_id === $actor->getKey() && $message->sender_type === $this->actorType($actor),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
