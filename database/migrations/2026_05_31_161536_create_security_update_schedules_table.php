<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'security_update_schedules',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('security_id');

                $table->unsignedBigInteger('security_update_type_id');

                $table->unsignedTinyInteger('run_day');

                $table->unsignedTinyInteger('run_hour');

                $table->timestamp('last_run_at')->nullable();

                $table->unsignedBigInteger('status_id');

                $table->timestamps();

                // indexes

                $table->unique(
                    [
                        'security_id',
                        'security_update_type_id',
                    ],
                    'sus_sec_type_unique'
                );

                $table->index(
                    [
                        'security_update_type_id',
                        'run_day',
                        'run_hour',
                        'status_id',
                    ],
                    'sus_run_sched_idx'
                );

                $table->index(
                    'security_id'
                );

                $table->index(
                    'status_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_update_schedules'
        );
    }
};
