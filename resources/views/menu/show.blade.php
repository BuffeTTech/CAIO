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
                
                <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $menu->name }}</h1>
                
                <!-- Tabela de Itens -->
                <table class="w-full text-left text-sm text-gray-800">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4">Itens</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $key => $menuItem)  
                                <tr class="border-b border-gray-200">
                                        <td class="py-2 px-4 font-medium align-top">{{ $menuItem->name }}</td>
                                </tr>
                        @endforeach
                    </tbody>
                </table>
                <br>
                <table class="w-full text-left text-sm text-gray-800">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4">Itens Fixos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fixedItems as $key => $menuFixedItem)  
                                <tr class="border-b border-gray-200">
                                        <td class="py-2 px-4 font-medium align-top">{{ $menuFixedItem->name }}</td>
                                </tr>
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