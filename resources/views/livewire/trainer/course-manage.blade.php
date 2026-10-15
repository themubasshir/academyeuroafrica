<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="register">{{ $course->programme->name }}</p>
                <h1 class="h1 mt-1.5">{{ $course->title }}</h1>
            </div>
            <span @class(['badge', 'badge-green' => $course->status === 'published', 'badge-gray' => $course->status !== 'published'])>{{ ucfirst($course->status) }}</span>
        </div>
    </div>

    <x-trainer.course-nav :course="$course" active="manage" />

    @if (session('saved'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>@endif

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="register-rule"><h2 class="register shrink-0">Modules &amp; lessons</h2></div>
                    <form wire:submit="saveModule" class="flex gap-2">
                        <input wire:model="moduleTitle" placeholder="New module title" aria-label="New module title" class="input w-56">
                        <button class="btn-primary btn-sm">+ Module</button>
                    </form>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse ($modules as $module)
                        <div class="card overflow-hidden">
                            <div class="flex items-center justify-between border-b border-paper-200 bg-paper-50 px-4 py-2.5">
                                <span class="text-sm font-semibold text-ink-900">{{ $module->title }}</span>
                                <span class="flex items-center gap-1">
                                    <button wire:click="moveModule({{ $module->id }}, 'up')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-500 transition hover:bg-paper-100 hover:text-ink-900" aria-label="Move module up">↑</button>
                                    <button wire:click="moveModule({{ $module->id }}, 'down')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-500 transition hover:bg-paper-100 hover:text-ink-900" aria-label="Move module down">↓</button>
                                    <button wire:click="editLesson(null, {{ $module->id }})" class="flex h-11 w-11 items-center justify-center rounded-md text-reg-700 transition hover:bg-reg-50" aria-label="Add lesson">+</button>
                                    <button wire:click="deleteModule({{ $module->id }})" wire:confirm="Delete module?" class="flex h-11 w-11 items-center justify-center rounded-md text-red-700 transition hover:bg-red-50" aria-label="Delete module">✕</button>
                                </span>
                            </div>
                            <ul class="divide-y divide-paper-200">
                                @foreach ($module->lessons as $lesson)
                                    <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                        <span class="min-w-0 truncate text-ink-800">{{ $lesson->title }} <span class="text-xs text-ink-600">({{ $lesson->type }})</span></span>
                                        <span class="flex shrink-0 gap-3 text-sm">
                                            <button wire:click="editLesson({{ $lesson->id }}, {{ $module->id }})" class="font-medium text-reg-700 hover:underline">Edit</button>
                                            <button wire:click="deleteLesson({{ $lesson->id }})" class="font-medium text-red-700 hover:underline">Delete</button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <x-ui.empty title="No modules yet" message="Add a module above to structure the course." />
                    @endforelse
                </div>
            </section>

            @if ($attachModuleId)
                <section class="rounded-lg border border-reg-100 bg-reg-50/60 p-5">
                    <h3 class="h3">{{ $editingLessonId ? 'Edit lesson' : 'New lesson' }}</h3>
                    <div class="mt-3 space-y-3">
                        <input wire:model="lessonTitle" placeholder="Lesson title" aria-label="Lesson title" class="input">
                        <select wire:model.live="lessonType" aria-label="Lesson type" class="input">
                            <option value="text">Text</option>
                            <option value="video">Video</option>
                        </select>
                        @if ($lessonType === 'video')
                            <input wire:model="videoId" placeholder="Video ID (YouTube/Vimeo/embed URL)" aria-label="Video ID" class="input">
                        @endif
                        <textarea wire:model="lessonContent" rows="4" placeholder="Content" aria-label="Lesson content" class="input"></textarea>
                        <div class="flex gap-2">
                            <button wire:click="saveLesson" class="btn-primary">Save lesson</button>
                            <button wire:click="$set('attachModuleId', null)" class="btn-ghost">Cancel</button>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <section class="card-pad self-start">
            <h2 class="h3">Materials</h2>
            <form wire:submit="uploadMaterial" class="mt-3 space-y-3">
                <select wire:model="attachModuleId" aria-label="Attach material to" class="input">
                    <option value="">Whole course</option>
                    @foreach ($modules as $m)<option value="{{ $m->id }}">Module: {{ $m->title }}</option>@endforeach
                </select>
                <input type="file" wire:model="materialUpload" aria-label="Upload material" class="block w-full text-sm text-ink-600">
                @error('materialUpload')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                <button class="btn-primary">Upload</button>
            </form>
            <ul class="mt-4 space-y-1.5 text-sm">
                @foreach ($materials as $m)
                    <li class="flex items-center gap-2 truncate text-ink-600">
                        <svg class="h-4 w-4 shrink-0 text-ink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 3h14a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V4a1 1 0 011-1z"/></svg>
                        <span class="truncate">{{ $m->original_name }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
