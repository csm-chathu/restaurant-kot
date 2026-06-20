<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTransactionalData extends Command
{
    protected $signature = 'data:clear-transactions
                            {--branch= : Only clear data for a specific branch_id (leave blank for ALL branches)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Clear transactional data (sales, shifts, accounting) while keeping products, stock, users, customers, and settings.';

    // Tables wiped in order (children before parents to respect FK constraints)
    private array $tables = [
        'sale_items',
        'sale_payments',
        'bottle_deposits',
        'open_bottles',
        'stock_movements',
        'audit_logs',
        'day_end_reports',
        'journal_entries',
        'cashier_shift_cash_outs',
        'cashier_shifts',
        'sales',
    ];

    // Tables with no branch_id column — need special handling
    private array $nobranchTables = [
        'journal_entries',
    ];

    public function handle(): int
    {
        $branchId = $this->option('branch');

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>TRANSACTIONAL DATA CLEAR</>');
        $this->line('  Tables to be cleared: <fg=cyan>' . implode(', ', $this->tables) . '</>');
        $this->line('  Scope: <fg=cyan>' . ($branchId ? "branch_id = {$branchId}" : 'ALL branches') . '</>');
        $this->newLine();
        $this->line('  <fg=gray>PRESERVED: products, stock quantities, categories, customers,');
        $this->line('  users, suppliers, branches, settings, gold_rates, tables, grns, purchases</>');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('  Are you sure? This cannot be undone.', false)) {
                $this->line('  Aborted.');
                return 0;
            }
            if (!$this->confirm('  Confirm again — all sales and shift data will be permanently deleted.', false)) {
                $this->line('  Aborted.');
                return 0;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tables as $table) {
            try {
                if ($branchId && !in_array($table, $this->nobranchTables) && DB::getSchemaBuilder()->hasColumn($table, 'branch_id')) {
                    $count = DB::table($table)->where('branch_id', $branchId)->delete();
                    $this->line("  <fg=green>✓</> {$table} — {$count} rows deleted (branch {$branchId})");
                } elseif ($branchId && in_array($table, $this->nobranchTables)) {
                    $saleIds = DB::table('sales')->where('branch_id', $branchId)->pluck('id');
                    $count = DB::table($table)
                        ->where('source_type', 'sale')
                        ->whereIn('source_id', $saleIds)
                        ->delete();
                    $this->line("  <fg=green>✓</> {$table} — {$count} rows deleted");
                } else {
                    DB::table($table)->truncate();
                    $this->line("  <fg=green>✓</> {$table} — truncated");
                }
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> {$table} — {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->line('  <fg=green;options=bold>Done. Sales and shift data cleared. Stock and products unchanged.</>');
        $this->newLine();

        return 0;
    }
}
