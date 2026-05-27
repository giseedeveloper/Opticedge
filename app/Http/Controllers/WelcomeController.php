<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('welcome', compact('packages'));
    }
}
