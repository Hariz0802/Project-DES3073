<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $inventoryItems = InventoryItem::orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
        
        $lowStockItems = InventoryItem::where('quantity', '<=', DB::raw('reorder_level'))
            ->get();

        return view('inventory.index', compact('inventoryItems', 'lowStockItems'));
    }

    public function create()
    {
        $categories = InventoryItem::distinct('category')->pluck('category');
        return view('inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'reorder_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255'
        ]);

        InventoryItem::create($validated);
        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item added successfully!');
    }

    public function edit(InventoryItem $inventoryItem)
    {
        $categories = InventoryItem::distinct('category')->pluck('category');
        return view('inventory.edit', compact('inventoryItem', 'categories'));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'reorder_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255'
        ]);

        $inventoryItem->update($validated);
        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item updated successfully!');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item deleted successfully!');
    }

    public function adjustStock(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'type' => 'required|in:add,subtract'
        ]);

        $inventoryItem->updateStock($validated['adjustment'], $validated['type']);
        return redirect()->route('inventory.index')
            ->with('success', 'Stock adjusted successfully!');
    }

    public function lowStock()
    {
        $lowStockItems = InventoryItem::where('quantity', '<=', DB::raw('reorder_level'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('inventory.low-stock', compact('lowStockItems'));
    }
}
