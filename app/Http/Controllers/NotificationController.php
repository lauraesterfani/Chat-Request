<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationRecord;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\RequestAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->visibleNotifications($request)->latest();
        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json($query->paginate(min(max((int) $request->input('per_page', 20), 1), 100)));
    }

    public function count(Request $request)
    {
        return response()->json(['unread' => $this->visibleNotifications($request)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, string $id)
    {
        $notification = $this->visibleNotifications($request)->whereKey($id)->firstOrFail();
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function readAll(Request $request)
    {
        $this->visibleNotifications($request)->whereNull('read_at')->where('created_at', '<=', $request->input('until', now()))->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificações marcadas como lidas.']);
    }

    public function preferences(Request $request)
    {
        [$type, $id] = $this->recipient($request);
        if ($request->isMethod('get')) {
            return response()->json(NotificationPreference::where(['recipient_type' => $type, 'recipient_id' => (string) $id])->get());
        }
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', NotificationRecord::CATEGORIES)],
            'internal_enabled' => ['boolean'],
            'email_enabled' => ['boolean'],
        ]);
        if (($data['email_enabled'] ?? false) && ! $this->hasVerifiedEmail($request)) {
            return response()->json([
                'message' => 'Confirme seu endereço de e-mail antes de ativar notificações por e-mail.',
                'email_verification_required' => true,
            ], 422);
        }
        $preference = NotificationPreference::updateOrCreate(['recipient_type' => $type, 'recipient_id' => (string) $id, 'category' => $data['category']], $data);

        return response()->json($preference);
    }

    private function recipient(Request $request): array
    {
        $actor = $this->actor($request);

        return [$actor instanceof User ? 'student' : 'staff_admin', $actor->getKey()];
    }

    private function actor(Request $request): User|StaffAdmin
    {
        $actor = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
        abort_unless($actor instanceof User || $actor instanceof StaffAdmin, 401);
        abort_if($actor instanceof StaffAdmin && ! $actor->isActive(), 403);

        return $actor;
    }

    private function visibleNotifications(Request $request)
    {
        [$type, $id] = $this->recipient($request);
        $actor = $this->actor($request);
        $query = NotificationRecord::query()
            ->where('recipient_type', $type)
            ->where('recipient_id', (string) $id)
            ->where(function ($notifications) use ($actor): void {
                $notifications->whereNull('request_id');
                $visibleRequests = RequestModel::query();
                if ($actor instanceof User) {
                    $visibleRequests->where('user_id', $actor->getKey());
                } else {
                    app(RequestAccessService::class)->constrainVisibleRequests($visibleRequests, $actor);
                }
                $notifications->orWhereIn('request_id', $visibleRequests->select('requests.id'));
            });

        return $query;
    }

    private function hasVerifiedEmail(Request $request): bool
    {
        $actor = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();

        return $actor instanceof User || $actor instanceof StaffAdmin
            ? filled($actor->email_verified_at ?? null)
            : false;
    }
}
