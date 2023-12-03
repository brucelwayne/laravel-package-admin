@php($user = \Illuminate\Support\Facades\Auth::guard('admin')->user())

<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <title>
        @if(empty($title))
            Mallria Admin
        @else
            {{$title}} - Mallria Admin
        @endif
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.bunny.net/css?family=Oswald:400,500,600,700,800,900&display=swap" rel="stylesheet"/>

    @stack('styles')
    @vite(['resources/scss/admin.scss'])

    @if(!empty(config('app.ga_4_id')))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{config('app.ga_4_id')}}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());

            gtag('config', '{{config('app.ga_4_id')}}');
        </script>
    @endif
</head>
<body class="font-sans antialiased bg-gray-50">

    <div class="page">
        <div class="header">
            @include('admin::components.nav')
        </div>
        <div class="main flex flex-row justify-start items-start">
            @include('admin::components.aside')
            <div class="main-content flex-1 p-4">
                @yield('content')
            </div>
        </div>
        <div class="footer">

        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
@vite([
    'resources/script/admin.js',
])
@stack('scripts')
</body>
</html>
