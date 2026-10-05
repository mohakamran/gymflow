<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:50'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['event'] ?? null, fn ($query, string $event) => $query->whereLike('event', $event.'%'))
            ->when($filters['user'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'eventGroups' => ['auth' => 'Authentication', 'tenant' => 'Gym settings', 'user' => 'Users'],
        ]);
    }
}
