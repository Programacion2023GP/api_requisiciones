<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Los proxies confiables para esta aplicación.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Cambié esto a '*' para confiar en todos los proxies. 
    
    /**
     * Las cabeceras que se deben usar para detectar los proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
