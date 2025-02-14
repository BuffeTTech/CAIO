<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIngredientRequest;
use App\Http\Requests\UpdateIngredientRequest;
use App\Models\Menu\Ingredient;
use App\Models\Menu\Item;
use App\Models\Menu\ItemHasIngredient;
use App\Models\Menu\Menu;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function __construct(
        protected Ingredient $ingredient,
        protected Menu $menu,
        protected Item $items,
        protected ItemHasIngredient $item_has_ingredient,
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIngredientRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Ingredient $ingredient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ingredient $ingredient)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIngredientRequest $request, Ingredient $ingredient)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $ingredient = $this->ingredient->find($request->id);
        if(!$ingredient) {
            return back()->withErrors(['ingredient'=> "Ingrediente não existe"]);
        }
        $item = $this->items->find($request->item_id);
        if(!$item) {
            return back()->withErrors(['item'=> "Item não existe"]);
        }

        $item_has_ingredient = $this->item_has_ingredient->where('ingredient_id', $ingredient->id)
                                                ->where('item_id', $item->id)->get()->first();
        if(!$item_has_ingredient) {
            return back()->withErrors(['item_ingredient'=> "Relacionamento não existe"]);
        }
        $item_has_ingredient->delete();

        return back()->with('message', "Ingrediente deletado com sucesso!");
    }
}
