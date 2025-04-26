<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Address;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        protected Client $client,
        protected Address $address

    ){}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = $this->client
        ->get();

        return response()->json($clients);
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
    public function store(Request $request)
    {
        // dd($request);
        // return response()->json($request->name);
        // // dd($request->name);   
        $client = Client::create([
            'name' => $request->name,
            'document' => $request->document,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'document_type' => DocumentType::CPF->name,
            'address_id'=>null
        ]);
        return response()->json($client);
    }
    public function store_address(Request $request)
    {
        $address = Address::create([
            'zipcode' => $request->zipcode,
            'street' => $request->street,
            'number' => $request->number,
            'complement' => $request->email,
            'city'=>$request->city,
            'country'=>$request->country,
            'state'=>$request->state,
            'neighborhood'=>$request->neighborhood
        ]);
        $address_id = $address->id;
        $client = $this->client
        ->where('id',$request->client_id)
        ->get()
        ->first();

        $client = $client->update([
            "address_id"=>$address_id
        ]);

        return response()->json($address);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $client = $this->client
        ->where('id',$request->client_id)
        ->get()
        ->first();

        $address = $this->address
        ->where('id',$client->address_id)
        ->get()
        ->first();
        if(!$client)
            return response()->json(["Invalid client ID"]);

        return response()->json([
            'client' =>$client,
            'address'=>$address
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }
}
