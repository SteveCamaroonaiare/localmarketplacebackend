<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modifier la colonne sender_type
        DB::statement("ALTER TABLE messages MODIFY COLUMN sender_type VARCHAR(20) NOT NULL DEFAULT 'customer'");
    }

    public function down()
    {
        // Retour à l'état précédent si nécessaire
        DB::statement("ALTER TABLE messages MODIFY COLUMN sender_type VARCHAR(20) NOT NULL");
    }
};