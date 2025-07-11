<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));
        $origin = $request->header('Origin');

        // Crear respuesta
        $response = $request->isMethod('OPTIONS') ? response('', 200) : $next($request);

        // Aplicar headers CORS solo si el origen está permitido
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        } else {
            // Opcional: Devolver error si el origen no está permitido
            $response->headers->set('Access-Control-Allow-Origin', '');
        }

        return $response;
    }
}
