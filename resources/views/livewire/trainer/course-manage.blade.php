<div class="mx-auto max-w-5xl space-y-8">
    <div>
        <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
        <h1 class="mt-2 h1">{{ $course->title }}</h1>
        <p class="text-sm text-gray-500">{{ ucfirst($course->status) }} · {{ $course->programme->name }}</p>
    </div>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Modules & lessons</h2>
                    <form wire:submit="saveModule" class="flex gap-2">
                        <input wire:model="moduleTitle" placeholder="New module title" aria-label="New module title"
                               class="input">
                        <button class="btn-primary btn-sm">+ Module</button>
                    </form>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($modules as $module)
                        <div class="rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-2">
                                <span class="font-medium text-sm">{{ $module->title }}</span>
                                <span class="flex gap-1">
                                    <button wire:click="moveModule({{ $module->id }}, 'up')" class="min-h-7 min-w-7 rounded px-1.5 py-0.5 text-gray-500 hover:bg-gray-200" aria-label="Move module up">↑</button>
                                    <button wire:click="moveModule({{ $module->id }}, 'down')" class="min-h-7 min-w-7 rounded px-1.5 py-0.5 text-gray-500 hover:bg-gray-200" aria-label="Move module down">↓</button>
                                    <button wire:click="editLesson(null, {{ $module->id }})" class="min-h-7 min-w-7 rounded px-1.5 py-0.5 text-eamn-700 hover:bg-eamn-100" aria-label="Add lesson">+</button>
                                    <button wire:click="deleteModule({{ $module->id }})" wire:confirm="Delete module?" class="min-h-7 min-w-7 rounded px-1.5 py-0.5 text-red-600 hover:bg-red-50" aria-label="Delete module">✕</button>
                                </span>
                            </div>
                            <ul class="divide-y divide-gray-100">
                                @foreach ($module->lessons as $lesson)
                                    <li class="flex items-center justify-between px-4 py-2 text-sm">
                                        <span>{{ $lesson->title }} <span class="text-xs text-gray-400">({{ $lesson->type }})</span></span>
                                        <span class="flex gap-2">
                                            <button wire:click="editLesson({{ $lesson->id }}, {{ $module->id }})" class="text-eamn-700">Edit</button>
                                            <button wire:click="deleteLesson({{ $lesson->id }})" class="text-red-600">Delete</button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500">No modules yet.</p>
                    @endforelse
                </div>
            </section>

            @if ($attachModuleId)
                <section class="rounded-xl border border-eamn-200 bg-eamn-50 p-5">
                    <h3 class="font-semibold">{{ $editingLessonId ? 'Edit lesson' : 'New lesson' }}</h3>
                    <div class="mt-3 space-y-3">
                        <input wire:model="lessonTitle" placeholder="Lesson title" aria-label="Lesson title" class="block w-full input">
                        <select wire:model.live="lessonType" aria-label="Lesson type" class="block w-full input">
                            <option value="text">Text</option>
                            <option value="video">Video</option>
                        </select>
                        @if ($lessonType === 'video')
                            <input wire:model="videoId" placeholder="Video ID (YouTube/Vimeo/embed URL)" aria-label="Video ID" class="block w-full input">
                        @endif
                        <textarea wire:model="lessonContent" rows="4" placeholder="Content" aria-label="Lesson content" class="block w-full input"></textarea>
                        <div class="flex gap-2">
                            <button wire:click="saveLesson" class="btn-primary">Save lesson</button>
                            <button wire:click="$set('attachModuleId', null)" class="rounded-lg px-4 py-2 text-sm text-gray-600">Cancel</button>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <section class="card-pad">
            <h2 class="font-semibold">Materials</h2>
            <form wire:submit="uploadMaterial" class="mt-3 space-y-3">
                <select wire:model="attachModuleId" aria-label="Attach material to" class="block w-full input">
                    <option value="">Whole course</option>
                    @foreach ($modules as $m)<option value="{{ $m->id }}">Module: {{ $m->title }}</option>@endforeach
                </select>
                <input type="file" wire:model="materialUpload" aria-label="Upload material" class="block w-full text-sm">
                @error('materialUpload')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button class="btn-primary">Upload</button>
            </form>
            <ul class="mt-4 space-y-1 text-sm">
                @foreach ($materials as $m)
                    <li class="truncate text-gray-600">📄 {{ $m->original_name }}</li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
