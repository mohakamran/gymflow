<?php

use App\Enums\Permission as P;
use App\Enums\Role;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredGymController;
use App\Http\Controllers\Billing\ExpenseController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Classes\ClassScheduleController;
use App\Http\Controllers\Classes\GymClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Members\AttendanceController;
use App\Http\Controllers\Members\MemberController;
use App\Http\Controllers\Members\MemberRecordController;
use App\Http\Controllers\Members\MembershipController;
use App\Http\Controllers\Members\MembershipPlanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Operations\EnquiryController;
use App\Http\Controllers\Operations\EquipmentController;
use App\Http\Controllers\Operations\TeamController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicGymController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\GymSettingsController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('welcome');
Route::get('sitemap.xml', [PublicGymController::class, 'sitemap'])->name('sitemap');
Route::get('g/{slug}', [PublicGymController::class, 'show'])->name('public.gym');
Route::post('g/{slug}/enquire', [PublicGymController::class, 'enquire'])->middleware('throttle:5,10')->name('public.enquire');

/*
|--------------------------------------------------------------------------
| Guest authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:20,1');

    Route::get('register', [RegisteredGymController::class, 'create'])->name('register');
    Route::post('register', [RegisteredGymController::class, 'store'])->middleware('throttle:registration');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated (any role)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('home', HomeController::class)->name('home');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->middleware('throttle:uploads')->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{id}', [NotificationController::class, 'read'])->whereUuid('id')->name('notifications.read');
    });
});

/*
|--------------------------------------------------------------------------
| Gym workspace (owner, staff, trainer)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'tenant.user'])->group(function () {
    Route::get('dashboard', DashboardController::class)->middleware('permission:'.P::DashboardView)->name('dashboard');

    // Members
    Route::middleware('permission:'.P::MembersView)->group(function () {
        Route::get('members/lookup', [MemberController::class, 'lookup'])->name('members.lookup');
        Route::resource('members', MemberController::class);
        Route::get('members/{member}/card', [MemberController::class, 'card'])->name('members.card');
        Route::post('members/{member}/portal', [MemberController::class, 'invite'])->name('members.portal.invite');
        Route::delete('members/{member}/portal', [MemberController::class, 'revoke'])->name('members.portal.revoke');

        Route::controller(MemberRecordController::class)->prefix('members/{member}')->name('members.')->group(function () {
            Route::post('notes', 'storeNote')->name('notes.store');
            Route::delete('notes/{note}', 'destroyNote')->name('notes.destroy');
            Route::post('documents', 'storeDocument')->middleware('throttle:uploads')->name('documents.store');
            Route::get('documents/{document}', 'downloadDocument')->name('documents.show');
            Route::delete('documents/{document}', 'destroyDocument')->name('documents.destroy');
            Route::post('progress', 'storeProgress')->name('progress.store');
            Route::delete('progress/{record}', 'destroyProgress')->name('progress.destroy');
            Route::get('workouts/create', 'createWorkout')->name('workouts.create');
            Route::post('workouts', 'storeWorkout')->name('workouts.store');
            Route::get('workouts/{workout}', 'showWorkout')->name('workouts.show');
            Route::get('workouts/{workout}/edit', 'editWorkout')->name('workouts.edit');
            Route::put('workouts/{workout}', 'updateWorkout')->name('workouts.update');
            Route::delete('workouts/{workout}', 'destroyWorkout')->name('workouts.destroy');
        });
    });

    // Plans & memberships
    Route::resource('plans', MembershipPlanController::class)->except('show')->middleware('permission:'.P::PlansManage);

    Route::middleware('permission:'.P::MembershipsView)->controller(MembershipController::class)->group(function () {
        Route::get('memberships', 'index')->name('memberships.index');
        Route::get('memberships/create', 'create')->name('memberships.create');
        Route::post('memberships', 'store')->name('memberships.store');
        Route::get('memberships/{membership}/renew', 'renewForm')->name('memberships.renew');
        Route::post('memberships/{membership}/renew', 'renew')->name('memberships.renew.store');
        Route::post('memberships/{membership}/suspend', 'suspend')->name('memberships.suspend');
        Route::post('memberships/{membership}/resume', 'resume')->name('memberships.resume');
        Route::post('memberships/{membership}/cancel', 'cancel')->name('memberships.cancel');
    });

    // Attendance
    Route::middleware('permission:'.P::AttendanceManage)->controller(AttendanceController::class)->group(function () {
        Route::get('attendance', 'index')->name('attendance.index');
        Route::get('attendance/history', 'history')->name('attendance.history');
        Route::get('attendance/kiosk', 'kiosk')->name('attendance.kiosk');
        Route::post('attendance', 'store')->middleware('throttle:120,1')->name('attendance.store');
        Route::patch('attendance/{attendance}/checkout', 'checkout')->name('attendance.checkout');
        Route::delete('attendance/{attendance}', 'destroy')->name('attendance.destroy');
    });

    // Team
    Route::middleware('permission:'.P::StaffManage)->controller(TeamController::class)->group(function () {
        Route::get('team', 'index')->name('staff.index');
        Route::get('team/create', 'create')->name('staff.create');
        Route::post('team', 'store')->name('staff.store');
        Route::get('team/{user}/edit', 'edit')->name('staff.edit');
        Route::put('team/{user}', 'update')->name('staff.update');
        Route::patch('team/{user}/toggle', 'toggle')->name('staff.toggle');
    });

    // Classes & schedule
    Route::middleware('permission:'.P::ClassesView)->group(function () {
        Route::get('classes', [ClassScheduleController::class, 'index'])->name('classes.index');
        Route::get('classes/sessions/create', [ClassScheduleController::class, 'create'])->name('classes.sessions.create');
        Route::post('classes/sessions', [ClassScheduleController::class, 'store'])->name('classes.sessions.store');
        Route::get('classes/sessions/{session}', [ClassScheduleController::class, 'show'])->name('classes.sessions.show');
        Route::post('classes/sessions/{session}/cancel', [ClassScheduleController::class, 'cancel'])->name('classes.sessions.cancel');
        Route::post('classes/sessions/{session}/bookings', [ClassScheduleController::class, 'book'])->name('classes.bookings.store');
        Route::patch('classes/bookings/{booking}', [ClassScheduleController::class, 'updateBooking'])->name('classes.bookings.update');
        Route::resource('classes/types', GymClassController::class)->except('show')->parameters(['types' => 'class'])->names('classes.types');
    });

    // Equipment
    Route::middleware('permission:'.P::EquipmentManage)->group(function () {
        Route::resource('equipment', EquipmentController::class);
        Route::post('equipment/{equipment}/maintenance', [EquipmentController::class, 'logMaintenance'])->name('equipment.maintenance.store');
    });

    // Billing
    Route::middleware('permission:'.P::PaymentsView)->controller(PaymentController::class)->group(function () {
        Route::get('payments', 'index')->name('payments.index');
        Route::get('payments/create', 'create')->name('payments.create');
        Route::post('payments', 'store')->name('payments.store');
        Route::get('payments/{payment}', 'show')->name('payments.show');
        Route::post('payments/{payment}/refund', 'refund')->name('payments.refund');
    });

    Route::middleware('permission:'.P::InvoicesView)->controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'index')->name('invoices.index');
        Route::get('invoices/create', 'create')->name('invoices.create');
        Route::post('invoices', 'store')->name('invoices.store');
        Route::get('invoices/{invoice}', 'show')->name('invoices.show');
        Route::get('invoices/{invoice}/print', 'print')->name('invoices.print');
        Route::get('invoices/{invoice}/pdf', 'pdf')->name('invoices.pdf');
        Route::post('invoices/{invoice}/email', 'email')->middleware('throttle:10,1')->name('invoices.email');
        Route::post('invoices/{invoice}/void', 'void')->name('invoices.void');
    });

    Route::middleware('permission:'.P::ExpensesManage)->group(function () {
        Route::resource('expenses', ExpenseController::class)->except('show');
        Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');
    });

    // Reports
    Route::middleware('permission:'.P::ReportsView)->controller(ReportController::class)->group(function () {
        Route::get('reports', 'index')->name('reports.index');
        Route::get('reports/{type}', 'show')->name('reports.show');
        Route::get('reports/{type}/export/{format}', 'export')->name('reports.export');
    });

    // Communication
    Route::resource('announcements', AnnouncementController::class)->except('show')->middleware('permission:'.P::AnnouncementsManage);

    Route::middleware('permission:'.P::MembersManage)->group(function () {
        Route::get('leads', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::patch('leads/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');
    });

    // Settings
    Route::middleware('permission:'.P::SettingsManage)->prefix('settings')->name('settings.')->group(function () {
        Route::controller(GymSettingsController::class)->group(function () {
            Route::get('/', fn () => redirect()->route('settings.profile.edit'))->name('index');
            Route::get('profile', 'editProfile')->name('profile.edit');
            Route::put('profile', 'updateProfile')->name('profile.update');
            Route::get('branding', 'editBranding')->name('branding.edit');
            Route::put('branding', 'updateBranding')->middleware('throttle:uploads')->name('branding.update');
            Route::get('hours', 'editHours')->name('hours.edit');
            Route::put('hours', 'updateHours')->name('hours.update');
            Route::get('localization', 'editLocalization')->name('localization.edit');
            Route::put('localization', 'updateLocalization')->name('localization.update');
        });
        Route::get('notifications', [NotificationSettingsController::class, 'edit'])->name('notifications.edit');
        Route::put('notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');
        Route::get('billing', [BillingController::class, 'edit'])->name('billing.edit');
        Route::post('billing', [BillingController::class, 'requestChange'])->middleware('throttle:5,1')->name('billing.request');
    });

    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:'.P::AuditView)->name('audit-logs.index');
});

/*
|--------------------------------------------------------------------------
| Member portal
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'tenant.user', 'permission:'.P::PortalAccess])->prefix('portal')->name('portal.')->controller(PortalController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('membership', 'membership')->name('membership');
    Route::get('billing', 'billing')->name('billing');
    Route::get('invoices/{invoice}/pdf', 'invoice')->name('invoices.pdf');
    Route::get('attendance', 'attendance')->name('attendance');
    Route::get('classes', 'classes')->name('classes');
    Route::post('classes/{session}/book', 'book')->middleware('throttle:20,1')->name('classes.book');
    Route::delete('bookings/{booking}', 'cancelBooking')->name('bookings.cancel');
    Route::get('workouts', 'workouts')->name('workouts');
    Route::get('workouts/{workout}', 'workout')->name('workouts.show');
    Route::get('progress', 'progress')->name('progress');
    Route::get('announcements', 'announcements')->name('announcements');
    Route::get('notifications', 'notifications')->name('notifications');
});

/*
|--------------------------------------------------------------------------
| Platform administration (super admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:'.Role::SuperAdmin->value])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('gyms', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('gyms/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::patch('gyms/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::get('users', [PlatformController::class, 'users'])->name('users.index');
    Route::patch('users/{user}/toggle', [PlatformController::class, 'toggleUser'])->whereNumber('user')->name('users.toggle');
    Route::get('plans', [PlatformController::class, 'plans'])->name('plans.index');
    Route::patch('plan-requests/{planRequest}', [PlatformController::class, 'resolveRequest'])->whereNumber('planRequest')->name('plans.resolve');
    Route::get('activity', [PlatformController::class, 'activity'])->name('activity.index');
});
