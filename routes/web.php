<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client guest routes
|--------------------------------------------------------------------------
*/

Route::livewire('/', 'pages::client.home')->name('home');
Route::livewire('/services', 'pages::client.services.index')->name('client.services');
Route::livewire('/services/{slug}', 'pages::client.services.details')->name('client.services.details');

// Tools
Route::livewire('/tools', 'pages::client.tools.index')->name('client.tools.index');

// Blogs
Route::livewire('/blogs', 'pages::client.blogs.index')->name('client.blogs.index');
Route::livewire('/blogs/{slug}', 'pages::client.blogs.details')->name('client.blogs.details');

//About
Route::livewire('/about', 'pages::client.about')->name('client.about');

// Contact
Route::livewire('/contact', 'pages::client.contact')->name('client.contact');

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::livewire('/email/verify', 'pages::client.auth.verify-email')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (! $request->hasValidSignature()) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    return redirect()->route('verified.success');
})->middleware('signed')->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    if (! auth()->check()) {
        return redirect()->route('home')->with('auth_error', 'Please login first to resend the verification email.');
    }

    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('verified.success');
    }

    $request->user()->sendEmailVerificationNotification();

    return back()->with('auth_success', 'A fresh verification link has been sent to your email address.');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::livewire('/verified-success', 'pages::client.auth.verified-success')->name('verified.success');


/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    // Client password reset
    Route::livewire('/reset-password/{token}', 'pages::client.auth.reset-password')
        ->name('password.reset');

    // Admin password reset
    Route::livewire('/admin/reset-password/{token}', 'pages::admin.auth.reset-password')
        ->name('admin.password.reset');
});


/*
|--------------------------------------------------------------------------
| Client protected routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:client'])->prefix('account')->name('account.')->group(function () {
    Route::livewire('/dashboard', 'pages::client.account.dashboard')->name('dashboard');

    Route::livewire('/services', 'pages::client.account.services')->name('services');
    Route::livewire('/tickets', 'pages::client.account.tickets')->name('tickets');
    Route::livewire('/proposals', 'pages::client.account.proposals')->name('proposals');
    Route::livewire('/profile', 'pages::client.account.profile')->name('profile');
    Route::livewire('/change-password', 'pages::client.account.change-password')->name('password');
});


/*
|--------------------------------------------------------------------------
| Admin guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::livewire('/admin/login', 'pages::admin.auth.login')->name('admin.login');
    Route::livewire('/admin/forgot-password', 'pages::admin.auth.forgot-password')
        ->name('admin.password.request');

    Route::livewire('/admin/reset-password/{token}', 'pages::admin.auth.reset-password')
        ->name('admin.password.reset');
});

/*
|--------------------------------------------------------------------------
| Admin protected routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,manager,staff,admin_manager'])->group(function () {

    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');

    // User management
    Route::livewire('/users', 'pages::admin.users.index')->name('users.index');
    Route::livewire('/users/create', 'pages::admin.users.create')->name('users.create');
    // Route::livewire('/users/{id}/edit', 'pages::admin.users.edit')->name('users.edit');

    // Department management
    Route::livewire('/departments', 'pages::admin.departments.index')->name('departments.index');
    Route::livewire('/departments/create', 'pages::admin.departments.create')->name('departments.create');
    Route::livewire('/departments/{department}/edit', 'pages::admin.departments.edit')->name('departments.edit');
});
