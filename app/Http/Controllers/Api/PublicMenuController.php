<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function table(Request $request, string $tableNumber)
    {
        $branchId = $request->query('b');

        $table = Table::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('table_number', $tableNumber)
            ->first();

        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $branch = Branch::find($table->branch_id);

        return response()->json([
            'table_number' => $table->table_number,
            'capacity'     => $table->capacity,
            'status'       => $table->status,
            'branch_id'    => $table->branch_id,
            'branch_name'  => $branch?->name ?? '',
            'branch_logo'  => $branch?->logo_url ?? null,
            'branch_address' => trim(implode(', ', array_filter([
                $branch?->address,
                $branch?->city,
            ]))),
        ]);
    }

    public function menu(Request $request)
    {
        $branchId = $request->query('b');

        if (!$branchId) {
            return response()->json(['message' => 'Branch required'], 422);
        }

        $categories = Category::where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::with('category:id,name')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where(fn($q) => $q->where('stock_quantity', '>', 0)->orWhereNull('stock_quantity'))
            ->orderBy('name')
            ->get([
                'id', 'name', 'description', 'selling_price', 'image',
                'category_id', 'stock_quantity', 'product_type',
            ]);

        return response()->json([
            'categories' => $categories,
            'products'   => $products->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'price'       => (float) $p->selling_price,
                'image'       => $p->image,
                'category_id' => $p->category_id,
                'category'    => $p->category?->name,
            ]),
        ]);
    }
}
