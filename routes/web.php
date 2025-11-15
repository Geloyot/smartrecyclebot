<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WasteObjectController; // adjust namespace if placed elsewhere
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

/*
    Emergency deployment route check
*/

// Emergency debug route
Route::get('/test', function () {
    return 'Laravel is working!';
});

Route::get('/env-check', function () {
    try {
        return response()->json([
            'app_key' => env('APP_KEY') ? 'SET' : 'MISSING',
            'db_connection' => env('DB_CONNECTION'),
            'database_url' => env('DATABASE_URL') ? 'SET' : 'MISSING',
            'can_connect_db' => DB::connection()->getPdo() ? 'YES' : 'NO',
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::get('/view-test', function () {
    return view('welcome'); // Test if views work
});

Route::get('/session-test', function () {
    session(['test' => 'working']);
    return 'Session: ' . session('test');
});

Route::get('/migrations-check', function () {
    try {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        return response()->json([
            'tables' => collect($tables)->pluck('tablename'),
            'users_count' => DB::table('users')->count(),
            'bins_count' => DB::table('bins')->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::get('/login-debug', function () {
    try {
        return view('livewire.auth.login'); // Or wherever your login view is
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
});

/*
    PageController Routes for handling pages
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/welcome', [PageController::class, 'welcome'])->name('welcome');
Route::get('/admin', [PageController::class, 'admin']);
Route::middleware(['auth'])->group (function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/bin-monitoring', [PageController::class, 'bin_monitoring'])->name('bin_monitoring');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/classification', [PageController::class, 'classification'])->name('classification');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/admin/user-management', [PageController::class, 'user_management'])->name('user_management');
});
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

/*
    ExportController Routes for handling report generations
*/
Route::middleware(['auth'])->group(function() {
    Route::get('/users/export/pdf', [ExportController::class, 'exportPdfUser'])->name('users_export.pdf');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/notifications/export/pdf', [ExportController::class, 'exportPdfNotification'])->name('notifications_export.pdf');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/bin_readings/export/pdf', [ExportController::class, 'exportPdfBinReading'])->name('bin_readings_export.pdf');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/waste_objects/export/pdf', [ExportController::class, 'exportPdfClassification'])->name('classifications_export.pdf');
});

Route::middleware(['auth'])->group(function() {
    Route::get('/users/export/csv', [ExportController::class, 'exportCsvUser'])->name('users_export.csv');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/notifications/export/csv', [ExportController::class, 'exportCsvNotification'])->name('notifications_export.csv');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/bin_readings/export/csv', [ExportController::class, 'exportCsvBinReading'])->name('bin_readings_export.csv');
});
Route::middleware(['auth'])->group(function() {
    Route::get('/waste_objects/export/csv', [ExportController::class, 'exportCsvClassification'])->name('classifications_export.csv');
});

/*
    NotificationController Routes for handling automated notifications and alerts
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
});
Route::middleware(['auth'])->group(function () {
    Route::get ('/notifications/recent',    [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});
Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
     ->name('notifications.unreadCount');

/*
    WasteObjectController Routes for segregation via object detection and classification
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/classifications/{id}', [WasteObjectController::class, 'show'])->name('classifications.show');
});

Route::post('/camera/start', [WasteObjectController::class, 'cameraStart'])
    ->name('camera.start');

Route::post('/camera/stop', [WasteObjectController::class, 'cameraStop'])
    ->name('camera.stop');

Route::get('/camera/status', [WasteObjectController::class, 'cameraStatus'])
    ->name('camera.status');

/*
    AdminController Routes for CRUD Masterfile functionalities
*/
Route::post('/admin/user-add', [AdminController::class, 'user_add_open']);
Route::post('/admin/user-edit', [AdminController::class, 'user_edit_open']);
Route::post('/admin/user-deactivate', [AdminController::class, 'user_deactivate_open']);

Route::post('/admin/user-add', [DataController::class, 'userAdd']);
Route::post('/admin/user-edit/{id}', [DataController::class, 'userEdit']);
Route::post('/admin/user-deactivate/{id}', [DataController::class, 'userDeactivate']);

/*
    Health routes for deployed version
*/
Route::any('/health', fn() => 'ok');

require __DIR__.'/auth.php';
