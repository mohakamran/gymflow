<?php

namespace App\Http\Controllers\Classes;

use App\Enums\BookingStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\User;
use App\Services\ClassScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function __construct(protected ClassScheduleService $schedule) {}

    /**
     * Weekly timetable.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassSession::class);

        $request->validate(['week' => ['nullable', 'date'], 'trainer' => ['nullable', 'integer']]);
        $weekStart = CarbonImmutable::parse($request->input('week', tenant_today()->toDateString()), tenant_timezone())->startOfWeek();
        $user = $request->user();
        $onlyMine = ! $user->can(Permission::ClassesManage) || $request->boolean('mine');

        $sessions = ClassSession::query()
            ->with(['gymClass', 'trainer'])
            ->withCount('activeBookings')
            ->whereBetween('starts_at', [$weekStart->utc(), $weekStart->endOfWeek()->utc()])
            ->when($request->integer('trainer'), fn ($query, $trainer) => $query->where('trainer_id', $trainer))
            ->when($onlyMine && $user->hasRole(Role::Trainer->value) && ! $user->can(Permission::SettingsManage), fn ($query) => $query->where('trainer_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        return view('classes.schedule', [
            'weekStart' => $weekStart,
            'days' => collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset)),
            'sessionsByDay' => $sessions->groupBy(fn (ClassSession $session) => $session->starts_at->copy()->setTimezone(tenant_timezone())->toDateString()),
            'trainers' => User::query()->role([Role::Trainer->value, Role::Owner->value])->orderBy('name')->pluck('name', 'id'),
            'totalBooked' => $sessions->sum('active_bookings_count'),
            'totalCapacity' => $sessions->sum('capacity'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ClassSession::class);

        return view('classes.session-form', [
            'classes' => GymClass::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedClass' => $request->integer('class') ?: null,
            'trainers' => User::query()->where('is_active', true)->role([Role::Trainer->value, Role::Owner->value])->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ClassSession::class);
        $tenantId = $request->user()->tenant_id;

        $data = $request->validate([
            'gym_class_id' => ['required', 'integer', Rule::exists(GymClass::class, 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'date' => ['required', 'date', 'after_or_equal:'.tenant_today()->subDay()->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'trainer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('tenant_id', $tenantId)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
            'repeat_days' => ['nullable', 'array'],
            'repeat_days.*' => ['integer', 'between:1,7'],
            'repeat_until' => ['nullable', 'required_with:repeat_days', 'date', 'after_or_equal:date', 'before_or_equal:'.tenant_today()->addYear()->toDateString()],
        ]);

        $sessions = $this->schedule->schedule(GymClass::findOrFail($data['gym_class_id']), $data);

        return $this->done(
            redirect()->route('classes.index', ['week' => $sessions->first()->starts_at->copy()->setTimezone(tenant_timezone())->toDateString()]),
            $sessions->count() === 1 ? 'Session scheduled.' : "{$sessions->count()} sessions scheduled.",
        );
    }

    public function show(ClassSession $session): View
    {
        $this->authorize('view', $session);

        return view('classes.session', [
            'session' => $session->load(['gymClass', 'trainer', 'bookings' => fn ($query) => $query->with('member')->orderByRaw('case when status = ? then 1 else 0 end', [BookingStatus::Cancelled->value])->oldest()]),
            'seriesCount' => $session->series_id ? ClassSession::query()->where('series_id', $session->series_id)->upcoming()->count() : 0,
        ]);
    }

    public function cancel(Request $request, ClassSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        if ($request->boolean('series') && $session->series_id) {
            $sessions = ClassSession::query()->where('series_id', $session->series_id)->where('starts_at', '>=', $session->starts_at)->get();
            $sessions->each(fn (ClassSession $item) => $this->schedule->cancelSession($item));

            return $this->done(redirect()->route('classes.index'), "{$sessions->count()} sessions cancelled.");
        }

        $this->schedule->cancelSession($session);

        return $this->done(back(), 'Session cancelled and bookings released.');
    }

    public function book(Request $request, ClassSession $session): RedirectResponse
    {
        Gate::authorize(Permission::AttendanceManage);
        $data = $request->validate(['member_id' => ['required', 'integer', Rule::exists(Member::class, 'id')->where('tenant_id', $request->user()->tenant_id)]]);

        $member = Member::findOrFail($data['member_id']);
        $this->schedule->book($session, $member, byStaff: true);

        return $this->done(back(), "{$member->full_name} booked.");
    }

    public function updateBooking(Request $request, ClassBooking $booking): RedirectResponse
    {
        Gate::authorize(Permission::AttendanceManage);
        $data = $request->validate(['status' => ['required', Rule::enum(BookingStatus::class)]]);

        $status = BookingStatus::from($data['status']);
        $status === BookingStatus::Cancelled ? $this->schedule->cancelBooking($booking) : $booking->update(['status' => $status]);

        return $this->done(back(), 'Booking updated.');
    }
}
