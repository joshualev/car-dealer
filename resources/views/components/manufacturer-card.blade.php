@props(['manufacturer'])

<x-card class="max-w-sm bg-gradient-to-b from-indigo-50 to-indigo-100 dark:from-gray-700 dark:to-gray-800 border border-indigo-200 dark:border-indigo-600">
    <div class="flex flex-col space-y-4">
        <!-- Name and Country -->
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-indigo-900 dark:text-indigo-200 truncate">
                {{ $manufacturer->name }}
            </h2>
            <span class="bg-indigo-200 dark:bg-indigo-700 text-indigo-800 dark:text-indigo-100 text-xs px-2 py-1 rounded-full">
                {{ $manufacturer->country }}
            </span>
        </div>

        <!-- Description -->
        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
            {{ Str::limit($manufacturer->description, 150, '...') }}
        </p>

        <!-- Button -->
        <a href="{{ route('manufacturers.show', $manufacturer->id) }}">
        <button class="mt-auto px-4 py-2 bg-indigo-500 text-white font-semibold rounded-lg hover:bg-indigo-600 dark:bg-indigo-700 dark:hover:bg-indigo-600 transition">
            Learn More
        </button>
        </a>
    </div>
</x-card>
