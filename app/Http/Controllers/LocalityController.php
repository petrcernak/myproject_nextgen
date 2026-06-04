<?php

namespace App\Http\Controllers;

use App\Models\Locality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocalityController extends Controller
{
    private function authorize(): void
    {
        abort_unless($this->currentUser()->isGroupAdmin(), 403);
    }

    private function authorizeLocality(Locality $locality): void
    {
        $this->authorize();
        abort_unless($locality->id_group == $this->currentGroupId(), 403);
    }

    public function index(): View
    {
        $this->authorize();
        $localities = Locality::where('id_group', $this->currentGroupId())
            ->withCount('projects')
            ->orderBy('name')
            ->get();

        return view('localities.index', compact('localities'));
    }

    public function create(): View
    {
        $this->authorize();
        return view('localities.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $data['id_group'] = $this->currentGroupId();
        Locality::create($data);

        return redirect()->route('localities.index')->with('success', __('Locality created.'));
    }

    public function edit(Locality $locality): View
    {
        $this->authorizeLocality($locality);
        return view('localities.form', compact('locality'));
    }

    public function update(Request $request, Locality $locality): RedirectResponse
    {
        $this->authorizeLocality($locality);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $locality->update($data);

        return redirect()->route('localities.index')->with('success', __('Locality saved.'));
    }

    public function destroy(Locality $locality): RedirectResponse
    {
        $this->authorizeLocality($locality);

        if ($locality->projects()->exists()) {
            return back()->with('error', __('Locality cannot be deleted — it has projects assigned to it.'));
        }

        $locality->delete();

        return redirect()->route('localities.index')->with('success', __('Locality deleted.'));
    }
}
