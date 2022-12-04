<?php

use Illuminate\Support\Facades\Route;
use PrevailExcel\Nowpayments\Http\Controllers\DashboardController;
use PrevailExcel\Nowpayments\Http\Middleware\Authorize;

/*
 * This file is part of the Laravel NOWPayments package.
 *
 * (c) Prevail Ejimadu <prevailexcellent@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Route::group([
	'prefix'  =>  config('nowpayments.path', 'laravel-nowpayments'),
	'middleware' => config('nowpayments.middleware', [Authorize::class]),
], function () {
	Route::get('/', DashboardController::class)->name('nowpayments.dashboard');
});
