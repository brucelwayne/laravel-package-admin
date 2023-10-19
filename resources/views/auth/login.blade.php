@extends('layouts.www')

@section('content')
    <section class="my-20">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto lg:py-0">
            <div class="w-full bg-white rounded shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        Sign in to admin panel
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{route('admin.attempt-login')}}" method="post">
                        @csrf
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
                            <input type="email" name="email" id="email"
                                   placeholder="nam@mallria.com"
                                   value="{{ old('email') }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                            @if($errors->has('email'))
                                <div class="error mt-2 text-sm text-red-600 dark:text-red-500">{{ $errors->first('email') }}</div>
                            @endif
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                            <input type="password" name="password" id="password" placeholder="" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                        </div>
                        <div>
                            {!! htmlFormSnippet() !!}
                            @if($errors->has('g-recaptcha-response'))
                                <div class="error mt-2 text-sm text-red-600 dark:text-red-500">{{ $errors->first('g-recaptcha-response') }}</div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="remember" name="remember" aria-describedby="remember" type="checkbox" checked class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="remember" class="text-gray-500 dark:text-gray-300">Remember me</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full text-white px-4 lg:px-6 py-3 lg:py-4 text-sm lg:text-lg bg-primary-900 hover:bg-primary-800 text-white capitalize font-semibold rounded">
                            Sign in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    @push('recaptcha')
        {!! htmlScriptTagJsApi(['action'=>'admin.login']) !!}
    @endpush
@endsection