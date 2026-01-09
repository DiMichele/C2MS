<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controller base per tutti i controller dell'applicazione
 * 
 * Questo controller astratto fornisce funzionalità comuni
 * per tutti i controller dell'applicazione SUGECO:
 * - AuthorizesRequests: permette l'uso di $this->authorize()
 * - ValidatesRequests: permette l'uso di $this->validate()
 * 
 * @package App\Http\Controllers
 * @version 1.1
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
