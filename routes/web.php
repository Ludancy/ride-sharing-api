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

    $logFiles = glob(storage_path('logs/*.log'));

    if (empty($logFiles)) {
        return response("No log files found in storage/logs.\nCurrent LOG_CHANNEL: " . env('LOG_CHANNEL') . "\nIf LOG_CHANNEL is 'errorlog' or 'stderr', logs won't be saved to files.", 404, ['Content-Type' => 'text/plain']);
    }

    $content = '';
    foreach ($logFiles as $file) {
        $content .= "=== " . basename($file) . " ===\n";
        $content .= \Illuminate\Support\Facades\File::get($file) . "\n\n";
    }

    return response($content, 200, [
        'Content-Type' => 'text/plain'
    ]);
});
