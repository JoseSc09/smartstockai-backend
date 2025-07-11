<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\LowStockNotification;

class ProductController extends Controller
{
    /**
     * Listar todos los productos con su categoría.
     */
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products);
    }

    /**
     * Crear un nuevo producto y verificar bajo stock.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());

        if ($product->checkLowStock()) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new LowStockNotification($product));
            }
        }

        return response()->json($product->load('category'), 201);
    }

    /**
     * Mostrar un producto específico con su categoría.
     */
    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    /**
     * Actualizar un producto existente y verificar bajo stock.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->all());

        if ($product->checkLowStock()) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new LowStockNotification($product));
            }
        }

        return response()->json($product->load('category'));
    }

    /**
     * Eliminar un producto (soft delete).
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }

    /**
     * Predecir stock futuro de un producto.
     */
    public function predictStock(Product $product)
    {
        $movements = $product->movements()->where('type', 'out')->get();
        $averageConsumption = $movements->avg('quantity') ?? 0;
        $predictedStock = $product->stock - ($averageConsumption * 30);
        $reorderQuantity = $averageConsumption * 60;

        return response()->json([
            'product' => $product->name,
            'current_stock' => $product->stock,
            'predicted_stock_30_days' => max(0, $predictedStock),
            'reorder_suggestion' => ceil($reorderQuantity),
        ]);
    }
}
