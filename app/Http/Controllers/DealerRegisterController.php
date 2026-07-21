<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DealerRegisterController extends Controller
{
    /**
     * Shown when a dealer account is still pending admin approval.
     */
    public function pending(): View
    {
        return view('auth.dealer-pending');
    }
}
