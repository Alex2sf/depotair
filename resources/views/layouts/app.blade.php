<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name', 'Depot Kasir') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Shopee Orange: #EE4D2D */
        .bg-shopee { background-color: #EE4D2D; }
        .text-shopee { color: #EE4D2D; }
        .border-shopee { border-color: #EE4D2D; }
        body { padding-bottom: 70px; /* Space for bottom nav */ background-color: #F5F5F5; } 
    </style>
</head>
<body class="font-sans antialiased text-gray-900">

    <!-- TOP HEADER (Mobile Style) -->
    <header class="bg-shopee text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-md mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="font-bold text-lg tracking-wide">Depot Online</a>
            
            <div class="flex items-center gap-4">
                <!-- Chat/Help -->
                <a href="https://wa.me/6287727777302" target="_blank">
                    <i class="fas fa-comment-dots text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
    <div x-data="{show: true}" x-init="setTimeout(() => show = false, 3000)" x-show="show" class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-full shadow-lg z-[70] text-sm font-bold flex items-center gap-2 animate-bounce-in">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div x-data="{show: true}" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-6 py-3 rounded-full shadow-lg z-[70] text-sm font-bold flex items-center gap-2 animate-shake">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <!-- MAIN CONTENT -->
    <main class="max-w-md mx-auto mb-20">
        @yield('content')
    </main>

    <!-- BOTTOM NAVIGATION (Mobile Style) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 max-w-md mx-auto">
        <div class="grid grid-cols-3 h-16">
            <!-- Belanja (Home) -->
            <a href="{{ route('customer.home') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('customer.home') ? 'text-shopee' : 'text-gray-500' }}">
                <i class="fas fa-store text-xl mb-1"></i>
                <span class="text-[10px]">Belanja</span>
            </a>
            
            <!-- Track Pesanan -->
            <a href="{{ route('track.form') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('track.*') ? 'text-shopee' : 'text-gray-500' }}">
                <i class="fas fa-search-location text-xl mb-1"></i>
                <span class="text-[10px]">Track Pesanan</span>
            </a>

            <!-- Keranjang -->
            <a href="{{ route('cart.show') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('cart.show') ? 'text-shopee' : 'text-gray-500' }}">
                 <div class="relative">
                    <i class="fas fa-shopping-cart text-xl mb-1"></i>
                    @if(session()->has('cart') && collect(session('cart'))->sum('qty') > 0)
                    <span class="absolute -top-1 -right-2 bg-shopee text-white text-[9px] font-bold px-1 rounded-full border border-white">
                        {{ collect(session('cart'))->sum('qty') }}
                    </span>
                    @endif
                 </div>
                <span class="text-[10px]">Keranjang</span>
            </a>
        </div>
    </nav>

</body>
</html>