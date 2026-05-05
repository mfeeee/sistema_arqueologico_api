<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(
 *     title="Sistema de Coleta Arqueológica API",
 *     version="1.0.0",
 *     ...
 * )
 * @OA\Server(url="/api", description="Servidor principal")
 * @OA\SecurityScheme(...)
 */
abstract class Controller
{
    use AuthorizesRequests;
}
