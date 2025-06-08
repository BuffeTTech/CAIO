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
            <h1 class="text-xl font-bold">Adicionar elemento ao menu {{ $menu->name }}</h1>

            <form action="" method="get" class="flex w-full">
                <input type="text" name="query" id="" class="w-50 h-12 p-4 border-b-2 border-gray-300">
                <button type="submit" class="rounded-md bg-slate-800 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-700 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none w-50">Pesquisar</button>
            </form>

            <div id="dados">
                @if($items)
                <h2>Itens</h2>
                    <ul>
                        @foreach ($items as $item)
                            <li>
                                <form action="{{route('menu.store_item_to_menu', ['menu_id'=>$menu->id])}}" method="post">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition">Adicionar {{ $item->name }}</button>
                                    <input type="hidden" value="{{ $item->id }}" name="item_id">
                                </form>
                                <br>
                            </li>                        
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </body>
</html>
