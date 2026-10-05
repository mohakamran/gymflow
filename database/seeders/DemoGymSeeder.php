<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudience;
use App\Enums\AttendanceMethod;
use App\Enums\BookingStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\DurationUnit;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Enums\ExpenseCategory;
use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Enquiry;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\StaffProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ClassScheduleService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SequenceGenerator;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Fills a gym with a year of realistic demo data (members, memberships, invoices, payments,
 * attendance, classes, equipment, expenses). Development only.
 */
class DemoGymSeeder extends Seeder
{
    protected CarbonImmutable $today;

    public function run(Tenant $gym, int $memberCount = 120): void
    {
        fake()->seed(2026);

        app(TenantContext::class)->run($gym, function () use ($gym, $memberCount): void {
            $this->today = tenant_today();

            $gym->forceFill([
                'phone' => '+1 555 0100',
                'address_line' => '120 Market Street',
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
                'website' => 'https://ironpeak.example',
                'settings' => array_replace_recursive($gym->settings ?? [], [
                    'tax' => ['enabled' => true, 'rate' => 8.25, 'label' => 'Sales tax'],
                    'profile' => ['about' => 'Iron Peak Fitness is a community gym with 24 classes a week, certified coaches and a fully equipped strength floor. First visit is on us.'],
                    'onboarding' => ['hours_confirmed' => true],
                ]),
            ])->save();

            $trainers = $this->team($gym);
            $plans = $this->plans();
            $members = $this->members($gym, $plans, $trainers, $memberCount);
            $this->attendance($members);
            $this->classes($gym, $trainers, $members);
            $this->equipment();
            $this->expenses();
            $this->communication($gym);
        });
    }

    /**
     * @return Collection<int, User>
     */
    protected function team(Tenant $gym): Collection
    {
        $profiles = [
            'owner@gymflow.test' => ['Owner & Head Coach', 'Strength & conditioning'],
            'staff@gymflow.test' => ['Front Desk Manager', null],
            'trainer@gymflow.test' => ['Personal Trainer', 'Powerlifting, mobility'],
        ];

        foreach ($profiles as $email => [$title, $specialization]) {
            $user = User::query()->where('email', $email)->first();
            $user?->staffProfile()->updateOrCreate([], [
                'tenant_id' => $gym->id,
                'job_title' => $title,
                'specialization' => $specialization,
                'bio' => $specialization ? 'Certified coach with a passion for helping members build lasting habits.' : null,
                'hired_on' => $this->today->subYears(2)->toDateString(),
                'working_hours' => ['monday' => ['start' => '07:00', 'end' => '15:00'], 'wednesday' => ['start' => '07:00', 'end' => '15:00'], 'friday' => ['start' => '12:00', 'end' => '20:00']],
                'is_public' => true,
            ]);
        }

        $extra = collect([['Priya Shah', 'priya@gymflow.test', 'Yoga & Pilates'], ['Marcus Cole', 'marcus@gymflow.test', 'HIIT & CrossFit']])
            ->map(function (array $row) use ($gym): User {
                [$name, $email, $specialization] = $row;
                $user = User::factory()->forTenant($gym, Role::Trainer)->create(['name' => $name, 'email' => $email]);
                $profile = new StaffProfile(['user_id' => $user->id, 'job_title' => 'Coach', 'specialization' => $specialization, 'bio' => "Leads our {$specialization} classes.", 'is_public' => true, 'hired_on' => $this->today->subMonths(8)->toDateString()]);
                $profile->tenant_id = $gym->id;
                $profile->save();

                return $user;
            });

        return User::query()->role(Role::Trainer->value)->get()->merge($extra)->unique('id')->values();
    }

    /**
     * @return Collection<int, MembershipPlan>
     */
    protected function plans(): Collection
    {
        return collect([
            ['Monthly Unlimited', 1, DurationUnit::Month, 59, 25, ['Unlimited gym access', 'All group classes', 'Locker & showers'], null, '#4f46e5'],
            ['Quarterly', 3, DurationUnit::Month, 159, 0, ['Unlimited gym access', 'All group classes', '1 PT intro session'], null, '#0891b2'],
            ['Annual', 1, DurationUnit::Year, 549, 0, ['Best value — 2 months free', 'All group classes', 'Guest passes'], null, '#059669'],
            ['Off-Peak', 1, DurationUnit::Month, 35, 0, ['Gym access 10am–4pm', '4 classes a week'], 4, '#d97706'],
        ])->map(fn (array $row, int $index) => MembershipPlan::create([
            'name' => $row[0], 'duration_value' => $row[1], 'duration_unit' => $row[2], 'price' => $row[3], 'signup_fee' => $row[4],
            'features' => $row[5], 'class_limit_per_week' => $row[6], 'color' => $row[7], 'is_taxable' => true, 'is_active' => true,
            'is_public' => true, 'sort_order' => $index, 'access_hours' => $row[0] === 'Off-Peak' ? 'Weekdays 10am–4pm' : null,
        ]));
    }

