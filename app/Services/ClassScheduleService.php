<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ClassSessionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Membership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassScheduleService
{
    public const MAX_SESSIONS_PER_SERIES = 200;

    /**
     * Schedule one session, or a weekly series when repeat days are given.
     * Times are entered in the gym's timezone and stored in UTC.
     *
     * @param  array{date: string, start_time: string, trainer_id?: int|null, capacity?: int|null, location?: string|null, repeat_days?: list<int>|null, repeat_until?: string|null}  $data
     * @return Collection<int, ClassSession>
     */
    public function schedule(GymClass $class, array $data): Collection
    {
        $timezone = tenant_timezone();
        $firstDate = CarbonImmutable::parse($data['date'], $timezone);
        $repeatDays = array_map('intval', $data['repeat_days'] ?? []);
        $until = ! empty($data['repeat_until']) && $repeatDays !== []
            ? CarbonImmutable::parse($data['repeat_until'], $timezone)
            : $firstDate;

        $dates = collect();

        for ($day = $firstDate; $day->lte($until); $day = $day->addDay()) {
            if ($repeatDays === [] ? $day->isSameDay($firstDate) : in_array($day->dayOfWeekIso, $repeatDays, true)) {
                $dates->push($day);
            }

            if ($dates->count() > self::MAX_SESSIONS_PER_SERIES) {
                throw new BusinessRuleException('A series can contain at most '.self::MAX_SESSIONS_PER_SERIES.' sessions. Choose an earlier end date.');
            }
        }

        if ($dates->isEmpty()) {
            throw new BusinessRuleException('No sessions fall on the selected days. Check the repeat days and dates.');
        }

        $seriesId = $dates->count() > 1 ? (string) Str::uuid() : null;

        return DB::transaction(fn () => $dates->map(function (CarbonImmutable $date) use ($class, $data, $timezone, $seriesId): ClassSession {
            $startsAt = CarbonImmutable::parse($date->toDateString().' '.$data['start_time'], $timezone);

            $session = new ClassSession([
                'gym_class_id' => $class->id,
                'trainer_id' => $data['trainer_id'] ?? $class->trainer_id,
                'series_id' => $seriesId,
                'starts_at' => $startsAt->utc(),
                'ends_at' => $startsAt->addMinutes($class->duration_minutes)->utc(),
                'capacity' => $data['capacity'] ?? $class->capacity,
                'location' => $data['location'] ?? $class->location,
                'status' => ClassSessionStatus::Scheduled,
            ]);
            $session->tenant_id = $class->tenant_id;
            $session->save();

            return $session;
        }));
    }

    public function book(ClassSession $session, Member $member, bool $byStaff = false): ClassBooking
    {
        $session->loadMissing('gymClass');

        if (! $session->isBookable()) {
            throw new BusinessRuleException('This class is no longer open for booking.');
        }

        if (! $byStaff && ! $session->gymClass->allow_member_booking) {
            throw new BusinessRuleException('Please ask the front desk to book this class for you.');
        }

        $membership = Membership::query()->where('member_id', $member->id)->currentlyActive()->with('plan')->latest('ends_on')->first();

        if ($membership === null) {
            throw new BusinessRuleException('An active membership is required to book classes.');
        }

        $this->ensureWithinWeeklyLimit($membership, $member, $session);

        return DB::transaction(function () use ($session, $member): ClassBooking {
            $locked = ClassSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $taken = $locked->bookings()->whereIn('status', BookingStatus::occupying())->count();

            $existing = ClassBooking::query()->where('class_session_id', $session->id)->where('member_id', $member->id)->first();

            if ($existing && in_array($existing->status->value, BookingStatus::occupying(), true)) {
                throw new BusinessRuleException("{$member->full_name} is already booked into this class.");
            }

            if ($taken >= $locked->capacity) {
                throw new BusinessRuleException('Sorry, this class is full.');
            }

            if ($existing) {
                $existing->update(['status' => BookingStatus::Booked]);

                return $existing;
            }

            $booking = new ClassBooking(['class_session_id' => $session->id, 'member_id' => $member->id, 'status' => BookingStatus::Booked]);
            $booking->tenant_id = $session->tenant_id;
            $booking->save();

            return $booking;
        });
    }

    public function cancelBooking(ClassBooking $booking): ClassBooking
    {
        if ($booking->status !== BookingStatus::Booked) {
            throw new BusinessRuleException('Only upcoming bookings can be cancelled.');
        }

        $booking->update(['status' => BookingStatus::Cancelled]);

        return $booking;
    }

    public function cancelSession(ClassSession $session): ClassSession
    {
        $session->update(['status' => ClassSessionStatus::Cancelled]);
        $session->bookings()->where('status', BookingStatus::Booked->value)->update(['status' => BookingStatus::Cancelled->value]);

        return $session;
    }

    protected function ensureWithinWeeklyLimit(Membership $membership, Member $member, ClassSession $session): void
    {
        $limit = $membership->plan?->class_limit_per_week;

        if ($limit === null) {
            return;
        }

        $local = $session->starts_at->copy()->setTimezone(tenant_timezone());
        $weekStart = CarbonImmutable::parse($local->toDateString(), tenant_timezone())->startOfWeek();

        $bookedThisWeek = ClassBooking::query()
            ->where('member_id', $member->id)
            ->whereIn('status', BookingStatus::occupying())
            ->whereHas('session', fn ($query) => $query->whereBetween('starts_at', [$weekStart->utc(), $weekStart->endOfWeek()->utc()]))
            ->count();

        if ($bookedThisWeek >= $limit) {
            throw new BusinessRuleException("The {$membership->plan->name} plan includes {$limit} classes per week, and that limit has been reached.");
        }
    }
}
