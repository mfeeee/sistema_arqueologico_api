<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(
 *     title="Sistema de Coleta Arqueologica API",
 *     version="1.0.0",
 *     description="API REST para o Sistema de Coleta Arqueologica."
 * )
 *
 * @OA\Server(url="/api", description="Servidor Railway")
 *
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
