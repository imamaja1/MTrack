<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-modal id="delete_user_modal" title="{{ __('Are you sure you want to delete your account?') }}">
    <form method="POST" wire:submit="deleteUser" class="space-y-4">
        <p class="text-sm text-base-content/70">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <x-input wire:model="password" label="{{ __('Password') }}" type="password" icon="o-key" required />

        <x-slot:actions>
            <x-button label="{{ __('Cancel') }}" onclick="delete_user_modal.close()" class="btn-ghost" />
            <x-button label="{{ __('Delete account') }}" type="submit" class="btn-error" data-test="confirm-delete-user-button" />
        </x-slot:actions>
    </form>
</x-modal>
