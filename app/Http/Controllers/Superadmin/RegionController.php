<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index()
    {
        $regions = Region::orderBy('name')->get();

        return view('superadmin.regions.index', compact('regions'));
    }

    public function create()
    {
        return view('superadmin.regions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
        ]);

        Region::create([
            'name' => $validated['name'],
            'is_platform' => true,
        ]);

        return redirect()->route('superadmin.regions.index')->with('success', 'Region added.');
    }

    public function edit(Region $region)
    {
        return view('superadmin.regions.edit', compact('region'));
    }

    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,'.$region->id,
        ]);

        $region->update($validated);

        return redirect()->route('superadmin.regions.index')->with('success', 'Region updated.');
    }

    public function destroy(Region $region)
    {
        if ($region->users()->exists()) {
            return redirect()->route('superadmin.regions.index')
                ->with('error', 'Cannot delete a region linked to users.');
        }

        $region->delete();

        return redirect()->route('superadmin.regions.index')->with('success', 'Region deleted.');
    }
}
