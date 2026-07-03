@props([
    'user' => null,
    'inputId' => 'profile_image',
    'removeInputId' => 'remove_profile_image',
])

@php
    $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'profile_image');
@endphp

@if($hasColumn)
    <div>
        <label for="{{ $inputId }}" class="admin-prod-label">Profile photo</label>
        <p class="admin-prod-form-hint !mt-0 mb-3">Upload a photo to show on the organization tree. JPG, PNG, or WebP up to 5 MB.</p>

        @if($user?->profile_image_url)
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}"
                    class="h-16 w-16 rounded-full object-cover border-2 border-slate-200 shadow-sm">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remove_profile_image" id="{{ $removeInputId }}" value="1"
                        class="rounded border-slate-300 text-[#232f3e] focus:ring-[#232f3e]/20">
                    Remove current photo
                </label>
            </div>
        @endif

        <input type="file" id="{{ $inputId }}" name="profile_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            class="admin-prod-file">
        @error('profile_image')
            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
        @enderror
    </div>
@endif
