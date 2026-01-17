@props(['active', 'color' => 'blue'])

@php
$activeClasses = "bg-{$color}-50 text-{$color}-600 border-r-4 border-{$color}-600";
$inactiveClasses = "text-gray-600 hover:bg-gray-50 hover:text-{$color}-600";

$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-6 py-3 transition-all duration-200 ' . $activeClasses
            : 'flex items-center gap-3 px-6 py-3 transition-all duration-200 ' . $inactiveClasses;
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
