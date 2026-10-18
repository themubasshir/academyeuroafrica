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
                    <form wire:submit="saveModule" class="flex flex-wrap gap-2">
                        <input wire:model="moduleTitle" placeholder="New module title" aria-label="New module title" class="input w-56">
                        <select wire:model="moduleStatus" aria-label="Module status" class="input w-36">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                        <button class="btn-primary btn-sm">+ Module</button>
                    </form>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse ($modules as $module)
                        <div class="card overflow-hidden">
                            <div class="flex items-center justify-between border-b border-paper-200 bg-paper-50 px-4 py-2.5">
                                <span class="text-sm font-semibold text-ink-900">{{ $module->title }} <span class="ml-2 text-xs font-normal text-ink-500">{{ ucfirst($module->status) }}</span></span>
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
                                        <span class="min-w-0 truncate text-ink-800">
                                            {{ $lesson->title }}
                                            <span class="text-xs text-ink-600">· {{ str_replace('_', ' ', $lesson->type) }} · {{ $lesson->is_required ? 'required' : 'optional' }} · {{ $lesson->status }}</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2 text-sm">
                                            <button wire:click="moveLesson({{ $lesson->id }}, 'up')" class="flex h-9 w-9 items-center justify-center rounded text-ink-500 hover:bg-paper-100" aria-label="Move lesson up">↑</button>
                                            <button wire:click="moveLesson({{ $lesson->id }}, 'down')" class="flex h-9 w-9 items-center justify-center rounded text-ink-500 hover:bg-paper-100" aria-label="Move lesson down">↓</button>
                                            <a href="{{ route('trainer.lesson.preview', [$course, $lesson]) }}" class="font-medium text-ink-600 hover:underline">Preview</a>
                                            <button wire:click="editLesson({{ $lesson->id }}, {{ $module->id }})" class="font-medium text-reg-700 hover:underline">Edit</button>
                                            <button wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete this lesson and its progress?" class="font-medium text-red-700 hover:underline">Delete</button>
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

            @if ($lessonModuleId)
                <section class="rounded-lg border border-reg-100 bg-reg-50/60 p-5">
                    <h3 class="h3">{{ $editingLessonId ? 'Edit lesson' : 'New lesson' }}</h3>
                    <div class="mt-3 space-y-3">
                        <input wire:model="lessonTitle" placeholder="Lesson title" aria-label="Lesson title" class="input">
                        <select wire:model="lessonModuleId" aria-label="Lesson module" class="input">
                            @foreach ($modules as $module)<option value="{{ $module->id }}">{{ $module->title }}</option>@endforeach
                        </select>
                        <select wire:model.live="lessonType" aria-label="Lesson type" class="input">
                            <option value="text">Text</option>
                            <option value="rich_text">Rich text</option>
                            <option value="pdf">PDF document</option>
                            <option value="video">Video</option>
                        </select>
                        @if ($lessonType === 'video')
                            <select wire:model.live="videoSource" aria-label="Video source" class="input">
                                <option value="hosted">Approved YouTube or Vimeo</option>
                                <option value="upload">Private uploaded video</option>
                            </select>
                            @if ($videoSource === 'hosted')
                                <input wire:model="videoId" placeholder="YouTube/Vimeo ID or URL" aria-label="Video ID or URL" class="input">
                            @else
                                <input type="file" wire:model="lessonUpload" accept="video/mp4,video/webm" aria-label="Upload lesson video" class="block w-full text-sm text-ink-600">
                                <p class="text-xs text-ink-500">MP4 or WebM, up to 512 MB. The hosting PHP upload limit may be lower.</p>
                            @endif
                            <input type="number" wire:model="videoDurationSeconds" min="1" max="86400" placeholder="Video duration in seconds" aria-label="Video duration in seconds" class="input">
                        @elseif ($lessonType === 'pdf')
                            <input type="file" wire:model="lessonUpload" accept="application/pdf" aria-label="Upload primary PDF" class="block w-full text-sm text-ink-600">
                            <p class="text-xs text-ink-500">The PDF is shown inside the lesson. Opening it does not mark the lesson complete.</p>
                        @endif
                        <textarea wire:model="lessonContent" rows="5" placeholder="Lesson text or supported HTML formatting" aria-label="Lesson content" class="input"></textarea>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <select wire:model="lessonStatus" aria-label="Lesson status" class="input">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <label class="flex min-h-11 items-center gap-2 text-sm text-ink-700">
                                <input type="checkbox" wire:model="lessonRequired" class="rounded text-reg-600"> Required for certificate
                            </label>
                            <label class="block text-sm text-ink-700">
                                <span class="label">Video completion %</span>
                                <input type="number" wire:model="completionThreshold" min="1" max="100" class="input mt-1">
                            </label>
                        </div>
                        @error('lessonUpload')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <div class="flex gap-2">
                            <button wire:click="saveLesson" class="btn-primary">Save lesson</button>
                            <button wire:click="$set('lessonModuleId', null)" class="btn-ghost">Cancel</button>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <section class="card-pad self-start">
            <h2 class="h3">Materials</h2>
            <form wire:submit="uploadMaterial" class="mt-3 space-y-3">
                <select wire:model.live="attachModuleId" aria-label="Attach material to" class="input">
                    <option value="">Whole course</option>
                    @foreach ($modules as $m)<option value="{{ $m->id }}">Module: {{ $m->title }}</option>@endforeach
                </select>
                @if ($attachModuleId)
                    <select wire:model="attachLessonId" aria-label="Attach to lesson (optional)" class="input">
                        <option value="">Module-level</option>
                        @foreach ($modules->firstWhere('id', (int) $attachModuleId)?->lessons ?? [] as $l)
                            <option value="{{ $l->id }}">Lesson: {{ $l->title }}</option>
                        @endforeach
                    </select>
                @endif
                <input wire:model="materialDescription" placeholder="Description (shown to students)" aria-label="Material description" class="input">
                <select wire:model.live="materialAudience" aria-label="Audience" class="input">
                    <option value="course">Whole course</option>
                    <option value="group">A group</option>
                    <option value="student">One student</option>
                </select>
                @if ($materialAudience === 'group')
                    <select wire:model="materialGroupId" aria-label="Group" class="input">
                        <option value="">Select group…</option>
                        @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                    </select>
                @elseif ($materialAudience === 'student')
                    <select wire:model="materialStudentId" aria-label="Student" class="input">
                        <option value="">Select student…</option>
                        @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                @endif
                <input type="file" wire:model="materialUpload" aria-label="Upload material" class="block w-full text-sm text-ink-600">
                @error('materialUpload')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                @error('materialGroupId')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                @error('materialStudentId')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                <button class="btn-primary">Upload</button>
            </form>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($materials as $m)
                    <li class="rounded-md border border-paper-200 bg-paper-50/60 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-ink-900">{{ $m->original_name }}</p>
                                <p class="mt-0.5 truncate text-xs text-ink-500">
                                    {{ $m->typeLabel() }} · {{ $m->humanSize() }}
                                    · {{ class_basename($m->attachable_type) }}@if($m->attachable && $m->attachable_type !== \App\Models\Course::class): {{ $m->attachable->title ?? '' }}@endif
                                    @if ($m->group) · <span class="text-seal-600">{{ $m->group->name }}</span>@endif
                                    @if ($m->student) · <span class="text-amber-700">{{ $m->student->name }}</span>@endif
                                </p>
                                @if ($m->description)<p class="mt-1 text-xs text-ink-600">{{ $m->description }}</p>@endif
                            </div>
                        </div>

                        @if ($renamingId === $m->id)
                            <div class="mt-2 flex gap-2">
                                <input wire:model="renameValue" class="input flex-1" aria-label="New name">
                                <button wire:click="saveRename" class="btn-primary btn-sm">Save</button>
                                <button wire:click="$set('renamingId', null)" class="btn-ghost btn-sm">✕</button>
                            </div>
                        @elseif ($replacingId === $m->id)
                            <div class="mt-2 flex items-center gap-2">
                                <input type="file" wire:model="materialReplace" class="w-full text-xs" aria-label="Replacement file">
                                <button wire:click="replaceMaterial" class="btn-primary btn-sm">Go</button>
                                <button wire:click="$set('replacingId', null)" class="btn-ghost btn-sm">✕</button>
                            </div>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <a href="{{ route('files.material', $m) }}" class="font-medium text-reg-700 hover:underline">Download</a>
                                <button wire:click="startRename({{ $m->id }})" class="font-medium text-ink-600 hover:underline">Rename</button>
                                <button wire:click="$set('replacingId', {{ $m->id }})" class="font-medium text-ink-600 hover:underline">Replace</button>
                                <button wire:click="deleteMaterial({{ $m->id }})" wire:confirm="Delete this file? Students will lose access." class="font-medium text-red-700 hover:underline">Delete</button>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="text-xs text-ink-500">No materials yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
