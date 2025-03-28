<aside class="p-2 z-40 w-64 h-screen transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full pb-4 overflow-y-auto bg-white">
        <ul class="space-y-2 text-sm">
            <li>
                <a href="{{route('admin.dashboard')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{route('admin.brands')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                    <span class="ml-3">Brands</span>
                </a>
                <ul class="ml-4">
                    <li>
                        <a href="{{route('admin.brands.create-new-brand')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <span class="ml-3">Create New Brand</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.brands.entries')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <span class="ml-3">Entries</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.brands.entries')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <span class="ml-3">Establishing</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.brands.entries')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <span class="ml-3">Feedback</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('admin.brands.entries')}}" class="flex items-center p-2 text-gray-900 rounded dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <span class="ml-3">Venting</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</aside>