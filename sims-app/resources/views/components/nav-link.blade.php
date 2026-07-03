@props(['active', 'color' => 'blue'])

@php
$colors = [
    'blue' => [
        'active' => 'bg-blue-50 text-blue-600 border-r-4 border-blue-600',
        'inactive' => 'text-gray-600 hover:bg-blue-50 hover:text-blue-600',
    ],
    'indigo' => [
        'active' => 'bg-indigo-50 text-indigo-600 border-r-4 border-indigo-600',
        'inactive' => 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-600',
    ],
    'violet' => [
        'active' => 'bg-violet-50 text-violet-600 border-r-4 border-violet-600',
        'inactive' => 'text-gray-600 hover:bg-violet-50 hover:text-violet-600',
    ],
    'emerald' => [
        'active' => 'bg-emerald-50 text-emerald-600 border-r-4 border-emerald-600',
        'inactive' => 'text-gray-600 hover:bg-emerald-50 hover:text-emerald-600',
    ],
    'pink' => [
        'active' => 'bg-pink-50 text-pink-600 border-r-4 border-pink-600',
        'inactive' => 'text-gray-600 hover:bg-pink-50 hover:text-pink-600',
    ],
    'amber' => [
        'active' => 'bg-amber-50 text-amber-600 border-r-4 border-amber-600',
        'inactive' => 'text-gray-600 hover:bg-amber-50 hover:text-amber-600',
    ],
    'teal' => [
        'active' => 'bg-teal-50 text-teal-600 border-r-4 border-teal-600',
        'inactive' => 'text-gray-600 hover:bg-teal-50 hover:text-teal-600',
    ],
    'purple' => [
        'active' => 'bg-purple-50 text-purple-600 border-r-4 border-purple-600',
        'inactive' => 'text-gray-600 hover:bg-purple-50 hover:text-purple-600',
    ],
    'rose' => [
        'active' => 'bg-rose-50 text-rose-600 border-r-4 border-rose-600',
        'inactive' => 'text-gray-600 hover:bg-rose-50 hover:text-rose-600',
    ],
    'red' => [
        'active' => 'bg-red-50 text-red-600 border-r-4 border-red-600',
        'inactive' => 'text-gray-600 hover:bg-red-50 hover:text-red-600',
    ],
    'cyan' => [
        'active' => 'bg-cyan-50 text-cyan-600 border-r-4 border-cyan-600',
        'inactive' => 'text-gray-600 hover:bg-cyan-50 hover:text-cyan-600',
    ],
    'orange' => [
        'active' => 'bg-orange-50 text-orange-600 border-r-4 border-orange-600',
        'inactive' => 'text-gray-600 hover:bg-orange-50 hover:text-orange-600',
    ],
    'fuchsia' => [
        'active' => 'bg-fuchsia-50 text-fuchsia-600 border-r-4 border-fuchsia-600',
        'inactive' => 'text-gray-600 hover:bg-fuchsia-50 hover:text-fuchsia-600',
    ],
    'slate' => [
        'active' => 'bg-slate-50 text-slate-600 border-r-4 border-slate-600',
        'inactive' => 'text-gray-600 hover:bg-slate-50 hover:text-slate-600',
    ],
];

$activeClasses = $colors[$color]['active'] ?? $colors['blue']['active'];
$inactiveClasses = $colors[$color]['inactive'] ?? $colors['blue']['inactive'];

$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-6 py-3 transition-all duration-200 ' . $activeClasses
            : 'flex items-center gap-3 px-6 py-3 transition-all duration-200 ' . $inactiveClasses;
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
