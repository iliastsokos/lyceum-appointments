<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSchoolClassRequest;
use App\Models\Child;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(): View
    {
        // Child.class is a plain string, not a foreign key (see the comment
        // on update() below), so this is a manual count-by-name rather than
        // an Eloquent withCount() over a real relationship.
        $childCounts = Child::selectRaw('class, count(*) as aggregate')
            ->groupBy('class')
            ->pluck('aggregate', 'class');

        $schoolClasses = SchoolClass::orderBy('name')->get()
            ->map(fn (SchoolClass $schoolClass) => tap($schoolClass, function ($sc) use ($childCounts) {
                $sc->children_count = $childCounts->get($sc->name, 0);
            }));

        return view('admin.school-classes.index', ['schoolClasses' => $schoolClasses]);
    }

    public function store(StoreSchoolClassRequest $request): RedirectResponse
    {
        SchoolClass::create($request->validated());

        return redirect()->route('admin.school-classes.index')->with('status', 'school-class-created');
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $oldName = $schoolClass->name;
        $newName = $request->validated('name');

        DB::transaction(function () use ($schoolClass, $oldName, $newName) {
            $schoolClass->update(['name' => $newName]);

            // The child-teacher `class` column is a plain string (see the
            // comment on Child's fillable list), not a foreign key, so a
            // rename has to be propagated by hand to keep existing children
            // pointing at a name that still exists.
            Child::where('class', $oldName)->update(['class' => $newName]);
        });

        return redirect()->route('admin.school-classes.index')->with('status', 'school-class-updated');
    }

    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        if (Child::where('class', $schoolClass->name)->exists()) {
            return redirect()->route('admin.school-classes.index')->withErrors([
                'schoolClass' => 'Δεν είναι δυνατή η διαγραφή: υπάρχουν μαθητές καταχωρημένοι σε αυτό το τμήμα.',
            ]);
        }

        $schoolClass->delete();

        return redirect()->route('admin.school-classes.index')->with('status', 'school-class-deleted');
    }
}
