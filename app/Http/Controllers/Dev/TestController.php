<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    //
    public function index()
    {
        return view('pages.dev.test');
    }

    public function toast()
    {
        return back()->with('info', 'Something went wrong! Try again.');
    }
}
