<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'verlogs2026') {
        abort(403, 'Unauthorized access');
    }

    $logPath = storage_path('logs/laravel.log');

    if (!\Illuminate\Support\Facades\File::exists($logPath)) {
        return response('Log file not found.', 404);
    }

    return response(\Illuminate\Support\Facades\File::get($logPath), 200, [
        'Content-Type' => 'text/plain'
    ]);
});
