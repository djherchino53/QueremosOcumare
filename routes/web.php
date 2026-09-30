<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

Volt::route('/dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Rutas para Médicos, Recepción y Farmacia
    // Rutas para todos los roles autorizados (incluyendo Farmacia para ver pacientes)
    Route::middleware(['role:super_admin,admin,doctor,receptionist,pharmacist'])->group(function () {
        Volt::route('/patients', 'patients.index')->name('patients.index');
        Volt::route('/patients/{patient}', 'patients.show')->name('patients.show');
    });

    // Rutas EXCLUYENDO Farmacia (Citas Médicas)
    Route::middleware(['role:super_admin,admin,doctor,receptionist'])->group(function () {
        Volt::route('/appointments', 'appointments.index')->name('appointments.index');
    });

    // Rutas para Médicos y Admin (NO Recepción, NO Farmacia)
    Route::middleware(['role:super_admin,admin,doctor'])->group(function () {
        Volt::route('/medical-histories', 'medical-histories.index')->name('medical-histories.index');
    });

    // Rutas restringidas (Solo Admin + Farmacéutico para insumos)
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Volt::route('/users', 'users.index')->name('users.index');
        Volt::route('/medical-specialties', 'medical-specialties.index')->name('medical-specialties.index');
        Volt::route('/medical-doctors', 'medical-doctors.index')->name('medical-doctors.index');
        Volt::route('/cash', 'cash.index')->name('cash.index');
        Volt::route('/reports', 'reports.index')->name('reports.index');
    });

    Route::middleware(['role:doctor,admin,super_admin'])->group(function () {
        Volt::route('/medical-profile/{user?}', 'medical-doctors.profile')->name('medical-doctors.profile');
    });

    Route::middleware(['role:super_admin,admin,pharmacist'])->group(function () {
        Volt::route('/supplies', 'supplies.index')->name('supplies.index');
    });

    Route::post('logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
