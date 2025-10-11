<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // ESSENCIAL: IMPORTAÇÃO DO BASE CONTROLLER

/**
 * Classe base para todos os seus Controllers.
 * Ao estender 'BaseController', ela resolve o erro "Undefined method 'middleware'".
 */
class Controller extends BaseController // ESTENDE O BASE CONTROLLER CORRETO
{
    use AuthorizesRequests, ValidatesRequests;
}
