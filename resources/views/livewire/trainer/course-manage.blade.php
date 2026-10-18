<div class="mx-auto max-w-6xl space-y-6">
    @php
        $editingLesson = $editingLessonId ? $modules->flatMap->lessons->firstWhere('id', $editingLessonId) : null;
    @endphp

    <div>
        <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0"><p class="register">{{ $course->programme->name }}</p><h1 class="h1 mt-1.5">{{ $course->title }}</h1></div>
            <span @class(['badge-green' => $course->status === 'published', 'badge-gray' => $course->status !== 'published'])>{{ ucfirst($course->status) }}</span>
        </div>
    </div>

    <x-trainer.course-nav :course="$course" active="manage" />

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="curriculum-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 id="curriculum-heading" class="h2">Curriculum</h2><p class="mt-1 text-sm text-ink-600">Arrange modules and lessons in the order learners will follow.</p></div>
                <button type="button" wire:click="createModule" class="btn-primary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    Add Module
                </button>
            </div>

            <div class="mt-5 space-y-5">
                @forelse ($modules as $module)
                    <article class="card overflow-hidden" wire:key="module-{{ $module->id }}">
                        <header class="flex flex-col gap-3 border-b border-paper-300 bg-paper-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-ink-950">{{ $module->title }}</h3>
                                    <span @class(['badge-green' => $module->status === 'published', 'badge-gray' => $module->status !== 'published'])>{{ ucfirst($module->status) }}</span>
                                </div>
                                <p class="folio mt-1.5">{{ $module->lessons->count() }} {{ Str::plural('lesson', $module->lessons->count()) }} · Order {{ $module->sort_order }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1">
                                <button type="button" wire:click="moveModule({{ $module->id }}, 'up')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-600 hover:bg-paper-100 hover:text-ink-950" aria-label="Move {{ $module->title }} up"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m7 11 5-5 5 5M12 6v12" /></svg></button>
                                <button type="button" wire:click="moveModule({{ $module->id }}, 'down')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-600 hover:bg-paper-100 hover:text-ink-950" aria-label="Move {{ $module->title }} down"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m7 13 5 5 5-5M12 18V6" /></svg></button>
                                <button type="button" wire:click="editModule({{ $module->id }})" class="btn-ghost btn-sm">Edit</button>
                                <button type="button" wire:click="editLesson(null, {{ $module->id }})" class="btn-secondary btn-sm">Add Lesson</button>
                                <button type="button" wire:click="deleteModule({{ $module->id }})" wire:confirm="Delete {{ $module->title }} and all {{ $module->lessons->count() }} lessons? Lesson progress and attached lesson files will also be permanently deleted." class="btn-ghost btn-sm !text-red-700">Delete</button>
                            </div>
                        </header>

                        <ul class="divide-y divide-paper-200" aria-label="Lessons in {{ $module->title }}">
                            @forelse ($module->lessons as $lesson)
                                @php
                                    $typeIcon = match ($lesson->type) {
                                        'pdf' => 'M7 3h7l5 5v13H7zM14 3v6h5M10 13h6M10 17h4',
                                        'video' => 'M4 6h11v12H4zM15 10l5-3v10l-5-3z',
                                        'rich_text' => 'M5 4h14M9 4v16M5 20h8',
                                        default => 'M6 4h12v16H6zM9 8h6M9 12h6M9 16h4',
                                    };
                                @endphp
                                <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="lesson-{{ $lesson->id }}">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-paper-300 bg-paper-50 text-ink-600" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcon }}" /></svg></span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink-900">{{ $lesson->title }}</p>
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                <span @class(['badge-green' => $lesson->status === 'published', 'badge-gray' => $lesson->status !== 'published'])>{{ ucfirst($lesson->status) }}</span>
                                                <span class="badge-blue">{{ str_replace('_', ' ', ucfirst($lesson->type)) }}</span>
                                                <span class="badge-gray">{{ $lesson->is_required ? 'Required' : 'Optional' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap items-center gap-1 sm:justify-end">
                                        <button type="button" wire:click="moveLesson({{ $lesson->id }}, 'up')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-600 hover:bg-paper-100" aria-label="Move {{ $lesson->title }} up"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m7 14 5-5 5 5" /></svg></button>
                                        <button type="button" wire:click="moveLesson({{ $lesson->id }}, 'down')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-600 hover:bg-paper-100" aria-label="Move {{ $lesson->title }} down"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m7 10 5 5 5-5" /></svg></button>
                                        <a href="{{ route('trainer.lesson.preview', [$course, $lesson]) }}" class="btn-ghost btn-sm">Preview</a>
                                        <button type="button" wire:click="editLesson({{ $lesson->id }}, {{ $module->id }})" class="btn-ghost btn-sm">Edit</button>
                                        <button type="button" wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete {{ $lesson->title }}? Learner progress and attached lesson files will be permanently deleted." class="btn-ghost btn-sm !text-red-700">Delete</button>
                                    </div>
                                </li>
                            @empty
                                <li class="px-4 py-5"><x-ui.empty title="No lessons in this module" message="Add a text, PDF, or video lesson to begin this section." /></li>
                            @endforelse
                        </ul>
                    </article>
                @empty
                    <x-ui.empty title="No modules yet" message="Add a module to establish the first section of this course.">
                        <button type="button" wire:click="createModule" class="btn-primary btn-sm">Add Module</button>
                    </x-ui.empty>
                @endforelse
            </div>
        </section>

        <section class="card-pad self-start" aria-labelledby="materials-heading">
            <h2 id="materials-heading" class="h2">Course materials</h2>
            <p class="mt-1 text-sm text-ink-600">Private files for enrolled learners or assigned trainers.</p>
            <form wire:submit="uploadMaterial" class="mt-5 space-y-4">
                <div><label for="material-level" class="label">Attach to</label><select id="material-level" wire:model.live="attachModuleId" class="input"><option value="">Whole course</option>@foreach ($modules as $module)<option value="{{ $module->id }}">Module: {{ $module->title }}</option>@endforeach</select></div>
                @if ($attachModuleId)<div><label for="material-lesson" class="label">Lesson <span class="font-normal text-ink-600">(optional)</span></label><select id="material-lesson" wire:model="attachLessonId" class="input"><option value="">Module level</option>@foreach ($modules->firstWhere('id', (int) $attachModuleId)?->lessons ?? [] as $lesson)<option value="{{ $lesson->id }}">{{ $lesson->title }}</option>@endforeach</select></div>@endif
                <div><label for="material-description" class="label">Display description</label><input id="material-description" wire:model="materialDescription" class="input"></div>
                <div><label for="material-visibility" class="label">Visibility</label><select id="material-visibility" wire:model.live="materialVisibility" class="input"><option value="enrolled">Enrolled learners</option><option value="trainers">Trainers only</option></select><p class="mt-1.5 text-xs text-ink-600">Trainer-only files never appear in the learner materials list and are blocked by the download controller.</p></div>
                @if ($materialVisibility === 'enrolled')
                    <div><label for="material-audience" class="label">Learner audience</label><select id="material-audience" wire:model.live="materialAudience" class="input"><option value="course">Whole course</option><option value="group">A group</option><option value="student">One learner</option></select></div>
                    @if ($materialAudience === 'group')<div><label for="material-group" class="label">Group</label><select id="material-group" wire:model="materialGroupId" class="input"><option value="">Select group…</option>@foreach ($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>@error('materialGroupId')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>@endif
                    @if ($materialAudience === 'student')<div><label for="material-student" class="label">Learner</label><select id="material-student" wire:model="materialStudentId" class="input"><option value="">Select learner…</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select>@error('materialStudentId')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>@endif
                @endif
                <div><label for="material-file" class="label">File</label><input id="material-file" type="file" wire:model="materialUpload" class="block w-full text-sm text-ink-600">@error('materialUpload')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="materialUpload,uploadMaterial">Upload material</button>
            </form>

            <ul class="mt-6 space-y-3 text-sm">
                @forelse ($materials as $material)
                    <li class="rounded-md border border-paper-200 bg-paper-50 p-3" wire:key="material-{{ $material->id }}">
                        <p class="break-words font-semibold text-ink-900">{{ $material->original_name }}</p>
                        <p class="mt-1 text-xs text-ink-600">{{ $material->typeLabel() }} · {{ $material->humanSize() }} · {{ $material->visibility === 'trainers' ? 'Trainers only' : 'Enrolled learners' }}</p>
                        @if ($material->description)<p class="mt-1 text-xs text-ink-600">{{ $material->description }}</p>@endif
                        @if ($renamingId === $material->id)
                            <div class="mt-3 space-y-2"><input wire:model="renameValue" class="input" aria-label="New description"><div class="flex gap-2"><button type="button" wire:click="saveRename" class="btn-primary btn-sm">Save</button><button type="button" wire:click="$set('renamingId', null)" class="btn-secondary btn-sm">Cancel</button></div></div>
                        @elseif ($replacingId === $material->id)
                            <div class="mt-3 space-y-2"><input type="file" wire:model="materialReplace" class="block w-full text-xs" aria-label="Replacement file"><div class="flex gap-2"><button type="button" wire:click="replaceMaterial" class="btn-primary btn-sm">Replace</button><button type="button" wire:click="$set('replacingId', null)" class="btn-secondary btn-sm">Cancel</button></div></div>
                        @else
                            <div class="mt-3 flex flex-wrap gap-2"><a href="{{ route('files.material', $material) }}" class="btn-ghost btn-sm">Download</a><button type="button" wire:click="startRename({{ $material->id }})" class="btn-ghost btn-sm">Rename</button><button type="button" wire:click="$set('replacingId', {{ $material->id }})" class="btn-ghost btn-sm">Replace</button><button type="button" wire:click="deleteMaterial({{ $material->id }})" wire:confirm="Delete this private file? Everyone with access will lose it." class="btn-ghost btn-sm !text-red-700">Delete</button></div>
                        @endif
                    </li>
                @empty
                    <li><x-ui.empty title="No course materials" message="Upload learner resources or trainer-only working files." /></li>
                @endforelse
            </ul>
        </section>
    </div>

    <x-ui.modal open="showModuleModal" close="closeModuleModal" title-id="module-dialog-title" description-id="module-dialog-description" :confirm-close="true">
        <x-slot:header><h2 id="module-dialog-title" class="h2">{{ $editingModuleId ? 'Edit module' : 'Add module' }}</h2><p id="module-dialog-description" class="mt-1 text-sm text-ink-600">Set the section title, publication status, and curriculum order.</p></x-slot:header>
        <form wire:submit="saveModule" class="flex min-h-0 flex-1 flex-col">
            <div class="space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div><label for="module-title" class="label">Title</label><input id="module-title" wire:model="moduleTitle" class="input" required autofocus>@error('moduleTitle')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="module-status" class="label">Status</label><select id="module-status" wire:model="moduleStatus" class="input"><option value="draft">Draft</option><option value="published">Published</option></select>@error('moduleStatus')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div><div><label for="module-order" class="label">Display order</label><input id="module-order" wire:model="moduleSortOrder" type="number" min="0" class="input">@error('moduleSortOrder')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div></div>
                @if ($editingModuleId)<p class="rounded-md border border-paper-300 bg-paper-50 p-3 text-sm text-ink-600">This module currently contains {{ $modules->firstWhere('id', $editingModuleId)?->lessons->count() ?? 0 }} lessons. Editing it keeps those lessons and their learner progress.</p>@endif
            </div>
            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-paper-300 bg-paper-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><button type="button" x-on:click="close()" class="btn-secondary">Cancel</button><button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveModule">{{ $editingModuleId ? 'Save changes' : 'Create module' }}</button></div>
        </form>
    </x-ui.modal>

    <x-ui.modal open="showLessonModal" close="closeLessonModal" title-id="lesson-dialog-title" description-id="lesson-dialog-description" size="xl" :confirm-close="true" :mobile-fullscreen="true">
        <x-slot:header><h2 id="lesson-dialog-title" class="h2">{{ $editingLessonId ? 'Edit lesson' : 'Add lesson' }}</h2><p id="lesson-dialog-description" class="mt-1 text-sm text-ink-600">Choose one lesson type; only its relevant publishing controls are shown.</p></x-slot:header>
        <form wire:submit="saveLesson" class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-4 py-5 sm:px-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label for="lesson-title" class="label">Lesson title</label><input id="lesson-title" wire:model="lessonTitle" class="input" required autofocus>@error('lessonTitle')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="lesson-module" class="label">Module</label><select id="lesson-module" wire:model="lessonModuleId" class="input">@foreach ($modules as $module)<option value="{{ $module->id }}">{{ $module->title }}</option>@endforeach</select>@error('lessonModuleId')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="lesson-status" class="label">Publication status</label><select id="lesson-status" wire:model="lessonStatus" class="input"><option value="draft">Draft</option><option value="published">Published</option></select>@error('lessonStatus')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                </div>

                <fieldset><legend class="label">Lesson type</legend><div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([['text', 'Text', 'Plain written lesson'], ['rich_text', 'Rich text', 'Formatted written lesson'], ['pdf', 'PDF', 'Private document'], ['video', 'Video', 'Upload or hosted link']] as [$value, $label, $help])
                        <label class="cursor-pointer rounded-md border p-3 transition has-[:checked]:border-reg-600 has-[:checked]:bg-reg-50"><input type="radio" wire:model.live="lessonType" value="{{ $value }}" class="sr-only"><span class="block text-sm font-semibold text-ink-900">{{ $label }}</span><span class="mt-0.5 block text-xs text-ink-600">{{ $help }}</span></label>
                    @endforeach
                </div>@error('lessonType')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</fieldset>

                @if (in_array($lessonType, ['text', 'rich_text'], true))
                    <section class="space-y-3" aria-labelledby="lesson-content-heading">
                        <div class="flex items-center justify-between gap-3"><h3 id="lesson-content-heading" class="h3">Lesson content</h3><div class="flex rounded-md border border-paper-300 bg-paper-50 p-0.5"><button type="button" wire:click="$set('showContentPreview', false)" class="rounded px-3 py-1.5 text-xs font-semibold {{ ! $showContentPreview ? 'bg-white text-ink-950 shadow-sm' : 'text-ink-600' }}">Editor</button><button type="button" wire:click="$set('showContentPreview', true)" class="rounded px-3 py-1.5 text-xs font-semibold {{ $showContentPreview ? 'bg-white text-ink-950 shadow-sm' : 'text-ink-600' }}">Formatted preview</button></div></div>
                        @if ($showContentPreview)<article class="prose min-h-56 max-w-none rounded-md border border-paper-300 bg-paper-50 p-5 text-sm text-ink-700">{!! $this->lessonPreview !!}</article>@else<textarea wire:model.live.debounce.400ms="lessonContent" rows="12" class="input" aria-label="Lesson content" placeholder="{{ $lessonType === 'rich_text' ? 'Use safe HTML such as paragraphs, headings, lists, strong, and emphasis.' : 'Write the lesson text.' }}"></textarea>@endif
                        @error('lessonContent')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    </section>
                @elseif ($lessonType === 'pdf')
                    <section class="space-y-4" aria-labelledby="pdf-upload-heading">
                        <div><h3 id="pdf-upload-heading" class="h3">PDF document</h3><p class="mt-1 text-sm text-ink-600">The file stays on private storage and is served only after enrolment authorization.</p></div>
                        @if ($editingLesson?->primaryMaterial)<div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-paper-300 bg-paper-50 p-3"><div><p class="text-sm font-semibold">Current PDF</p><p class="mt-0.5 text-xs text-ink-600">{{ $editingLesson->primaryMaterial->original_name }}</p></div><a href="{{ route('trainer.lesson.preview', [$course, $editingLesson]) }}" target="_blank" class="btn-secondary btn-sm">Instructor preview</a></div>@endif
                        <div x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress" class="rounded-lg border border-paper-300 bg-paper-50 p-5 text-center"><label for="pdf-upload" class="btn-secondary cursor-pointer">Browse PDF<input id="pdf-upload" type="file" wire:model="lessonUpload" accept="application/pdf,.pdf" class="sr-only"></label><p class="mt-2 text-xs text-ink-600">PDF only · up to 25 MB application limit, subject to the server’s {{ $uploadLimitLabel }} request limit.</p><p x-show="uploading" class="mt-2 text-sm text-reg-700" role="status">Uploading… <span x-text="progress"></span>%</p></div>
                        @error('lessonUpload')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <label class="flex items-start gap-3 rounded-md border border-paper-300 p-3"><input type="checkbox" wire:model="requiresAcknowledgement" class="mt-0.5 rounded text-reg-600"><span><span class="block text-sm font-semibold text-ink-900">Require learner acknowledgement</span><span class="mt-0.5 block text-xs text-ink-600">Learners must select “I have read this PDF” before the lesson is complete. Disable this only when opening the PDF should complete it automatically.</span></span></label>
                    </section>
                @elseif ($lessonType === 'video')
                    <section class="space-y-5" aria-labelledby="video-source-heading">
                        <fieldset><legend id="video-source-heading" class="label">Video source</legend><div class="grid gap-2 sm:grid-cols-2"><label class="cursor-pointer rounded-md border p-4 has-[:checked]:border-reg-600 has-[:checked]:bg-reg-50"><input type="radio" wire:model.live="videoSource" value="upload" class="sr-only"><span class="block text-sm font-semibold">Upload MP4/WebM</span><span class="mt-1 block text-xs text-ink-600">Private delivery for enrolled learners.</span></label><label class="cursor-pointer rounded-md border p-4 has-[:checked]:border-reg-600 has-[:checked]:bg-reg-50"><input type="radio" wire:model.live="videoSource" value="hosted" class="sr-only"><span class="block text-sm font-semibold">YouTube/Vimeo link</span><span class="mt-1 block text-xs text-ink-600">Embed an approved hosted video.</span></label></div></fieldset>
                        @if ($videoSource === 'upload')
                            @if ($editingLesson?->video?->provider === 'upload' && $editingLesson->primaryMaterial)<div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-paper-300 bg-paper-50 p-3"><div><p class="text-sm font-semibold">Current private video</p><p class="mt-0.5 text-xs text-ink-600">{{ $editingLesson->primaryMaterial->original_name }}</p></div><a href="{{ route('trainer.lesson.preview', [$course, $editingLesson]) }}" target="_blank" class="btn-secondary btn-sm">Instructor preview</a></div>@endif
                            <div x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress" class="rounded-lg border border-paper-300 bg-paper-50 p-6 text-center"><label for="video-upload" class="btn-primary cursor-pointer">Browse Video<input id="video-upload" type="file" wire:model="lessonUpload" accept="video/mp4,video/webm,.mp4,.webm" class="sr-only"></label><p class="mt-3 text-xs text-ink-600">MP4 or WebM. This server currently reports a {{ $uploadLimitLabel }} PHP request limit; cPanel upload/post size and Livewire temporary storage must all support the file.</p><p x-show="uploading" class="mt-2 text-sm text-reg-700" role="status">Uploading… <span x-text="progress"></span>%</p></div>
                            @error('lessonUpload')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        @else
                            <div><label for="video-id" class="label">YouTube or Vimeo URL / ID</label><input id="video-id" wire:model="videoId" class="input" placeholder="https://www.youtube.com/watch?v=…">@error('videoId')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror@if ($editingLesson?->video && $editingLesson->video->provider !== 'upload')<a href="{{ route('trainer.lesson.preview', [$course, $editingLesson]) }}" target="_blank" class="link mt-2 inline-block text-sm">Open instructor preview</a>@endif</div>
                        @endif
                        <div class="grid gap-4 sm:grid-cols-2"><div><label for="video-duration" class="label">Video duration <span class="font-normal text-ink-600">(seconds)</span></label><input id="video-duration" type="number" wire:model="videoDurationSeconds" min="1" max="86400" class="input">@error('videoDurationSeconds')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div><div><label for="video-threshold" class="label">Completion threshold <span class="font-normal text-ink-600">(%)</span></label><input id="video-threshold" type="number" wire:model="completionThreshold" min="1" max="100" class="input">@error('completionThreshold')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div></div>
                    </section>
                @endif

                <label class="flex items-start gap-3 border-t border-paper-200 pt-5"><input type="checkbox" wire:model="lessonRequired" class="mt-0.5 rounded text-reg-600"><span><span class="block text-sm font-semibold text-ink-900">Required for certification</span><span class="mt-0.5 block text-xs text-ink-600">Optional lessons do not block certificate eligibility.</span></span></label>
            </div>
            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-paper-300 bg-paper-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"><p class="hidden text-xs text-ink-600 sm:block">Changes are not stored until you save.</p><div class="flex flex-col-reverse gap-2 sm:flex-row"><button type="button" x-on:click="close()" class="btn-secondary">Cancel</button><button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveLesson,lessonUpload"><span wire:loading.remove wire:target="saveLesson">Save lesson</span><span wire:loading wire:target="saveLesson">Saving…</span></button></div></div>
        </form>
    </x-ui.modal>
</div>
