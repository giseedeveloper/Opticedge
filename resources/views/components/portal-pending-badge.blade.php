@props(['count' => 0, 'class' => ''])
@php $n = (int) $count; @endphp
@if($n > 0)
    <span
        {{ $attributes->merge(['class' => 'portal-pending-badge inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white shadow-sm '.$class]) }}
        aria-label="{{ $n }} pending"
    >{{ $n > 99 ? '99+' : $n }}</span>
@endif
