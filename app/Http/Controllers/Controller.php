<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(
 *     title="Sistema de Coleta Arqueológica API",
 *     version="1.0.0",
 *     description="API REST para o Sistema de Coleta Arqueológica."
 * )
 * @OA\Server(url="/api", description="Servidor Railway")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
abstract class Controller
{
    use AuthorizesRequests;
}
