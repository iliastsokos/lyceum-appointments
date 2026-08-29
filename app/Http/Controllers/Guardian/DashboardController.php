<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $children = $request->user()->children()->orderBy('first_name')->get();

        return view('guardian.dashboard', [
            'children' => $children,
        ]);
    }
}
