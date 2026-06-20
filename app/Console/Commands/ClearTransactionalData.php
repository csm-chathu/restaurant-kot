<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTransactionalData extends Command
{
    protected $signature = 'data:clear-transactions
                            {--branch= : Only clear data for a specific branch_id (leave blank for ALL branches)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Clear all transactional data (sales, shifts, accounting) while keeping products, users, customers, and settings.';

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

    // Tables that are NOT branch-scoped (always fully truncated)
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
        $this->line('  <fg=gray>PRESERVED: products, categories, customers, users, suppliers,');
        $this->line('  branches, settings, grns, purchases, employees, gold_rates, tables</>');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('  Are you sure? This cannot be undone.', false)) {
                $this->line('  Aborted.');
                return 0;
            }
            if (!$this->confirm('  Type YES to confirm final deletion', false)) {
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
                    // journal_entries: delete via sale source
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

        // Reset stock quantities to match GRN receipts (since stock_movements are gone)
        $this->newLine();
        $this->line('  Recalculating stock from GRNs...');
        try {
            if ($branchId) {
                DB::table('products')->where('branch_id', $branchId)->update(['stock_quantity' => 0]);
                $grns = DB::table('grn_items')
                    ->join('grns', 'grn_items.grn_id', '=', 'grns.id')
                    ->where('grns.branch_id', $branchId)
                    ->where('grns.status', 'received')
                    ->select('grn_items.product_id', DB::raw('SUM(grn_items.received_quantity) as qty'))
                    ->groupBy('grn_items.product_id')
                    ->get();
            } else {
                DB::table('products')->update(['stock_quantity' => 0]);
                $grns = DB::table('grn_items')
                    ->join('grns', 'grn_items.grn_id', '=', 'grns.id')
                    ->where('grns.status', 'received')
                    ->select('grn_items.product_id', DB::raw('SUM(grn_items.received_quantity) as qty'))
                    ->groupBy('grn_items.product_id')
                    ->get();
            }

            foreach ($grns as $row) {
                DB::table('products')->where('id', $row->product_id)->update(['stock_quantity' => $row->qty]);
            }
            $this->line("  <fg=green>✓</> Stock quantities reset from GRN history ({$grns->count()} products)");
        } catch (\Exception $e) {
            $this->line("  <fg=yellow>⚠</> Stock reset skipped: {$e->getMessage()}");
        }

        $this->newLine();
        $this->line('  <fg=green;options=bold>Done. Database is clean and ready to use.</>');
        $this->newLine();

        return 0;
    }
}
