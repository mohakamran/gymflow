<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\ProgressRecord;
use App\Models\WorkoutPlan;
use App\Services\ClassScheduleService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Member self-service portal. Every query is pinned to the signed-in member's own record.
 */
class PortalController extends Controller
{
    protected function member(Request $request): Member
    {
        $member = $request->user()->member;
        abort_if($member === null, 403, 'Your login is not linked to a membership yet. Please contact the front desk.');

        return $member;
    }

    public function dashboard(Request $request): View
    {
        $member = $this->member($request)->load(['currentMembership.plan', 'trainer.staffProfile']);

        return view('portal.dashboard', [
            'member' => $member,
            'tenant' => tenant(),
            'visitsThisMonth' => $member->attendances()->betweenDays(tenant_today()->startOfMonth()->toDateString(), tenant_today()->toDateString())->count(),
            'nextBookings' => $member->bookings()->where('status', BookingStatus::Booked->value)
                ->whereHas('session', fn ($query) => $query->where('starts_at', '>=', now()))
                ->with('session.gymClass')->get()->sortBy('session.starts_at')->take(3),
            'balance' => round($member->invoices()->open()->get()->sum(fn ($invoice) => $invoice->balance()), 2),
            'announcements' => Announcement::query()->published()->forMembers()->orderByDesc('is_pinned')->latest('published_at')->limit(3)->get(),
            'activePlan' => $member->workoutPlans()->where('is_active', true)->latest()->first(),
        ]);
    }

    public function membership(Request $request): View
    {
        $member = $this->member($request);

        return view('portal.membership', [
            'member' => $member->load('currentMembership.plan'),
            'memberships' => $member->memberships()->with('plan')->latest('starts_on')->get(),
        ]);
    }

    public function billing(Request $request): View
    {
        $member = $this->member($request);

        return view('portal.billing', [
            'invoices' => $member->invoices()->latest('issued_on')->latest('id')->get(),
            'payments' => $member->payments()->with('invoice')->latest('paid_at')->get(),
        ]);
    }

    public function invoice(Request $request, Invoice $invoice, InvoiceService $invoices): Response
    {
        abort_unless($invoice->member_id === $this->member($request)->id, 404);

        return $invoices->pdf($invoice)->download($invoices->filename($invoice));
    }

    public function attendance(Request $request): View
    {
        $member = $this->member($request);

        return view('portal.attendance', [
            'member' => $member,
            'visits' => $member->attendances()->latest('checked_in_at')->paginate(20),
            'thisMonth' => $member->attendances()->betweenDays(tenant_today()->startOfMonth()->toDateString(), tenant_today()->toDateString())->count(),
            'total' => $member->attendances()->count(),
        ]);
    }

    public function classes(Request $request): View
    {
        $member = $this->member($request);

        $sessions = ClassSession::query()->upcoming()->where('starts_at', '<=', now()->addDays(14))
            ->whereHas('gymClass', fn ($query) => $query->where('is_active', true))
            ->with(['gymClass', 'trainer'])->withCount('activeBookings')->get();

        return view('portal.classes', [
            'member' => $member,
            'sessionsByDay' => $sessions->groupBy(fn (ClassSession $session) => $session->starts_at->copy()->setTimezone(tenant_timezone())->toDateString()),
            'myBookings' => $member->bookings()->whereIn('status', BookingStatus::occupying())->pluck('id', 'class_session_id'),
        ]);
    }

    public function book(Request $request, ClassSession $session, ClassScheduleService $schedule): RedirectResponse
    {
        $schedule->book($session, $this->member($request));

        return $this->done(back(), 'You are booked in! See you there.');
    }

    public function cancelBooking(Request $request, ClassBooking $booking, ClassScheduleService $schedule): RedirectResponse
    {
        abort_unless($booking->member_id === $this->member($request)->id, 404);
        $schedule->cancelBooking($booking);

        return $this->done(back(), 'Booking cancelled.');
    }

    public function workouts(Request $request): View
    {
        return view('portal.workouts', ['plans' => $this->member($request)->workoutPlans()->with('trainer')->orderByDesc('is_active')->latest()->get()]);
    }

    public function workout(Request $request, WorkoutPlan $workout): View
    {
        abort_unless($workout->member_id === $this->member($request)->id, 404);

        return view('portal.workout', ['plan' => $workout->load('trainer')]);
    }

    public function progress(Request $request): View
    {
        return view('portal.progress', [
            'records' => $this->member($request)->progressRecords()->orderBy('recorded_on')->get(),
            'measurements' => ProgressRecord::MEASUREMENTS,
        ]);
    }

    public function announcements(): View
    {
        return view('portal.announcements', ['announcements' => Announcement::query()->published()->forMembers()->orderByDesc('is_pinned')->latest('published_at')->paginate(10)]);
    }

    public function notifications(Request $request): View
    {
        $member = $this->member($request);
        $notifications = $member->notifications()->paginate(20);
        $member->unreadNotifications()->update(['read_at' => now()]);

        return view('portal.notifications', ['notifications' => $notifications]);
    }
}
