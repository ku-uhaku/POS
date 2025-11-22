<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAuditTrailToTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:add {table : The name of the table} {--soft-delete : Add soft delete support}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add audit trail columns (created_by, updated_by, deleted_by, deleted_at) to a table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tableName = $this->argument('table');
        $addSoftDelete = $this->option('soft-delete');

        if (! Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");

            return Command::FAILURE;
        }

        $this->info("Adding audit trail columns to '{$tableName}' table...");

        try {
            DB::beginTransaction();

            Schema::table($tableName, function ($table) use ($addSoftDelete, $tableName) {
                // Add created_by if it doesn't exist
                if (! Schema::hasColumn($tableName, 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('created_at')
                        ->constrained('users')->nullOnDelete();
                    $this->info('  ✓ Added created_by column');
                } else {
                    $this->warn('  - created_by column already exists');
                }

                // Add updated_by if it doesn't exist
                if (! Schema::hasColumn($tableName, 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('updated_at')
                        ->constrained('users')->nullOnDelete();
                    $this->info('  ✓ Added updated_by column');
                } else {
                    $this->warn('  - updated_by column already exists');
                }

                // Add soft delete if requested and doesn't exist
                if ($addSoftDelete && ! Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                    $this->info('  ✓ Added deleted_at column (soft delete)');
                } elseif ($addSoftDelete) {
                    $this->warn('  - deleted_at column already exists');
                }

                // Add deleted_by if it doesn't exist
                if (! Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->foreignId('deleted_by')->nullable()
                        ->after($addSoftDelete ? 'deleted_at' : 'updated_at')
                        ->constrained('users')->nullOnDelete();
                    $this->info('  ✓ Added deleted_by column');
                } else {
                    $this->warn('  - deleted_by column already exists');
                }
            });

            DB::commit();
            $this->info("\n✓ Audit trail columns added successfully to '{$tableName}' table!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to add audit trail columns: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}

