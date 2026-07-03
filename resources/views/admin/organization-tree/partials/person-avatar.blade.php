@props(['user', 'size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-14 w-14 text-lg',
        default => 'h-11 w-11 text-sm',
    };
@endphp

@if($user->profile_image_url)
    <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}"
        {{ $attributes->merge(['class' => "rounded-full object-cover shrink-0 bg-slate-100 border border-slate-200/80 {$sizeClasses}"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full shrink-0 font-bold text-white bg-slate-400 border border-slate-200/80 {$sizeClasses}"]) }}
        aria-hidden="true">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </span>
@endif
