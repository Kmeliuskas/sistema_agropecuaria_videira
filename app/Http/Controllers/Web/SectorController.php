<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectorController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Sector::class);

        $query = Sector::query()->latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (request('active') === '0') {
            $query->where('is_active', false);
        } elseif (request('active') !== 'all') {
            $query->where('is_active', true);
        }

        $perPage = request('per_page', 5);
        $sectors = $query->paginate($perPage)->withQueryString();

        return view('sectors.index', [
            'sectors' => $sectors,
            'filters' => ['search' => $search, 'active' => request('active')],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Sector::class);

        return view('sectors.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Sector::class);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', 'unique:sectors,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $sector = Sector::create($validated);

        return redirect()
            ->route('sectors.index')
            ->with('success', "Setor {$sector->name} criado com sucesso.");
    }

    public function edit(Sector $sector): View
    {
        $this->authorize('update', $sector);

        return view('sectors.form', [
            'sector' => $sector,
        ]);
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', 'unique:sectors,code,' . $sector->id],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $sector->update($validated);

        return redirect()
            ->route('sectors.index')
            ->with('success', "Setor {$sector->name} atualizado com sucesso.");
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        $this->authorize('delete', $sector);

        $sector->delete();

        return redirect()
            ->route('sectors.index')
            ->with('success', "Setor {$sector->name} removido.");
    }
}
