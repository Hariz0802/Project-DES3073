<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    /**
     * Display a listing of the recipes.
     */
    public function index()
    {
        $recipes = Recipe::all()->groupBy('category');
        return view('recipe.index', compact('recipes'));
    }

    /**
     * Show the form for creating a new recipe.
     */
    public function create()
    {
        $categories = Recipe::distinct('category')->pluck('category')->filter();
        $ingredients = InventoryItem::all();
        return view('recipe.create', compact('categories', 'ingredients'));
    }

    /**
     * Store a newly created recipe in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
            'preparation_time' => 'required|integer|min:0',
            'cooking_time' => 'required|integer|min:0',
            'serving_size' => 'required|integer|min:1',
            'cost_per_serving' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.id' => 'required|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'instructions' => 'required|array|min:1',
            'instructions.*' => 'required|string',
            'new_category' => 'required_if:category,new|string|max:255',
        ]);

        // Handle new category
        if ($request->category === 'new' && $request->has('new_category')) {
            $validated['category'] = $request->new_category;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('recipes', 'public');
            $validated['image'] = $path;
        }

        // Create recipe
        $recipe = Recipe::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'preparation_time' => $validated['preparation_time'],
            'cooking_time' => $validated['cooking_time'],
            'serving_size' => $validated['serving_size'],
            'cost_per_serving' => $validated['cost_per_serving'],
            'image' => $validated['image'] ?? null,
            'ingredients' => $validated['ingredients'],
            'instructions' => $validated['instructions'],
        ]);

        // Update inventory quantities
        foreach ($validated['ingredients'] as $ingredient) {
            $inventoryItem = InventoryItem::find($ingredient['id']);
            $inventoryItem->decrement('quantity', $ingredient['quantity']);
        }

        return redirect()->route('recipe.index')
            ->with('success', 'Recipe created successfully.');
    }

    /**
     * Display the specified recipe.
     */
    public function show(Recipe $recipe)
    {
        return view('recipe.show', compact('recipe'));
    }

    /**
     * Show the form for editing the specified recipe.
     */
    public function edit(Recipe $recipe)
    {
        $categories = Recipe::distinct('category')->pluck('category')->filter();
        $ingredients = InventoryItem::all();
        return view('recipe.edit', compact('recipe', 'categories', 'ingredients'));
    }

    /**
     * Update the specified recipe in storage.
     */
    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
            'preparation_time' => 'required|integer|min:0',
            'cooking_time' => 'required|integer|min:0',
            'serving_size' => 'required|integer|min:1',
            'cost_per_serving' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.id' => 'required|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'instructions' => 'required|array|min:1',
            'instructions.*' => 'required|string',
            'new_category' => 'required_if:category,new|string|max:255',
        ]);

        // Handle new category
        if ($request->category === 'new' && $request->has('new_category')) {
            $validated['category'] = $request->new_category;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($recipe->image) {
                Storage::disk('public')->delete($recipe->image);
            }
            $path = $request->file('image')->store('recipes', 'public');
            $validated['image'] = $path;
        }

        // Update inventory quantities
        $oldIngredients = $recipe->ingredients;
        $newIngredients = $validated['ingredients'];

        // Return quantities from old recipe to inventory
        foreach ($oldIngredients as $ingredient) {
            $inventoryItem = InventoryItem::find($ingredient['id']);
            $inventoryItem->increment('quantity', $ingredient['quantity']);
        }

        // Deduct quantities for new recipe from inventory
        foreach ($newIngredients as $ingredient) {
            $inventoryItem = InventoryItem::find($ingredient['id']);
            $inventoryItem->decrement('quantity', $ingredient['quantity']);
        }

        // Update recipe
        $recipe->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'preparation_time' => $validated['preparation_time'],
            'cooking_time' => $validated['cooking_time'],
            'serving_size' => $validated['serving_size'],
            'cost_per_serving' => $validated['cost_per_serving'],
            'ingredients' => $validated['ingredients'],
            'instructions' => $validated['instructions'],
        ]);

        if (isset($validated['image'])) {
            $recipe->image = $validated['image'];
            $recipe->save();
        }

        return redirect()->route('recipe.show', $recipe)
            ->with('success', 'Recipe updated successfully.');
    }

    /**
     * Remove the specified recipe from storage.
     */
    public function destroy(Recipe $recipe)
    {
        // Return quantities to inventory
        foreach ($recipe->ingredients as $ingredient) {
            $inventoryItem = InventoryItem::find($ingredient['id']);
            $inventoryItem->increment('quantity', $ingredient['quantity']);
        }

        // Delete recipe image if exists
        if ($recipe->image) {
            Storage::disk('public')->delete($recipe->image);
        }

        $recipe->delete();

        return redirect()->route('recipe.index')
            ->with('success', 'Recipe deleted successfully.');
    }

    /**
     * Calculate the cost of a recipe based on its ingredients.
     */
    public function calculateCost(Request $request)
    {
        $validated = $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $totalCost = 0;
        foreach ($validated['ingredients'] as $ingredient) {
            $inventoryItem = InventoryItem::find($ingredient['id']);
            $totalCost += $inventoryItem->unit_price * $ingredient['quantity'];
        }

        return response()->json([
            'total_cost' => $totalCost,
            'suggested_price' => $totalCost * 1.5, // 50% markup
        ]);
    }
}
