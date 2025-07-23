<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->timestamp('whatsapp_reminder_sent_at')->nullable()->after('estimated_call_time');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // Cek dulu sebelum drop
            if (Schema::hasColumn('queues', 'whatsapp_reminder_sent_at')) {
                $table->dropColumn('whatsapp_reminder_sent_at');
            }
        });
    }
};