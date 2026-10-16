<div class="mx-auto max-w-4xl space-y-8">
    <x-ui.page-head register="Student register" title="My profile" :folio="'Ref EAMN-'.auth()->id()" />

    <form wire:submit="saveProfile" class="card-pad space-y-5">
        <div><h2 class="h3">Personal details</h2><p class="muted mt-1">Your role, account status and learning records are managed by the Academy.</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="name" class="label">Full name</label><input wire:model="name" id="name" type="text" autocomplete="name" class="input">@error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="label">Email address</label><input wire:model="email" id="email" type="email" autocomplete="email" class="input">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="timezone" class="label">Time zone</label><input wire:model="timezone" id="timezone" type="text" autocomplete="off" placeholder="Africa/Nairobi" class="input">@error('timezone')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
        </div>
        @if (! auth()->user()->hasVerifiedEmail())
            <p class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Your email is not verified. Check your inbox for a verification link.</p>
        @endif
        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Save profile</button>
    </form>

    <form wire:submit="saveNotificationPreferences" class="card-pad space-y-5">
        <div><h2 class="h3">Notification preferences</h2><p class="muted mt-1">Security and transaction notices are always sent when required.</p></div>
        <div class="space-y-3">
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.email" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Email updates about enrolments and certificates</span></label>
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.classes" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Upcoming live-class reminders</span></label>
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.bookings" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Private-training booking updates</span></label>
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.assessments" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Assignment and quiz results</span></label>
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.certificates" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Certificate updates</span></label>
            <label class="flex items-center gap-3"><input wire:model="notificationPreferences.refunds" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600"><span class="text-sm">Refund and access updates</span></label>
        </div>
        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Save preferences</button>
    </form>

    <form wire:submit="changePassword" class="card-pad space-y-5">
        <div><h2 class="h3">Account security</h2><p class="muted mt-1">Changing your password signs you out of this session and invalidates remembered sign-ins.</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="current-password" class="label">Current password</label><input wire:model="currentPassword" id="current-password" type="password" autocomplete="current-password" class="input">@error('currentPassword')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="new-password" class="label">New password</label><input wire:model="password" id="new-password" type="password" autocomplete="new-password" class="input">@error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="password-confirmation" class="label">Confirm new password</label><input wire:model="passwordConfirmation" id="password-confirmation" type="password" autocomplete="new-password" class="input"></div>
        </div>
        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Change password</button>
    </form>
</div>
