<?php

namespace App\Services;

use App\Enums\AttendanceMethod;
use App\Enums\MemberStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;

class AttendanceService
{
    /**
     * Find a member from a scanned QR payload ("GYM:<token>"), a raw token or a member code.
     */
    public function resolveMember(string $input): ?Member
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        $token = str_starts_with($input, 'GYM:') ? substr($input, 4) : $input;

        return Member::query()->where('check_in_token', $token)->first()
            ?? Member::query()->where('member_code', strtoupper($input))->first();
    }

    public function checkIn(Member $member, AttendanceMethod $method = AttendanceMethod::Manual, ?string $notes = null): Attendance
    {
        if ($member->status !== MemberStatus::Active) {
            throw new BusinessRuleException("{$member->full_name}'s account is {$member->status->label()}.");
        }

        $requireMembership = (bool) (tenant()?->setting('attendance.require_active_membership', true) ?? true);

        if ($requireMembership && ! Membership::query()->where('member_id', $member->id)->currentlyActive()->exists()) {
            throw new BusinessRuleException("{$member->full_name} has no active membership. Renew it to check in.");
        }

        $open = Attendance::query()->where('member_id', $member->id)->today()->open()->first();

        if ($open) {
            throw new BusinessRuleException("{$member->full_name} is already checked in since ".format_time($open->checked_in_at).'.');
        }

        $attendance = new Attendance([
            'member_id' => $member->id,
            'checked_in_at' => now(),
            'method' => $method,
            'recorded_by' => auth()->id(),
            'notes' => $notes,
        ]);
        $attendance->tenant_id = $member->tenant_id;
        $attendance->save();

        return $attendance;
    }

    public function checkOut(Attendance $attendance): Attendance
    {
        if ($attendance->checked_out_at) {
            throw new BusinessRuleException('This visit has already been checked out.');
        }

        $attendance->update(['checked_out_at' => now()]);

        return $attendance;
    }

    /**
     * Toggle: check out if the member is in the gym, otherwise check in.
     *
     * @return array{attendance: Attendance, action: 'in'|'out'}
     */
    public function toggle(Member $member, AttendanceMethod $method): array
    {
        $open = Attendance::query()->where('member_id', $member->id)->today()->open()->first();

        if ($open) {
            return ['attendance' => $this->checkOut($open), 'action' => 'out'];
        }

        return ['attendance' => $this->checkIn($member, $method), 'action' => 'in'];
    }
}
