<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/download-apk', function () {
    $path = public_path('downloads/bersih-laundry.apk');
    abort_unless(file_exists($path), 404, 'APK belum tersedia.');
    return response()->download($path, 'Bersih-Laundry.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('download.apk');
