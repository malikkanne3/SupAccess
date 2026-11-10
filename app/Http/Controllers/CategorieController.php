<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategorieController extends Controller
{
    public function index()
    {
        return Inertia::render('Categories/Index', [
            'categories' => Categorie::withCount('equipements')->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Categorie::create($this->valider($request));

        return back()->with('success', 'Catégorie ajoutée.');
    }

    public function update(Request $request, Categorie $categorie)
    {
        $categorie->update($this->valider($request, $categorie->id));

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Categorie $categorie)
    {
        if ($categorie->equipements()->exists()) {
            return back()->with('error', 'Cette catégorie contient des équipements : déplacez-les avant de la supprimer.');
        }
        $categorie->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }

    private function valider(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:80', Rule::unique('categories', 'nom')->ignore($id)],
            'description' => ['nullable', 'string', 'max:300'],
        ], [
            'nom.required' => 'Donnez un nom à la catégorie.',
            'nom.unique' => 'Cette catégorie existe déjà.',
        ]);
    }
}
