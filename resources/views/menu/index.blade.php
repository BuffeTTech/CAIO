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
            @foreach ($menus as $menu)
                <div class="mb-6 p-6 bg-white shadow-md rounded-lg">
                    <!-- Título do Menu -->
                    <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $menu->name }}</h1>
                    
                    <!-- Tabela de Itens -->
                    <table class="w-full text-left text-sm text-gray-800">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4">Item</th>
                                <th class="py-2 px-4 text-center">QTD</th>
                                <th class="py-2 px-4">Ingredientes</th>
                                <th class="py-2 px-4 text-center">✔</th>
                                <th class="py-2 px-4 text-center">QTD</th>
                                <th class="py-2 px-4">Equipamentos</th>
                                <th class="py-2 px-4 text-center">✔</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menu->items as $menuItem)
                                @php 
                                    $totalIngredients = count($menuItem->item->ingredients);
                                    $totalMatherials = count($menuItem->item->matherials);
                                    $maxRows = max($totalIngredients, $totalMatherials);
                                    $maxRows = $maxRows == 0 ? 1 : $maxRows;
                                @endphp
                                
                                @for ($i = 0; $i < $maxRows; $i++)
                                <tr class="border-b border-gray-200">
                                    @if ($i == 0)
                                        <td class="py-2 px-4 font-medium align-top" rowspan="{{ $maxRows }}">{{ $menuItem->item->name }}</td>
                                    @endif
                                    {{-- @if ($i == 1)
                                        <td class="py-2 px-4 text-center align-top" rowspan="{{ $maxRows }}">
                                            <button class="bg-green-500 text-white px-4 py-2 rounded">➕ Adicionar Ingrediente</button>
                                        </td>                                    
                                    @endif
                                    @if ($i == 2)
                                        <td class="py-2 px-4 text-center align-top" rowspan="{{ $maxRows }}">
                                            <button class="bg-blue-500 text-white px-4 py-2 rounded">➕ Adicionar Material</button>
                                        </td>
                                    @endif --}}
                                    
                                    <td class="py-2 px-4 text-center align-top">
                                        {{ $menuItem->item->ingredients[$i]->quantity ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->ingredients[$i]->ingredient->name ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                                        @if (isset($menuItem->item->ingredients[$i]))
                                        <form action="{{route('ingredient.destroy',['id'=>$menuItem->item->ingredients[$i]->ingredient->id, "item_id"=>$menuItem->item->id])}}" method="POST">
                                            @method('delete')
                                            @csrf
                                            <button type="submit">❌</button>
                                        </form>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        {{ $menuItem->item->matherials[$i]->quantity ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->matherials[$i]->matherial->name ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        @if (isset($menuItem->item->matherials[$i]))
                                        <form action="{{route('matherial.destroy',['id'=>$menuItem->item->matherials[$i]->matherial->id, "item_id"=>$menuItem->item->id])}}" method="POST">
                                            @method('delete')
                                            @csrf
                                            <button type="submit">❌</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endfor
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </body>
</html>
