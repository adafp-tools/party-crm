<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberVerifyController;
use App\Livewire\Campaigns\Form as CampaignsForm;
use App\Livewire\Campaigns\Index as CampaignsIndex;
use App\Livewire\Dues\Arrears as DuesArrears;
use App\Livewire\Dues\ConfirmQueue as DuesConfirmQueue;
use App\Livewire\Dues\Plans as DuesPlans;
use App\Livewire\Dues\Reconcile as DuesReconcile;
use App\Livewire\Dues\RecordPayment as DuesRecordPayment;
use App\Livewire\Events\Attendance as EventsAttendance;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Install\Wizard as InstallWizard;
use App\Livewire\Members\Form as MembersForm;
use App\Livewire\Members\ImportWizard as MembersImportWizard;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Members\Show as MembersShow;
use App\Livewire\Public\JoinForm;
use App\Livewire\Reports\AuditViewer;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Structure\Tree as StructureTree;
use App\Livewire\Tasks\Form as TasksForm;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Locale switcher
Route::post('locale', function (Request $request) {
    $supported = ['en', 'fr', 'pt', 'ar', 'am'];
    $locale = $request->string('locale')->toString();
    if (in_array($locale, $supported, true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Two-factor authentication
Route::middleware('auth')->group(function () {
    Route::get('two-factor/challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::get('two-factor/setup', [TwoFactorController::class, 'showSetup'])->name('two-factor.setup');
    Route::post('two-factor/setup', [TwoFactorController::class, 'confirmSetup'])->name('two-factor.setup.confirm');
});

// Protected routes
Route::middleware(['auth', 'two-factor'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/structure', StructureTree::class)->name('structure.index');

    Route::get('/users', UsersIndex::class)->name('users.index');

    // Members
    Route::get('/members', MembersIndex::class)->name('members.index');
    Route::get('/members/create', MembersForm::class)->name('members.create');
    Route::get('/members/import', MembersImportWizard::class)->name('members.import');
    Route::get('/members/{member}', MembersShow::class)->name('members.show');
    Route::get('/members/{member}/edit', MembersForm::class)->name('members.edit');
    Route::get('/members/{member}/card', [MemberCardController::class, 'download'])->name('members.card');

    // Events
    Route::get('/events', EventsIndex::class)->name('events.index');
    Route::get('/events/{event}/attendance', EventsAttendance::class)->name('events.attendance');

    // Tasks
    Route::get('/tasks', TasksIndex::class)->name('tasks.index');
    Route::get('/tasks/create', TasksForm::class)->name('tasks.create');
    Route::get('/tasks/{task}/edit', TasksForm::class)->name('tasks.edit');

    // Reports
    Route::get('/reports', ReportsIndex::class)->name('reports.index');
    Route::get('/reports/audit-log', AuditViewer::class)->name('reports.audit-log');
    Route::get('/reports/audit-export', [AuditLogController::class, 'export'])->name('reports.audit-export');

    // Exports
    Route::post('/exports/members', [ExportController::class, 'members'])->name('exports.members');

    // Settings
    Route::get('/settings', SettingsIndex::class)->name('settings.index');

    // Dues redirect (keeps nav link working)
    Route::redirect('/dues', '/dues/plans')->name('dues.index');

    // Campaigns
    Route::get('/campaigns', CampaignsIndex::class)->name('campaigns.index');
    Route::get('/campaigns/create', CampaignsForm::class)->name('campaigns.create');
    Route::get('/campaigns/{campaign}/edit', CampaignsForm::class)->name('campaigns.edit');

    // Dues
    Route::get('/dues/plans', DuesPlans::class)->name('dues.plans');
    Route::get('/dues/record', DuesRecordPayment::class)->name('dues.record');
    Route::get('/dues/record/{member}', DuesRecordPayment::class)->name('dues.record.member');
    Route::get('/dues/confirm', DuesConfirmQueue::class)->name('dues.confirm');
    Route::get('/dues/reconcile', DuesReconcile::class)->name('dues.reconcile');
    Route::get('/dues/arrears', DuesArrears::class)->name('dues.arrears');
});

// Privacy notice (public)
Route::view('/privacy', 'privacy')->name('privacy');

// Health check (public, unauthenticated)
Route::get('/health', HealthController::class)->name('health');

// PWA — serve index.html for all /app/* paths (Vue Router handles routing client-side)
Route::get('/app/{any?}', function () {
    $path = public_path('app/index.html');

    return file_exists($path)
        ? response()->file($path)
        : response('PWA not built yet. Run: cd mobile && npm run build', 503);
})->where('any', '.*')->name('pwa');

// Web installer — only while not installed
Route::get('/install', InstallWizard::class)->name('install')->middleware('web');

// Public join form
Route::get('/{locale}/join', JoinForm::class)
    ->name('join')
    ->where('locale', '[a-z]{2}');

// Public member verification (signed URL)
Route::get('/{locale}/verify/{membership_no}', [MemberVerifyController::class, 'show'])
    ->name('members.verify')
    ->where('locale', '[a-z]{2}');
