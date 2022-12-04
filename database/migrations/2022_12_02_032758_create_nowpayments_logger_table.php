<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * This file is part of the Laravel NOWPayments package.
 *
 * (c) Prevail Ejimadu <prevailexcellent@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CreateNowpaymentsLoggerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nowpayments_api_call_logger', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->integer('count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nowpayments_api_call_logger');
    }
};
