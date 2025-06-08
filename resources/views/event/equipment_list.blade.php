<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
        @endif
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="max-w-4xl mx-auto py-6">
            <div class="mb-6 p-6 bg-white shadow-md rounded-lg">
                <!-- Título do Menu -->

                <div class="bg-white p-4 rounded-lg shadow-md w-full max-w-lg">
                    <h1 class="text-2xl font-bold text-gray-800 mb-3">Evento de {{ $event->client->name }}</h1>
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">{{ \Carbon\Carbon::parse($event->date)->locale('pt_BR')->translatedFormat('d \d\e M. \d\e Y \à\s H:i') }}</h2>
                    <p class="text-lg font-semibold">{{ $event->address->street }}, Nº {{ $event->address->number }}</p>
                    @if (!empty($event->address->complement))
                        <p class="text-sm text-gray-600">Complemento: {{ $event->address->complement }}</p>
                    @endif
                    <p class="text-sm">{{ $event->address->neighborhood }}, {{ $event->address->city }}/{{ $event->address->state }}</p>
                    <p class="text-sm">{{ $event->address->country }}</p>
                    <p class="text-sm font-medium text-gray-700">CEP: {{ $event->address->zipcode }}</p>
                </div>

                {{-- <a href="{{route('event.add_item_to_checklist', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Adicionar item</a>

                <a href="{{route('event.shopping_list', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Lista de Compras</a>

                <a href="{{route('event.equipment_list', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Lista dos Equipamentos</a> --}}

                <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $event->menu->name }}</h1>
                <br>

                <h1 class="text-2xl font-bold text-gray-500 mb-3">Equipamentos e Utensilios</h1>

                <!-- Tabela de Itens -->
                <table class="w-full text-left text-sm text-gray-800">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4">Item</th>
                            <th class="py-2 px-4 text-center">QTD</th>
                            <th class="py-2 px-4">Equipamento Necessário</th>
                            <th class="py-2 px-4 text-center">✔</th>
                            <th class="py-2 px-4 text-center">QTD</th>
                            <th class="py-2 px-4">Utensílio Necessário</th>

                            <th class="py-2 px-4 text-center">✔</th>


                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventItems as $key => $menuItem)
                            @php 
                                $maxRows = max(count($menuItem->item->ingredients), count($menuItem->item->matherials));
                                $maxRows = $maxRows == 0 ? 1 : $maxRows;
                            @endphp
                    
                    @for ($i = 0; $i < $maxRows; $i++)
                        <tr class="border-b border-gray-200">
                            @if ($i == 0)
                                <td class="py-2 px-4 font-medium align-top" rowspan="{{ $maxRows }}">{{ $menuItem->item->name }}</td>
                            @endif
                            {{-- @dd($menuItem->item->matherials) --}}
                            <td class="py-2 px-4 text-center align-top">
                                @if(isset($menuItem->item->matherials[$i]->matherial) && $menuItem->item->matherials[$i]->category == App\Enums\MatherialType::EQUIPMENT->name)
                                    {{ $menuItem->item->matherials[$i]->matherial->quantity ?? '' }}
                                @endif
                            </td>
                            <td class="py-2 px-4 align-top">
                                @if(isset($menuItem->item->matherials[$i]->matherial) && $menuItem->item->matherials[$i]->matherial->category == App\Enums\MatherialType::EQUIPMENT->name)

                                    {{ $menuItem->item->matherials[$i]->matherial->name ?? '' }}
                                    @endif
                            </td>
                            <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                                @if (isset($menuItem->matherials[$i]))
                                    <form action="{{ route('event.checklist.check_matherial', ['event_id'=>$event->id, 'matherial_id'=>$menuItem->matherials[$i]->matherial_id, "item_id"=>$menuItem->item->id])}}" class="form-checklist" method="post">
                                        @csrf
                                        @method('patch')
                                        <input type="checkbox" name="check" {{ $menuItem->matherials[$i]->checked_at != null ? "checked" : ""}}>
                                    </form>
                                @endif
                            </td>
                            <td class="py-2 px-4 text-center align-top">
                                @if(isset($menuItem->item->matherials[$i]->matherial) && $menuItem->item->matherials[$i]->matherial->category == App\Enums\MatherialType::TOOL->name)
                                        {{ $menuItem->item->matherials[$i]->quantity ?? '' }}
                                @endif
                                </td>
                                <td class="py-2 px-4 align-top">
                                @if(isset($menuItem->item->matherials[$i]->matherial) && $menuItem->item->matherials[$i]->matherial->category == App\Enums\MatherialType::TOOL->name)

                                        {{ $menuItem->item->matherials[$i]->matherial->name ?? '' }}
                                @endif
                                </td>
                            @if ($i == 0)
                                <td class="py-2 px-4 text-center align-top" rowspan="{{ $maxRows }}">
                                    <form action="{{ route('event.checklist.check_item', ['event_id'=>$event->id, "item_id"=>$menuItem->item->id])}}" class="form-checklist" method="post">
                                        @csrf
                                        @method('patch')
                                        <input type="checkbox" name="check" {{ $menuItem->checked_at != null ? "checked" : ""}}>
                                    </form>
                                    <form action="{{ route('event.checklist.delete_item', ['event_id'=>$event->id, "item_id"=>$menuItem->item->id])}}" method="post">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Deletar item {{ $menuItem->item->name }} (somente do cardapio do cliente)">❌</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endfor
                        @endforeach
                    </tbody>
                </table>


                <h1 class="text-2xl font-bold text-gray-500 mb-3">Itens Fixos</h1>
                <!-- Tabela de Itens -->
                <table class="w-full text-left text-sm text-gray-800">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4">Categoria</th>
                            <th class="py-2 px-4">Item</th>
                            <th class="py-2 px-4 text-center">QTD</th>
                            <th class="py-2 px-4 text-center">✔</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventFixedItems as $key => $menuItem)
                            @php 
                                $maxRows = count($menuItem->fixedItems);
                                $maxRows = $maxRows == 0 ? 1 : $maxRows;
                            @endphp
                    @for ($i = 0; $i < $maxRows; $i++)
                    <tr class="border-b border-gray-200">
                        @if ($i == 0)
                            <td class="py-2 px-4 font-medium align-top" rowspan="{{ $maxRows }}">{{ $key }}</td>
                        @endif
                        <td class="py-2 px-4 text-center align-top">{{ $menuItem->fixedItems[$i]->quantity ?? '' }}</td>
                        <td class="py-2 px-4 align-top">{{ $menuItem->fixedItems[$i]->name ?? '' }}</td>
                        <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                            @if (isset($menuItem->fixedItems[$i]))
                                <form action="{{ route('event.checklist.check_ingredient', ['event_id'=>$event->id, 'ingredient_id'=>$menuItem->fixedItems[$i]->id, 'item_id'=>$menuItem->fixedItems[$i]->id])}}" class="form-checklist" method="post">
                                    @csrf
                                    @method('patch')
                                    <input type="checkbox" name="check" {{ $menuItem->fixedItems[$i]->checked_at != null ? "checked" : ""}}>
                                </form>
                            @endif
                        </td>
                        @if ($i == 0)
                            {{-- <td class="py-2 px-4 text-center align-top" rowspan="{{ $maxRows }}">
                                <form action="{{ route('event.checklist.check_item', ['event_id'=>$event->id, 'item_id'=>$menuItem->fixedItems[$i]->id])}}" class="form-checklist" method="post">
                                    @csrf
                                    @method('patch')
                                    <input type="checkbox" name="check" {{ $menuItem->checked_at != null ? "checked" : ""}}>
                                </form>
                                <form action="{{ route('event.checklist.delete_item', ['event_id'=>$event->id, 'item_id'=>$menuItem->item->id])}}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="Deletar item {{ $menuItem->item->name }} (somente do cardapio do cliente)">❌</button>
                                </form>
                            </td> --}}
                        @endif
                    </tr>
                @endfor
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", (event) => {
                const checklist_forms = document.querySelectorAll(".form-checklist")
                checklist_forms.forEach(checklist=>{
                    const checkbox = checklist.querySelector("input[type=checkbox]")

                    checkbox.addEventListener('change', (e)=>checklist.submit())
                })
            });

        </script>
    </body>
</html>