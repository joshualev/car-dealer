@props(['car'])

<x-card class="max-w-md bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700">
    <div class="flex flex-col space-y-6">
        <!-- Model -->
        <div>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                    {{ $car->model }}
                </h2>
                <!-- Colour -->
                <span class="inline-block px-3 py-1 rounded-full text-[12px] font-medium text-white" style="background-color: {{ $car->colour_code ?? '#6B7280' }}">
                    {{ ucfirst($car->colour) }}
                </span>
            </div>

            <div class="mt-2">
                <!-- Year -->
                <p class="text-lg text-blue-600 dark:text-blue-400 font-semibold">
                    {{ \Carbon\Carbon::parse($car->year)->format('Y') }}
                </p>
            </div>
        </div>
        <!-- Colour -->


        <!-- Manufacturer and Country of Origin -->
        <div class="mt-4 space-y-2">
            <p class="text-sm text-gray-700 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-gray-200">Manufactured by:</span>
                <a href="{{ route('manufacturers.show', $car->manufacturer->id) }}">{{ $car->manufacturer->name }}</a>
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-gray-200">Country of Origin:</span>
                {{ $car->manufacturer->country }}
            </p>
        </div>
    </div>
</x-card>