    /**
     * Members with membership history, invoices and payments spread over the last year.
     *
     * @param  Collection<int, MembershipPlan>  $plans
     * @param  Collection<int, User>  $trainers
     * @return Collection<int, Member>
     */
    protected function members(Tenant $gym, Collection $plans, Collection $trainers, int $count): Collection
    {
        $sequences = app(SequenceGenerator::class);
        $invoices = app(InvoiceService::class);
        $payments = app(PaymentService::class);
        $methods = [PaymentMethod::Card, PaymentMethod::Card, PaymentMethod::Cash, PaymentMethod::BankTransfer, PaymentMethod::MobileWallet];

        $members = collect(range(1, $count))->map(function (int $i) use ($gym, $sequences, $trainers): Member {
            // Skew joins towards recent months for a growth curve.
            $joined = $this->today->subDays((int) round(365 * (1 - sqrt(fake()->randomFloat(4, 0, 1)))));
            $member = Member::factory()->make(['joined_on' => $joined->toDateString(), 'trainer_id' => $i % 3 === 0 ? $trainers->random()->id : null]);
            $member->tenant_id = $gym->id;
            $member->member_code = $sequences->nextMemberCode($gym);
            $member->save();

            return $member;
        });

        // Link the demo portal login to a member record.
        if ($portalUser = User::query()->where('email', 'member@gymflow.test')->first()) {
            $maya = $members->first();
            $maya->forceFill(['first_name' => 'Maya', 'last_name' => 'Member', 'email' => $portalUser->email, 'user_id' => $portalUser->id, 'trainer_id' => $trainers->first()->id, 'joined_on' => $this->today->subMonths(7)->toDateString()])->save();
        }

        foreach ($members as $index => $member) {
            $plan = $index === 0 ? $plans[0] : $plans->random();
            $start = CarbonImmutable::parse($member->joined_on);
            $first = true;

            // Chain memberships from the joining date; ~75% of members keep renewing.
            while ($start->lte($this->today)) {
                $end = $plan->duration_unit->endDate($start, $plan->duration_value);
                $status = match (true) {
                    $end->lt($this->today) => MembershipStatus::Expired,
                    default => MembershipStatus::Active,
                };

                $membership = new Membership([
                    'member_id' => $member->id, 'membership_plan_id' => $plan->id, 'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString(),
                    'status' => $status, 'price' => $plan->effectivePrice(), 'discount_amount' => fake()->boolean(10) ? 10 : 0,
                    'renewed_from_id' => $first ? null : Membership::query()->where('member_id', $member->id)->latest('id')->value('id'),
                ]);
                $membership->tenant_id = $gym->id;
                $membership->created_at = $start->setTime(10, 0);
                $membership->save();

                $invoice = $invoices->createForMembership($membership);
                $invoice->forceFill(['issued_on' => $start->toDateString(), 'due_on' => $start->addDays(7)->toDateString(), 'created_at' => $start])->save();

                // Most invoices are paid; the newest few are left open or part-paid.
                $recent = $start->gt($this->today->subDays(20));
                $roll = fake()->numberBetween(1, 100);
                $amount = match (true) {
                    $recent && $roll <= 25 => 0,
                    $recent && $roll <= 40 => round((float) $invoice->total / 2, 2),
                    $roll <= 3 => 0,
                    default => (float) $invoice->total,
                };

                if ($amount > 0) {
                    $paidAt = $start->setTime(fake()->numberBetween(7, 20), fake()->numberBetween(0, 59))->utc();
                    $payments->record($member, $amount, fake()->randomElement($methods), $invoice, ['paid_at' => $paidAt, 'notify' => false]);
                }

                $first = false;
                $start = $end->addDay();

                if (! fake()->boolean(78)) {
                    break;
                }
            }
        }

        // A couple of frozen memberships and one refund for realism.
        Membership::query()->where('status', MembershipStatus::Active->value)->inRandomOrder()->limit(2)->get()
            ->each(fn (Membership $m) => $m->forceFill(['status' => MembershipStatus::Suspended, 'suspended_on' => $this->today->subDays(5)->toDateString()])->save());

        if ($refundable = Payment::query()->where('paid_at', '<', now()->subMonths(2))->inRandomOrder()->first()) {
            $payments->refund($refundable, round((float) $refundable->amount / 2, 2), 'Partial refund — moved away');
        }

        return $members;
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    protected function attendance(Collection $members): void
    {
        $active = Membership::query()->with('member')->get()->groupBy('member_id');
        $rows = [];

        for ($day = $this->today->subDays(89); $day->lte($this->today); $day = $day->addDay()) {
            $weekday = $day->isWeekend() ? 0.55 : 1.0;

            foreach ($members as $member) {
                $covered = ($active[$member->id] ?? collect())->contains(fn (Membership $m) => $m->starts_on->lte($day) && $m->ends_on->gte($day));

                if (! $covered || ! fake()->boolean((int) round(32 * $weekday))) {
                    continue;
                }

                $hour = fake()->randomElement([6, 6, 7, 7, 8, 9, 12, 12, 13, 17, 17, 18, 18, 18, 19, 19, 20]);
                $in = $day->setTime($hour, fake()->numberBetween(0, 59));
                $isToday = $day->isSameDay($this->today);
                $out = $isToday && $in->addMinutes(75)->gt(tenant_now()) ? null : $in->addMinutes(fake()->numberBetween(35, 110));

                if ($isToday && $in->gt(tenant_now())) {
                    continue;
                }

                $rows[] = [
                    'tenant_id' => $member->tenant_id, 'member_id' => $member->id,
                    'checked_in_at' => $in->utc(), 'checked_out_at' => $out?->utc(),
                    'method' => fake()->randomElement([AttendanceMethod::Qr->value, AttendanceMethod::Qr->value, AttendanceMethod::Manual->value, AttendanceMethod::Kiosk->value]),
                    'created_at' => $in->utc(), 'updated_at' => $in->utc(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Attendance::insert($chunk);
        }
    }

    /**
     * @param  Collection<int, User>  $trainers
     * @param  Collection<int, Member>  $members
     */
    protected function classes(Tenant $gym, Collection $trainers, Collection $members): void
    {
        $definitions = [
            ['Morning Yoga', '#7c3aed', 60, 18, 'Studio A', [1, 3, 5], '07:00', 'Flow through breath-led sequences to build strength and mobility.'],
            ['HIIT Blast', '#dc2626', 45, 20, 'Turf', [2, 4], '18:00', 'High-intensity intervals — burn, sweat, repeat.'],
            ['Spin', '#0891b2', 45, 16, 'Cycle Room', [1, 3], '18:30', 'Rhythm-based indoor cycling.'],
            ['Strength Fundamentals', '#059669', 60, 12, 'Strength Floor', [2, 6], '10:00', 'Learn the big lifts with expert coaching.'],
            ['Zumba', '#db2777', 50, 25, 'Studio A', [6], '11:30', 'Dance-fitness party.'],
        ];

        $activeMembers = Membership::query()->currentlyActive()->pluck('member_id')->unique()->values();

        foreach ($definitions as $index => [$name, $color, $duration, $capacity, $location, $days, $time, $description]) {
            $class = GymClass::create([
                'name' => $name, 'color' => $color, 'duration_minutes' => $duration, 'capacity' => $capacity, 'location' => $location,
                'description' => $description, 'trainer_id' => $trainers[$index % $trainers->count()]->id, 'is_active' => true, 'allow_member_booking' => true,
            ]);

            $sessions = app(ClassScheduleService::class)->schedule($class, [
                'date' => $this->today->subDays(20)->toDateString(), 'start_time' => $time,
                'repeat_days' => $days, 'repeat_until' => $this->today->addDays(21)->toDateString(),
            ]);

            foreach ($sessions as $session) {
                $past = $session->starts_at->isPast();
                $take = min($session->capacity, fake()->numberBetween((int) ($session->capacity * 0.3), $session->capacity));

                foreach ($activeMembers->shuffle()->take($past ? $take : (int) ($take * 0.6)) as $memberId) {
                    $booking = new ClassBooking([
                        'class_session_id' => $session->id, 'member_id' => $memberId,
                        'status' => $past ? (fake()->boolean(85) ? BookingStatus::Attended : BookingStatus::NoShow) : BookingStatus::Booked,
                    ]);
                    $booking->tenant_id = $gym->id;
                    $booking->save();
                }

                if ($past) {
                    $session->update(['status' => ClassSessionStatus::Completed]);
                }
            }
        }

        ClassSession::query()->where('starts_at', '>', now()->addDays(10))->inRandomOrder()->first()?->update(['status' => ClassSessionStatus::Cancelled]);
    }

    protected function equipment(): void
    {
        $items = [
            ['Commercial Treadmill', EquipmentCategory::Cardio, 6, 4200, 'Cardio Zone'], ['Concept2 Rower', EquipmentCategory::Cardio, 4, 1100, 'Cardio Zone'],
            ['Spin Bike', EquipmentCategory::Cardio, 16, 950, 'Cycle Room'], ['Assault Air Bike', EquipmentCategory::Cardio, 4, 1300, 'Turf'],
            ['Squat Rack', EquipmentCategory::Strength, 4, 1800, 'Strength Floor'], ['Cable Crossover', EquipmentCategory::Strength, 2, 5200, 'Strength Floor'],
            ['Leg Press', EquipmentCategory::Strength, 1, 3900, 'Strength Floor'], ['Dumbbell Set 2.5–50kg', EquipmentCategory::FreeWeights, 1, 6500, 'Strength Floor'],
            ['Olympic Barbell', EquipmentCategory::FreeWeights, 8, 320, 'Strength Floor'], ['Kettlebell Set', EquipmentCategory::Functional, 2, 900, 'Turf'],
            ['Plyo Boxes', EquipmentCategory::Functional, 10, 140, 'Turf'], ['Yoga Mats', EquipmentCategory::Accessories, 30, 25, 'Studio A'],
            ['Sound System', EquipmentCategory::Facility, 1, 2400, 'Studio A'], ['Smith Machine', EquipmentCategory::Strength, 1, 2800, 'Strength Floor'],
        ];

        foreach ($items as $i => [$name, $category, $quantity, $cost, $location]) {
            $status = match ($i) {
                3 => EquipmentStatus::UnderMaintenance, 13 => EquipmentStatus::Damaged, default => EquipmentStatus::Active
            };
            $equipment = Equipment::create([
                'name' => $name, 'category' => $category, 'quantity' => $quantity, 'cost' => $cost, 'location' => $location,
                'purchased_on' => $this->today->subMonths(fake()->numberBetween(4, 36))->toDateString(),
                'serial_number' => strtoupper(fake()->bothify('??-#####')),
                'condition' => fake()->randomElement([EquipmentCondition::New, EquipmentCondition::Good, EquipmentCondition::Good, EquipmentCondition::Fair]),
                'status' => $status,
                'last_maintained_on' => $this->today->subDays(fake()->numberBetween(20, 120))->toDateString(),
                'next_maintenance_on' => $this->today->addDays(fake()->numberBetween(-5, 75))->toDateString(),
            ]);

            $equipment->maintenances()->create([
                'performed_on' => $equipment->last_maintained_on->toDateString(), 'type' => 'service',
                'cost' => fake()->randomElement([0, 45, 80, 150]), 'performed_by' => 'FitServ Technicians',
            ]);
        }
    }

    protected function expenses(): void
    {
        for ($month = $this->today->subMonths(11)->startOfMonth(); $month->lte($this->today); $month = $month->addMonth()) {
            $rows = [
                [ExpenseCategory::Rent, 'Monthly rent', 1500, 'Market St Properties', 1],
                [ExpenseCategory::Utilities, 'Electricity & water', fake()->numberBetween(320, 480), 'City Utilities', 5],
                [ExpenseCategory::Salaries, 'Staff payroll', 1200, 'Payroll', 28],
                [ExpenseCategory::Marketing, 'Social media ads', fake()->numberBetween(120, 300), 'Meta Ads', 10],
                [ExpenseCategory::Maintenance, 'Cleaning service', 180, 'SparkleClean', 15],
            ];

            if (fake()->boolean(35)) {
                $rows[] = [ExpenseCategory::Equipment, 'New equipment', fake()->numberBetween(250, 900), 'Rogue Fitness', 20];
            }

            foreach ($rows as [$category, $title, $amount, $vendor, $day]) {
                $date = $month->setDay(min($day, $month->daysInMonth));

                if ($date->gt($this->today)) {
                    continue;
                }

                Expense::create(['category' => $category, 'title' => $title.' — '.$month->format('M Y'), 'amount' => $amount, 'spent_on' => $date->toDateString(), 'vendor' => $vendor, 'payment_method' => PaymentMethod::BankTransfer]);
            }
        }
    }

    protected function communication(Tenant $gym): void
    {
        $owner = User::query()->role(Role::Owner->value)->first();

        Announcement::create(['title' => 'New Saturday Zumba class!', 'body' => "We're adding a high-energy Zumba session every Saturday at 11:30am in Studio A.\n\nBook from your member portal.", 'audience' => AnnouncementAudience::Everyone, 'is_pinned' => true, 'published_at' => now()->subDays(3), 'created_by' => $owner?->id]);
        Announcement::create(['title' => 'Holiday opening hours', 'body' => 'We will close at 6pm on public holidays this month. Regular hours resume the following day.', 'audience' => AnnouncementAudience::Members, 'published_at' => now()->subDays(10), 'created_by' => $owner?->id]);

        foreach ([['Jordan Blake', 'jordan@example.com', 'Monthly Unlimited', 'new'], ['Sofia Martinez', 'sofia@example.com', 'Yoga classes', 'contacted'], ['Liam Chen', null, 'Personal training', 'new']] as [$name, $email, $interest, $status]) {
            $enquiry = new Enquiry(['name' => $name, 'email' => $email, 'phone' => $email ? null : '+1 555 0199', 'interest' => $interest, 'message' => 'Hi! Could I book a trial visit this week?', 'status' => $status]);
            $enquiry->tenant_id = $gym->id;
            $enquiry->save();
        }

        // Workout plan and progress for the demo portal member.
        $maya = Member::query()->whereNotNull('user_id')->first();

        if ($maya) {
            $maya->workoutPlans()->create([
                'trainer_id' => $maya->trainer_id, 'title' => '8-week strength foundation', 'goal' => 'Build strength & lose 4 kg', 'starts_on' => $this->today->subWeeks(3)->toDateString(), 'ends_on' => $this->today->addWeeks(5)->toDateString(), 'is_active' => true,
                'exercises' => [
                    ['day' => 'Day 1 — Lower', 'name' => 'Back squat', 'sets' => 4, 'reps' => '6-8', 'rest' => '2 min', 'notes' => 'Controlled descent'],
                    ['day' => 'Day 1 — Lower', 'name' => 'Romanian deadlift', 'sets' => 3, 'reps' => '10', 'rest' => '90s', 'notes' => ''],
                    ['day' => 'Day 1 — Lower', 'name' => 'Walking lunges', 'sets' => 3, 'reps' => '12 each', 'rest' => '60s', 'notes' => ''],
                    ['day' => 'Day 2 — Upper', 'name' => 'Bench press', 'sets' => 4, 'reps' => '6-8', 'rest' => '2 min', 'notes' => ''],
                    ['day' => 'Day 2 — Upper', 'name' => 'Seated cable row', 'sets' => 3, 'reps' => '10', 'rest' => '90s', 'notes' => ''],
                    ['day' => 'Day 2 — Upper', 'name' => 'Plank', 'sets' => 3, 'reps' => '45s', 'rest' => '45s', 'notes' => ''],
                ],
            ]);

            foreach (range(6, 0) as $i => $weeksAgo) {
                $maya->progressRecords()->create([
                    'recorded_on' => $this->today->subWeeks($weeksAgo * 2)->toDateString(), 'recorded_by' => $maya->trainer_id,
                    'weight_kg' => 72.5 - $i * 0.6 + fake()->randomFloat(1, -0.3, 0.3), 'body_fat_percent' => 27 - $i * 0.4, 'waist_cm' => 81 - $i * 0.5,
                ]);
            }

            $maya->memberNotes()->create(['user_id' => $maya->trainer_id, 'body' => 'Great progress on squat depth. Watch left knee tracking on lunges.']);
        }
    }
}
