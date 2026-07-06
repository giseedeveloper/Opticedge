<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public function mount(): void
    {
        $this->redirect(route('auth.google'), navigate: true);
    }
}; ?>

<div class="text-center text-sm text-slate-600">Redirecting to Google Sign-In…</div>
