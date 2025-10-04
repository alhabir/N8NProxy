<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocsController extends Controller
{
    public function merchant()
    {
        return view('docs.merchant');
    }

    public function admin()
    {
        return view('docs.admin');
    }
}
