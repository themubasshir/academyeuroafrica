<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.certificates') }}" class="link text-sm">&larr; Certificates</a>
    <x-ui.page-head :register="$course->programme->name" :title="'Certification — '.$course->title" folio="policy" />

    @if (session('saved'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>@endif

    <form wire:submit="save" class="space-y-6">
        <section class="card-pad">
            <label class="flex items-center gap-3">
                <input type="checkbox" wire:model.live="form.enabled" class="h-4 w-4 rounded border-paper-300 text-reg-600">
                <span class="font-semibold text-ink-900">Enable certification for this course</span>
            </label>
        </section>

        @if ($form['enabled'])
            <section class="card-pad space-y-4">
                <h2 class="h3">Completion requirements</h2>
                @foreach ([
                    'require_lessons' => 'All published lessons completed',
                    'require_quizzes' => 'All published quizzes passed',
                    'require_assignments' => 'All published assignments passed',
                    'require_trainer_approval' => 'Trainer approval required',
                    'require_admin_approval' => 'Administrator approval required',
                ] as $key => $label)
                    <label class="flex items-center gap-3">
                        <input type="checkbox" wire:model.live="form.{{ $key }}" class="h-4 w-4 rounded border-paper-300 text-reg-600">
                        <span class="text-sm text-ink-700">{{ $label }}</span>
                    </label>
                @endforeach

                @if ($form['require_quizzes'])
                    <div class="ml-7">
                        <label class="block text-xs font-medium text-ink-600">Min. quiz score % <span class="text-ink-400">(blank = quiz's own pass mark)</span></label>
                        <input type="number" wire:model="form.min_quiz_percent" min="0" max="100" class="input mt-1 w-32">
                        @error('form.min_quiz_percent')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endif
                @if ($form['require_assignments'])
                    <div class="ml-7">
                        <label class="block text-xs font-medium text-ink-600">Min. assignment score % <span class="text-ink-400">(blank = 50%)</span></label>
                        <input type="number" wire:model="form.min_assignment_percent" min="0" max="100" class="input mt-1 w-32">
                        @error('form.min_assignment_percent')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-ink-700">Min. live-session attendance %</label>
                    <p class="mt-0.5 text-xs text-ink-500">Only counts sessions already held that the student was invited to. Leave blank to skip.</p>
                    <input type="number" wire:model="form.min_attendance_percent" min="0" max="100" class="input mt-1 w-32" placeholder="e.g. 75">
                    @error('form.min_attendance_percent')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="card-pad space-y-4">
                <h2 class="h3">Issuance</h2>
                <label class="flex items-center gap-3">
                    <input type="checkbox" wire:model="form.auto_issue" class="h-4 w-4 rounded border-paper-300 text-reg-600">
                    <span class="text-sm text-ink-700">Issue automatically when requirements met <span class="text-ink-400">(off = admin issues manually)</span></span>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ink-700">Signatory name <span class="text-ink-400 font-normal">(optional)</span></label>
                        <input wire:model="form.signatory_name" class="input mt-1" placeholder="e.g. Dr Amara Osei">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-700">Signatory title</label>
                        <input wire:model="form.signatory_title" class="input mt-1" placeholder="e.g. Director of Studies">
                    </div>
                </div>
            </section>
        @endif

        <button class="btn-primary">Save policy</button>
    </form>
</div>
