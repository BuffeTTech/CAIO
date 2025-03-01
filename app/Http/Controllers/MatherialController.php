<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatherialRequest;
use App\Http\Requests\UpdateMatherialRequest;
use App\Models\Menu\Item;
use App\Models\Menu\ItemHasMatherial;
use App\Models\Menu\Matherial;
use App\Models\Menu\Menu;
use Illuminate\Http\Request;

class MatherialController extends Controller
{
    public function __construct(
        protected Menu $menu,
        protected Item $items,
        protected Matherial $matherial,
        protected ItemHasMatherial $item_has_matherial,
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
    public function store(StoreMatherialRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Matherial $matherial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matherial $matherial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMatherialRequest $request, Matherial $matherial)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $matherial = $this->matherial->find($request->id);
        if(!$matherial) {
            return response()->json(["data"=>"Invalid matherial id"], 404);
        }
        $item = $this->items->find($request->item_id);
        if(!$item) {
            return response()->json(["data"=>"Invalid item id"], 404);
        }

        $item_has_matherial = $this->item_has_matherial->where('matherial_id', $matherial->id)
                                                ->where('item_id', $item->id)->get()->first();
        if(!$item_has_matherial) {
            return response()->json(["data"=>"Invalid relationship"], 404);
        }
        $item_has_matherial->delete();

        return response()->json('deletado com sucesso!');
    }
}
