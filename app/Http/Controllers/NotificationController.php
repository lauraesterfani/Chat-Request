<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationRecord;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        [$type, $id] = $this->recipient($request);
        $query = NotificationRecord::where('recipient_type', $type)->where('recipient_id', (string) $id)->latest();
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
        [$type, $id] = $this->recipient($request);

        return response()->json(['unread' => NotificationRecord::where(['recipient_type' => $type, 'recipient_id' => (string) $id])->whereNull('read_at')->count()]);
    }

    public function read(Request $request, string $id)
    {
        [$type, $recipientId] = $this->recipient($request);
        $notification = NotificationRecord::where(['id' => $id, 'recipient_type' => $type, 'recipient_id' => (string) $recipientId])->firstOrFail();
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function readAll(Request $request)
    {
        [$type, $id] = $this->recipient($request);
        NotificationRecord::where(['recipient_type' => $type, 'recipient_id' => (string) $id])->whereNull('read_at')->where('created_at', '<=', $request->input('until', now()))->update(['read_at' => now()]);

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
        $preference = NotificationPreference::updateOrCreate(['recipient_type' => $type, 'recipient_id' => (string) $id, 'category' => $data['category']], $data);

        return response()->json($preference);
    }

    private function recipient(Request $request): array
    {
        $actor = Auth::guard('api')->user() ?? Auth::guard('staff_admins')->user() ?? $request->user();
        abort_unless($actor instanceof User || $actor instanceof StaffAdmin, 401);

        return [$actor instanceof User ? 'student' : 'staff_admin', $actor->getKey()];
    }
}
