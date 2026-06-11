<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CycleShopSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ChartOfAccountSeeder::class);

        // Branch
        $branch = Branch::firstOrCreate(
            ['code' => 'CYCLE'],
            [
                'name'      => 'Cycle World',
                'city'      => 'Colombo',
                'country'   => 'Sri Lanka',
                'is_active' => true,
                'shop_type' => 'cycle',
            ]
        );

        // Users
        $users = [
            ['name' => 'Owner',       'email' => 'owner@cycle.local',   'role' => 'owner',        'can_delete_transactions' => true,  'can_override_gold_rate' => true],
            ['name' => 'Admin',       'email' => 'admin@cycle.local',   'role' => 'admin',        'can_delete_transactions' => true,  'can_override_gold_rate' => true],
            ['name' => 'Manager',     'email' => 'manager@cycle.local', 'role' => 'manager',      'can_delete_transactions' => false, 'can_override_gold_rate' => true],
            ['name' => 'Cashier',     'email' => 'cashier@cycle.local', 'role' => 'cashier',      'can_delete_transactions' => false, 'can_override_gold_rate' => false],
            ['name' => 'Store Keeper','email' => 'keeper@cycle.local',  'role' => 'store_keeper', 'can_delete_transactions' => false, 'can_override_gold_rate' => false],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'                    => $u['name'],
                    'password'                => Hash::make('password'),
                    'role'                    => $u['role'],
                    'branch_id'               => $branch->id,
                    'can_delete_transactions' => $u['can_delete_transactions'],
                    'can_override_gold_rate'  => $u['can_override_gold_rate'],
                    'is_active'               => true,
                ]
            );
        }

        // Categories
        $categoryDefs = [
            ['name' => 'Bicycles',         'slug' => 'cycle-bicycles'],
            ['name' => 'Tyres & Tubes',    'slug' => 'cycle-tyres-tubes'],
            ['name' => 'Brakes',           'slug' => 'cycle-brakes'],
            ['name' => 'Chains & Gears',   'slug' => 'cycle-chains-gears'],
            ['name' => 'Handlebars',       'slug' => 'cycle-handlebars'],
            ['name' => 'Saddles & Seats',  'slug' => 'cycle-saddles'],
            ['name' => 'Lights & Safety',  'slug' => 'cycle-lights-safety'],
            ['name' => 'Tools & Lubricants','slug' => 'cycle-tools'],
            ['name' => 'Helmets & Gear',   'slug' => 'cycle-helmets'],
            ['name' => 'Accessories',      'slug' => 'cycle-accessories'],
        ];

        foreach ($categoryDefs as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug'], 'branch_id' => $branch->id],
                array_merge($cat, ['is_active' => true, 'branch_id' => $branch->id])
            );
        }

        // Suppliers
        $supplierDefs = [
            ['name' => 'Atlas Cycles Lanka',      'email' => 'orders@atlascycles.lk',    'phone' => '+94-11-234-5678', 'city' => 'Colombo',    'country' => 'Sri Lanka'],
            ['name' => 'Shimano Asia Dist.',       'email' => 'sales@shimano-asia.com',   'phone' => '+65-6123-4567',   'city' => 'Singapore',  'country' => 'Singapore'],
            ['name' => 'Hero Cycles Import',       'email' => 'import@herocycles.in',     'phone' => '+91-161-508-5000','city' => 'Ludhiana',   'country' => 'India'],
            ['name' => 'TyreTech Global',          'email' => 'info@tyretechglobal.com',  'phone' => '+44-20-7946-0000','city' => 'London',     'country' => 'UK'],
        ];

        $supplierIds = [];
        foreach ($supplierDefs as $sup) {
            $s = Supplier::firstOrCreate(
                ['email' => $sup['email']],
                array_merge($sup, ['is_active' => true, 'branch_id' => $branch->id])
            );
            $supplierIds[] = $s->id;
        }

        // Products  [name, category_slug, type, brand, unit, base_unit, purchase, sell, stock]
        $products = [
            // -- Bicycles --
            ['name' => 'Atlas Phoenix 26" MTB',           'cat' => 'cycle-bicycles',      'type' => 'Bicycle',  'brand' => 'Atlas',   'unit' => 'Unit',  'base' => '1 unit',  'buy' => 28500,  'sell' => 36000,  'stock' => 8],
            ['name' => 'Hero Sprint 24" Kids Cycle',      'cat' => 'cycle-bicycles',      'type' => 'Bicycle',  'brand' => 'Hero',    'unit' => 'Unit',  'base' => '1 unit',  'buy' => 16500,  'sell' => 22000,  'stock' => 12],
            ['name' => 'Trinx M136 Mountain Bike 27.5"',  'cat' => 'cycle-bicycles',      'type' => 'Bicycle',  'brand' => 'Trinx',   'unit' => 'Unit',  'base' => '1 unit',  'buy' => 52000,  'sell' => 68000,  'stock' => 5],
            ['name' => 'Atlas Comfort City Bike 28"',     'cat' => 'cycle-bicycles',      'type' => 'Bicycle',  'brand' => 'Atlas',   'unit' => 'Unit',  'base' => '1 unit',  'buy' => 32000,  'sell' => 42000,  'stock' => 6],

            // -- Tyres & Tubes --
            ['name' => 'Kenda 26x2.0 MTB Tyre',          'cat' => 'cycle-tyres-tubes',   'type' => 'Part',     'brand' => 'Kenda',   'unit' => 'Piece', 'base' => '1 piece', 'buy' => 950,    'sell' => 1450,   'stock' => 50],
            ['name' => 'Schwalbe 26x1.95 Tube',           'cat' => 'cycle-tyres-tubes',   'type' => 'Part',     'brand' => 'Schwalbe','unit' => 'Piece', 'base' => '1 piece', 'buy' => 380,    'sell' => 580,    'stock' => 80],
            ['name' => 'Kenda 24x1.75 Kids Tyre',         'cat' => 'cycle-tyres-tubes',   'type' => 'Part',     'brand' => 'Kenda',   'unit' => 'Piece', 'base' => '1 piece', 'buy' => 780,    'sell' => 1200,   'stock' => 40],
            ['name' => 'Generic 28x1.5 Road Tube',        'cat' => 'cycle-tyres-tubes',   'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 280,    'sell' => 420,    'stock' => 60],

            // -- Brakes --
            ['name' => 'Shimano Tourney V-Brake Set',     'cat' => 'cycle-brakes',        'type' => 'Part',     'brand' => 'Shimano', 'unit' => 'Set',   'base' => '1 set',   'buy' => 1100,   'sell' => 1650,   'stock' => 30],
            ['name' => 'Tektro 810A Caliper Brake Pair',  'cat' => 'cycle-brakes',        'type' => 'Part',     'brand' => 'Tektro',  'unit' => 'Pair',  'base' => '1 pair',  'buy' => 850,    'sell' => 1300,   'stock' => 25],
            ['name' => 'Brake Cable Set (2 pcs)',          'cat' => 'cycle-brakes',        'type' => 'Part',     'brand' => null,      'unit' => 'Set',   'base' => '1 set',   'buy' => 220,    'sell' => 380,    'stock' => 100],
            ['name' => 'Brake Pads Pair — V-Brake',       'cat' => 'cycle-brakes',        'type' => 'Part',     'brand' => null,      'unit' => 'Pair',  'base' => '1 pair',  'buy' => 140,    'sell' => 240,    'stock' => 120],

            // -- Chains & Gears --
            ['name' => 'Shimano HG40 6-Speed Chain',      'cat' => 'cycle-chains-gears',  'type' => 'Part',     'brand' => 'Shimano', 'unit' => 'Piece', 'base' => '1 piece', 'buy' => 680,    'sell' => 1050,   'stock' => 45],
            ['name' => 'Shimano Tourney 6-Sp Freewheel',  'cat' => 'cycle-chains-gears',  'type' => 'Part',     'brand' => 'Shimano', 'unit' => 'Piece', 'base' => '1 piece', 'buy' => 920,    'sell' => 1450,   'stock' => 30],
            ['name' => 'Shimano RD-TY21 Rear Derailleur', 'cat' => 'cycle-chains-gears',  'type' => 'Part',     'brand' => 'Shimano', 'unit' => 'Piece', 'base' => '1 piece', 'buy' => 1400,   'sell' => 2100,   'stock' => 20],
            ['name' => 'Generic Gear Cable Set',           'cat' => 'cycle-chains-gears',  'type' => 'Part',     'brand' => null,      'unit' => 'Set',   'base' => '1 set',   'buy' => 180,    'sell' => 320,    'stock' => 90],

            // -- Handlebars --
            ['name' => 'Alloy Flat Handlebar 580mm',      'cat' => 'cycle-handlebars',    'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 480,    'sell' => 750,    'stock' => 35],
            ['name' => 'Riser Bar MTB 620mm',              'cat' => 'cycle-handlebars',    'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 650,    'sell' => 980,    'stock' => 28],
            ['name' => 'Stem 60mm Ahead 31.8',             'cat' => 'cycle-handlebars',    'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 420,    'sell' => 680,    'stock' => 30],

            // -- Saddles & Seats --
            ['name' => 'Selle Royal Comfort Saddle',      'cat' => 'cycle-saddles',       'type' => 'Part',     'brand' => 'Selle Royal','unit' => 'Piece','base' => '1 piece','buy' => 1200,   'sell' => 1850,   'stock' => 20],
            ['name' => 'Kids Padded Saddle',               'cat' => 'cycle-saddles',       'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 550,    'sell' => 850,    'stock' => 25],
            ['name' => 'Seat Post 27.2mm x 350mm',        'cat' => 'cycle-saddles',       'type' => 'Part',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 380,    'sell' => 580,    'stock' => 40],

            // -- Lights & Safety --
            ['name' => 'USB Rechargeable Front Light',    'cat' => 'cycle-lights-safety', 'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 680,    'sell' => 1100,   'stock' => 30],
            ['name' => 'LED Rear Blinker Light',           'cat' => 'cycle-lights-safety', 'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 280,    'sell' => 480,    'stock' => 50],
            ['name' => 'Reflector Set (4 pcs)',            'cat' => 'cycle-lights-safety', 'type' => 'Accessory','brand' => null,      'unit' => 'Set',   'base' => '1 set',   'buy' => 120,    'sell' => 220,    'stock' => 80],

            // -- Tools & Lubricants --
            ['name' => 'Finish Line Dry Lube 120ml',      'cat' => 'cycle-tools',         'type' => 'Consumable','brand' => 'Finish Line','unit' => 'Bottle','base' => '120ml','buy' => 680,   'sell' => 1050,   'stock' => 40],
            ['name' => 'Multi-Tool 16-in-1',               'cat' => 'cycle-tools',         'type' => 'Tool',     'brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 750,    'sell' => 1200,   'stock' => 25],
            ['name' => 'Tyre Lever Set (3 pcs)',           'cat' => 'cycle-tools',         'type' => 'Tool',     'brand' => null,      'unit' => 'Set',   'base' => '1 set',   'buy' => 90,     'sell' => 160,    'stock' => 100],
            ['name' => 'Puncture Repair Kit',              'cat' => 'cycle-tools',         'type' => 'Consumable','brand' => null,     'unit' => 'Kit',   'base' => '1 kit',   'buy' => 120,    'sell' => 220,    'stock' => 120],

            // -- Helmets & Gear --
            ['name' => 'GUB MTB Helmet — Adult',          'cat' => 'cycle-helmets',       'type' => 'Accessory','brand' => 'GUB',     'unit' => 'Piece', 'base' => '1 piece', 'buy' => 2800,   'sell' => 4200,   'stock' => 15],
            ['name' => 'Kids Safety Helmet',               'cat' => 'cycle-helmets',       'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 1400,   'sell' => 2200,   'stock' => 18],
            ['name' => 'Cycling Gloves — Full Finger',     'cat' => 'cycle-helmets',       'type' => 'Accessory','brand' => null,      'unit' => 'Pair',  'base' => '1 pair',  'buy' => 650,    'sell' => 1050,   'stock' => 30],

            // -- Accessories --
            ['name' => 'Rear Carrier Rack',                'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 850,    'sell' => 1350,   'stock' => 20],
            ['name' => 'Bicycle Bell — Alloy',             'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 120,    'sell' => 220,    'stock' => 80],
            ['name' => 'Water Bottle Cage',                'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 180,    'sell' => 320,    'stock' => 60],
            ['name' => 'Bicycle Lock — Combination',       'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 480,    'sell' => 780,    'stock' => 35],
            ['name' => 'Handlebar Grip Pair',              'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Pair',  'base' => '1 pair',  'buy' => 150,    'sell' => 280,    'stock' => 90],
            ['name' => 'Mudguard Set — Full',              'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Set',   'base' => '1 set',   'buy' => 420,    'sell' => 680,    'stock' => 30],
            ['name' => 'Bicycle Pump — Floor Stand',       'cat' => 'cycle-accessories',   'type' => 'Accessory','brand' => null,      'unit' => 'Piece', 'base' => '1 piece', 'buy' => 1100,   'sell' => 1750,   'stock' => 12],
        ];

        foreach ($products as $p) {
            $categoryId = Category::where('slug', $p['cat'])->where('branch_id', $branch->id)->value('id');

            Product::create([
                'sku'            => 'CYC-' . strtoupper(Str::random(8)),
                'name'           => $p['name'],
                'category_id'    => $categoryId,
                'product_type'   => $p['type'],
                'brand'          => $p['brand'],
                'unit_type'      => $p['unit'],
                'base_unit'      => $p['base'],
                'purchase_price' => $p['buy'],
                'selling_price'  => $p['sell'],
                'stock_quantity' => $p['stock'],
                'min_stock_level'=> (int) round($p['stock'] * 0.2),
                'supplier_id'    => $supplierIds[array_rand($supplierIds)],
                'is_active'      => true,
                'branch_id'      => $branch->id,
            ]);
        }

        // Customers
        $customers = [
            ['name' => 'Kamal Perera',    'email' => 'kamal@example.lk',   'phone' => '+94-77-100-0001'],
            ['name' => 'Nimal Silva',     'email' => 'nimal@example.lk',   'phone' => '+94-77-100-0002'],
            ['name' => 'Sunil Fernando',  'email' => 'sunil@example.lk',   'phone' => '+94-77-100-0003'],
            ['name' => 'Amara Kumari',    'email' => 'amara@example.lk',   'phone' => '+94-77-100-0004'],
            ['name' => 'Ravi Jayasinghe', 'email' => 'ravi@example.lk',    'phone' => '+94-77-100-0005'],
            ['name' => 'Dilani Weerasinghe','email' => 'dilani@example.lk','phone' => '+94-77-100-0006'],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['email' => $c['email']],
                array_merge($c, ['branch_id' => $branch->id])
            );
        }
    }
}
