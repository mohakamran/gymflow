<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementAudience;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Announcement::class);

        return view('announcements.index', ['announcements' => Announcement::query()->with('author')->orderByDesc('is_pinned')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('announcements.form', ['announcement' => new Announcement(['audience' => AnnouncementAudience::Everyone])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);
        $data = $this->validated($request);

        $announcement = Announcement::create($data + ['created_by' => $request->user()->id, 'published_at' => now()]);
        $sent = $request->boolean('send_email') ? $this->broadcast($announcement) : 0;

        return $this->done(redirect()->route('announcements.index'), $sent ? "Announcement published and emailed to {$sent} people." : 'Announcement published.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);
        $announcement->update($this->validated($request));

        return $this->done(redirect()->route('announcements.index'), 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();

        return $this->done(redirect()->route('announcements.index'), 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $request->merge(['is_pinned' => $request->boolean('is_pinned')]);

        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'is_pinned' => ['boolean'],
        ]);
    }

    protected function broadcast(Announcement $announcement): int
    {
        $notification = new AnnouncementNotification(tenant(), $announcement);
        $count = 0;

        if ($announcement->audience !== AnnouncementAudience::Staff) {
            Member::query()->where('status', 'active')->whereNotNull('email')->chunkById(200, function ($members) use ($notification, &$count): void {
                Notification::send($members, $notification);
                $count += $members->count();
            });
        }

        if ($announcement->audience !== AnnouncementAudience::Members) {
            $staff = User::query()->where('is_active', true)->role([Role::Owner->value, Role::Staff->value, Role::Trainer->value])->get();
            Notification::send($staff, $notification);
            $count += $staff->count();
        }

        $announcement->forceFill(['emailed_at' => now()])->save();

        return $count;
    }
}
