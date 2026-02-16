<?php
// routes/web.php
use Illuminate\Support\Facades\Route;

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

Route::view('/', 'app');

Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$');
