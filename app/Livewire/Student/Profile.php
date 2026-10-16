<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $timezone = null;

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public array $notificationPreferences = [
        'email' => true,
        'classes' => true,
        'bookings' => true,
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone;
        $this->notificationPreferences = array_replace($this->notificationPreferences, $user->notification_preferences ?? []);
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $attributes = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $emailChanged = $attributes['email'] !== $user->email;
        $user->forceFill([
            ...$attributes,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ])->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->dispatch('toast', message: $emailChanged
            ? 'Profile saved. Verify your new email address.'
            : 'Profile saved.');
    }

    public function saveNotificationPreferences(): void
    {
        $this->validate([
            'notificationPreferences.email' => ['boolean'],
            'notificationPreferences.classes' => ['boolean'],
            'notificationPreferences.bookings' => ['boolean'],
        ]);

        auth()->user()->update(['notification_preferences' => $this->notificationPreferences]);
        $this->dispatch('toast', message: 'Notification preferences saved.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', 'same:passwordConfirmation', Password::defaults()],
            'passwordConfirmation' => ['required'],
        ]);

        auth()->user()->forceFill([
            'password' => Hash::make($this->password),
            'remember_token' => null,
        ])->save();

        auth()->logout();
        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.student.profile')->layout('layouts::app', ['title' => 'My Profile']);
    }
}
