<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Web\JobPublicController;

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

// Diagnostic route for Hostinger/Deployment storage issues
Route::get('/maintenance/storage-fix', function () {
    $results = [];

    // 1. Run storage:link
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        $results['storage_link_command'] = 'Artisan command ran successfully.';
    } catch (\Exception $e) {
        $results['storage_link_command'] = 'Error: ' . $e->getMessage();
    }

    // 2. Check Paths
    $results['paths'] = [
        'public_path' => public_path(),
        'storage_path' => storage_path('app/public'),
        'exists_public_storage' => is_link(public_path('storage')) ? 'Yes (Symlink)' : (is_dir(public_path('storage')) ? 'Yes (Directory)' : 'No'),
    ];

    // 3. Try manual symlink if standard fails
    if ($results['paths']['exists_public_storage'] === 'No') {
        try {
            symlink(storage_path('app/public'), public_path('storage'));
            $results['manual_symlink'] = 'Manual symlink created.';
        } catch (\Exception $e) {
            $results['manual_symlink'] = 'Error: ' . $e->getMessage();
        }
    }

    // 5. Clean up notification URLs with /build/ prefix
    try {
        $notifications = \App\Models\Notification::where('url', 'like', '/build/%')->get();
        $count = 0;
        foreach ($notifications as $n) {
            $n->url = str_replace('/build/', '/', $n->url);
            $n->save();
            $count++;
        }
        $results['notification_cleanup'] = "Fixed $count notification URLs.";
    } catch (\Exception $e) {
        $results['notification_cleanup'] = 'Error: ' . $e->getMessage();
    }

    return response()->json($results);
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

// Public job page with Open Graph meta tags for social sharing
Route::get('/candidate/jobs/{job}', [JobPublicController::class, 'show'])
    ->middleware('detect.social')
    ->name('jobs.public.show')
    ->whereNumber('job');

Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$');
