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
                <br>
                <a href="{{route('event.add_item_to_checklist', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Adicionar item</a>

                <a href="{{route('event.shopping_list', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Lista de Compras</a>

                <a href="{{route('event.equipment_list', ['event_id'=>$event->id])}}" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Lista dos Equipamentos</a>
                @php 
                    $status = false;
                    $menuEventItems = $event->menu_event->items;
                    $menuEventCategories = App\Enums\FoodCategory::foodItems();
                @endphp
                <div id="status-container" data-status="<?= $status ? 'true' : 'false' ?>">
                    <p>Status: <span id="status-text"><?= $status ? 'Ativo' : 'Inativo' ?></span></p>
                    <button onclick="toggleViewMode()" class="px-4 py-2 bg-blue-500 text-white rounded">
                        Ver por Categoria
                    </button>
                </div>
                <br>
                <br>


                <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $event->menu->name }}</h1>
                
                <!-- Tabela de Itens -->
                <table class="w-full text-left text-sm text-gray-800">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4">{{$status ? 'Categoria':'Item'}}</th>
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
                        @foreach ($menuEventItems as $key => $menuItem)
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
                                        {{ $menuItem->ingredients[$i]->quantity ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->ingredients[$i]->ingredient->name ?? '' }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top border-r border-gray-200">
                                        @if (isset($menuItem->ingredients[$i]))
                                            <form action="{{ route('event.checklist.check_ingredient', ['event_id'=>$event->id, 'ingredient_id'=>$menuItem->ingredients[$i]->ingredient_id, "item_id"=>$menuItem->item->id])}}" class="form-checklist" method="post">
                                                @csrf
                                                @method('patch')
                                                <input type="checkbox" name="check" {{ $menuItem->ingredients[$i]->checked_at != null ? "checked" : ""}}>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        <form action="">
                                            {{ $menuItem->item->matherials[$i]->quantity ?? '' }}
                                        </form>
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $menuItem->item->matherials[$i]->matherial->name ?? '' }}
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    let status = document.getElementById('status-container').getAttribute('data-status') === 'true';

    function toggleViewMode() {
        status = !status;
        document.getElementById('status-text').innerText = status ? 'Ativo' : 'Inativo';
    }

    // Adiciona o evento ao botão diretamente, evitando erro caso ele não seja encontrado
    document.querySelector("button[onclick='toggleViewMode()']")?.addEventListener("click", toggleViewMode);
});
</script>