<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DbSetupPageController extends Controller
{
    public function __invoke(): View
    {
        return view('db.setup', [
            'migrateUrl' => url('/db/migrate'),
            'seedUrl' => url('/db/seed'),
            'setupUrl' => url('/db/setup/run'),
        ]);
    }
}
