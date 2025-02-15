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
                    <div class="mb-6 p-6 bg-white shadow-md rounded-lg">
                        <form action="{{route('event.store')}}" method="POST">
                            @csrf
                            @method('POST')
                            <button type="submit">Criar Evento 🎈</button>
                        </form>
                    </div>
                    
                    <table class="w-full text-left text-sm text-gray-800">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4">Cliente</th>
                                <th class="py-2 px-4 text-center">Menu Escolhido</th>
                                <th class="py-2 px-4">Endereço</th>
                                <th class="py-2 px-4 text-center">Data</th>
                                <th class="py-2 px-4 text-center">CheckList</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)  
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 px-4 font-medium align-top">
                                        {{ $event->client->name}}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $event->menu->name }}
                                    </td>
                                    <td class="py-2 px-4 align-top">
                                        {{ $event->address->neighborhood }}, {{ $event->address->city }}/{{ $event->address->state }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        {{ \Carbon\Carbon::parse($event->date)->locale('pt_BR')->translatedFormat('d \d\e M. \d\e Y \à\s H:i') }}
                                    </td>
                                    <td class="py-2 px-4 text-center align-top">
                                        <a href="{{ route('event.checklist',['event_id'=> $event->id])}}">✅</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
        </div>
    </body>
</html>
