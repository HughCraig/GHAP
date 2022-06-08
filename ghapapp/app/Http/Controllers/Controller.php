<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    public function __construct()
    {
        ini_set('max_execution_time', 60);
    }

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
