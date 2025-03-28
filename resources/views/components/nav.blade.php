<nav class="z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start">
                <a href="{{route('admin.dashboard')}}" class="flex ml-2 md:mr-24 font-bold uppercase text-lg">
                    Mallria Admin
                </a>
            </div>
            <div class="flex-1">
                <ul class="flex flex-wrap items-center justify-start space-x-4 font-semibold">
                    <li>
                        <a href="{{route('admin.dashboard')}}"
                           class=" text-gray-700 hover:text-gray-800 hover:underline underline-offset-4">
                            Home
                        </a>
                    </li>
                </ul>
            </div>
            <div class="flex items-center">
                @if(!empty($user))
                    <div class="flex items-center ml-3">
                        <div>
                            <button type="button" class="flex justify-center items-center text-gray-900 hover:text-gray-700 dark:text-white" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                                <span class="sr-only">Open user menu</span>
                                {{$user->name}}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-1">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                </svg>
                            </button>
                        </div>
                        <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
                            <div class="px-4 py-3" role="none">
                                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                                    {{$user->email}}
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                        Profile
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                        Settings
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="btn-nav-logout block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                        Sign out
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>