<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('Registration Pending Approval') }}</h2>
        <p class="mb-4">
            {{ __('Your dealer account is currently under review by an administrator. You will be able to sign in once it is approved.') }}
        </p>
        <p>
            {{ __('You will be notified once your account is approved. Please check your email later or contact support if you have questions.') }}
        </p>
    </div>

    <div class="flex items-center justify-end mt-4">
        <a href="{{ route('welcome') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
            {{ __('Return Home') }}
        </a>
    </div>
</x-guest-layout>