<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RegionController extends Controller
{
    public function index()
    {
        $regions = Region::orderBy('name')->paginate(40);

        return view('admin.regions.index', compact('regions'));
    }

    public function create()
    {
        return view('admin.regions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
        ]);

        $attrs = ['name' => $validated['name']];
        if (Schema::hasColumn('regions', 'is_platform')) {
            $attrs['is_platform'] = false;
        }
        if (Schema::hasColumn('regions', 'created_by_tenant_id') && auth()->user()?->tenant_id) {
            $attrs['created_by_tenant_id'] = auth()->user()->tenant_id;
        }

        Region::create($attrs);

        return redirect()->route('admin.regions.index')->with('success', 'Region added.');
    }
}
