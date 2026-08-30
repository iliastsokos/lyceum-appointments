<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChildRequest;
use App\Models\Child;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function edit(Child $child): View
    {
        $this->authorize('update', $child);

        return view('guardian.children.edit', ['child' => $child, 'schoolClasses' => SchoolClass::names()]);
    }

    public function update(StoreChildRequest $request, Child $child): RedirectResponse
    {
        $this->authorize('update', $child);

        $child->update($request->validated());

        return redirect()->route('guardian.dashboard')->with('status', 'child-updated');
    }

    public function destroy(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('delete', $child);

        $child->delete();

        return redirect()->route('guardian.dashboard')->with('status', 'child-removed');
    }
}
