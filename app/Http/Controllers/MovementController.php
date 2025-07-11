<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\LowStockNotification;

class MovementController extends Controller
{
    public function index()
    {
        $movements = Movement::with('product')->get();
        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::find($request->product_id);
        if ($request->type === 'out' && $product->stock < $request->quantity) {
            return response()->json(['error' => 'Stock insuficiente'], 422);
        }

        $movement = Movement::create($request->all());

        if ($request->type === 'in') {
            $product->stock += $request->quantity;
        } else {
            $product->stock -= $request->quantity;
        }
        $product->save();

        if ($product->checkLowStock()) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new LowStockNotification($product));
            }
        }

        return response()->json($movement->load('product'), 201);
    }

    public function show(Movement $movement)
    {
        return response()->json($movement->load('product'));
    }

    public function update(Request $request, Movement $movement)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::find($request->product_id);
        $oldProduct = Product::find($movement->product_id);

        // Revertir el movimiento anterior
        if ($movement->type === 'in') {
            $oldProduct->stock -= $movement->quantity;
        } else {
            $oldProduct->stock += $movement->quantity;
        }
        $oldProduct->save();

        // Aplicar el nuevo movimiento
        if ($request->type === 'out' && $product->stock < $request->quantity) {
            return response()->json(['error' => 'Stock insuficiente'], 422);
        }

        $movement->update($request->all());

        if ($request->type === 'in') {
            $product->stock += $request->quantity;
        } else {
            $product->stock -= $request->quantity;
        }
        $product->save();

        if ($product->checkLowStock()) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new LowStockNotification($product));
            }
        }

        return response()->json($movement->load('product'));
    }

    public function destroy(Movement $movement)
    {
        $product = $movement->product;
        if ($movement->type === 'in') {
            if ($product->stock < $movement->quantity) {
                return response()->json(['error' => 'No se puede eliminar: stock insuficiente'], 422);
            }
            $product->stock -= $movement->quantity;
        } else {
            $product->stock += $movement->quantity;
        }
        $product->save();

        $movement->delete();
        return response()->json(null, 204);
    }
}
