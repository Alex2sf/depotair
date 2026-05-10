<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-900 to-purple-900 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
        <h1 class="text-4xl font-bold text-center mb-8 text-indigo-700">OWNER PANEL</h1>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('owner.doLogin') }}" method="POST">
            @csrf
            <input type="email" name="email" placeholder="Email" required
                   class="w-full border-2 border-gray-300 p-4 rounded-xl mb-4 text-lg focus:border-indigo-500 focus:outline-none">

            <input type="password" name="password" placeholder="Password" required
                   class="w-full border-2 border-gray-300 p-4 rounded-xl mb-8 text-lg focus:border-indigo-500 focus:outline-none">

            <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl text-xl font-bold hover:bg-indigo-700 transition">
                MASUK SEBAGAI OWNER
            </button>
        </form>
    </div>
</body>
</html>