<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop name column and add first_name, last_name
            $table->dropColumn('name');
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');

            // Personal information fields
            $table->integer('age')->nullable()->after('last_name');
            $table->string('cin')->nullable()->after('age');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('cin');
            $table->string('avatar')->nullable()->after('gender');
            $table->string('phone')->nullable()->after('avatar');

            // Address fields
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code')->nullable()->after('country');

            // Employment fields
            $table->string('employee_id')->unique()->nullable()->after('postal_code');
            $table->date('hire_date')->nullable()->after('employee_id');
            $table->decimal('salary', 10, 2)->nullable()->after('hire_date');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('salary');
            $table->foreignId('store_id')->nullable()->after('status')->constrained('stores')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'age',
                'cin',
                'gender',
                'avatar',
                'phone',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
                'employee_id',
                'hire_date',
                'salary',
                'status',
                'store_id',
            ]);
            $table->string('name')->after('id');
        });
    }
};
