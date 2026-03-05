<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

// Simple test route that bypasses sessions
Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'Server is running']);
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
]);

Route::get('/ping', function () {
    return 'ok';
});

Route::get('/auth/google/redirect', [ApiAuthController::class, 'googleRedirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [ApiAuthController::class, 'googleCallback'])->name('auth.google.callback');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);
    if (! $user->email) {
        return redirect('/login?verification=invalid');
    }

    if (! $request->hasValidSignature()) {
        return redirect('/login?verification=invalid');
    }

    $expected = sha1($user->getEmailForVerification());
    if (! hash_equals($expected, (string) $hash)) {
        return redirect('/login?verification=invalid');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();
    }

    return redirect('/verify/success');
})->name('verification.verify');

// Password reset routes
Route::get('/password/reset/{token}', function ($token) {
    return view('app');
})->name('password.reset');

Route::get('/password/reset', function () {
    return view('app');
})->name('password.request');

Route::get('/auth/select-role', function () {
    return view('app');
})->name('auth.select-role');

Route::get('/forgot-password', function () {
    return view('app');
})->name('password.request.page');

Route::view('/', 'app');

Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$');
