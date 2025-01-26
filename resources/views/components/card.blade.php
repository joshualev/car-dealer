@props(['class' => ''])

<div {{ $attributes->merge(['class' => "p-6 bg-gradient-to-tr from-gray-50 via-gray-100 to-white dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 {$class}"]) }}>
    {{ $slot }}
</div>
