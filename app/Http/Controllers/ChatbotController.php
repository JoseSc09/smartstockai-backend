<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function query(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $query = strtolower(trim($request->input('query')));

            // Consulta para producto más vendido
            if (str_contains($query, 'más vendido') || str_contains($query, 'mas vendido')) {
                $topProduct = Movement::where('type', 'out')
                    ->groupBy('product_id')
                    ->selectRaw('product_id, SUM(quantity) as total')
                    ->orderBy('total', 'desc')
                    ->first();

                if (!$topProduct) {
                    return response()->json(['response' => 'No hay datos de ventas disponibles.'], 200);
                }

                $product = Product::find($topProduct->product_id);
                if (!$product) {
                    Log::warning('Producto no encontrado para product_id: ' . $topProduct->product_id);
                    return response()->json(['response' => 'Producto no encontrado para el movimiento más vendido.'], 200);
                }

                return response()->json([
                    'response' => "El producto más vendido es {$product->name} con {$topProduct->total} unidades vendidas."
                ], 200);
            }

            // Consulta para stock de producto
            if (str_contains($query, 'stock de') || str_contains($query, 'existencias de')) {
                preg_match('/stock de (.+)/i', $query, $matches); // Ajustado para asegurar que captura después de "stock de"
                $productName = isset($matches[1]) ? trim($matches[1]) : null;

                if (!$productName) {
                    return response()->json(['response' => 'Por favor, especifica el nombre del producto después de "stock de".'], 200);
                }

                $product = Product::where('name', 'like', "%{$productName}%")->first();
                if (!$product) {
                    return response()->json(['response' => "No se encontró el producto {$productName}."], 200);
                }

                return response()->json([
                    'response' => "El stock de {$product->name} es {$product->stock} unidades."
                ], 200);
            }

            // Consulta no reconocida
            return response()->json([
                'response' => 'Lo siento, no entiendo la consulta. Prueba con "producto más vendido" o "stock de [nombre]".'
            ], 200);
        } catch (\Exception $e) {
            Log::error('ChatbotController error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }
}
