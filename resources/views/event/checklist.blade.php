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
                
                <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $event->menu->name }}</h1>
                
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
                            <th class="py-2 px-4 text-center">Geral</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($event->menu->items as $menuItem)
                            @php 
                                $maxRows = max(count($menuItem->item->ingredients), count($menuItem->item->matherials));
                                $maxRows = $maxRows == 0 ? 1 : $maxRows;
                            @endphp
                            
                            @for ($i = 0; $i < $maxRows; $i++)
                                <tr class="border-b border-gray-200">
                                    @if ($i == 0)
                                        <td class="py-2 px-4 font-medium align-top" rowspan="{{ $maxRows }}">{{ $menuItem->item->name }}</td>
                                    @endif
                                    <td class="py-2 px-4 text-center align-top">
                                        {{ $menuItem->item->ingredients[$i]->quantity ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->ingredients[$i]->ingredient->name ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                                        @if (isset($menuItem->item->ingredients[$i]))
                                            <input type="checkbox">
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        {{ $menuItem->item->matherials[$i]->quantity ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->matherials[$i]->matherial->name ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                                        @if (isset($menuItem->item->matherials[$i]))
                                            <input type="checkbox">
                                        @endif
                                    </td>
                                    @if ($i == 0)
                                        <td class="py-2 px-4 text-center align-top" rowspan="{{ $maxRows }}">
                                            <input type="checkbox">
                                        </td>
                                    @endif
                                </tr>
                            @endfor
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>