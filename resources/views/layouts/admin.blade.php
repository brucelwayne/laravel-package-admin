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

    @vite(['resources/scss/admin.scss'])
    @stack('styles')

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
            <aside class="p-2 z-40 w-64 h-screen transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar">
                <div class="h-full pb-4 overflow-y-auto bg-white dark:bg-gray-800">
                    <ul class="space-y-2 font-medium">
                        <li>
                            <a href="{{route('admin.index')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                                </svg>

                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <button type="button"
                                    class="@if(\Illuminate\Support\Facades\Route::is('admin.blog.*')) bg-gray-100 dark:bg-gray-700  @endif flex items-center w-full p-2 text-base text-gray-900 transition duration-75 rounded group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    aria-controls="dropdown-blog"
                                    data-collapse-toggle="dropdown-blog">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Blog</span>
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>
                            <ul id="dropdown-blog" class="@if(!\Illuminate\Support\Facades\Route::is('admin.blog.*')) hidden @endif py-2 space-y-1">
                                <li>
                                    <a href="{{route('admin.blog.create.show')}}" class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                        New Blog
                                    </a>
                                </li>
                                <li>
                                    <a href="{{route('admin.blog.index')}}" class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                        Blog Posts
                                    </a>
                                </li>
                                <li>
                                    <a href="{{route('admin.blog.tags.show')}}" class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                        Comments
                                    </a>
                                </li>
                                <li>
                                    <a href="{{route('admin.blog.cates.show')}}" class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                        Categories
                                    </a>
                                </li>
                                <li>
                                    <a href="{{route('admin.blog.tags.show')}}" class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                        Tags
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </aside>
            <div class="main-content flex-1 p-10">
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
