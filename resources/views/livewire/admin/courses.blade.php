<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Courses">
        <button type="button" wire:click="createCourse" class="btn-primary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
            Add Course
        </button>
    </x-ui.page-head>

    <p class="max-w-3xl text-sm text-ink-600">Manage catalogue details here, then use Curriculum and Pricing for the parts of each course that need focused authoring.</p>

    <div class="space-y-4" aria-label="Existing courses">
        @forelse ($courses as $course)
            @php
                $checkoutUrl = route('checkout.course', ['course' => $course->slug]);
                $variant = $course->checkoutVariant;
                $requirements = [
                    'Course published' => $course->status === 'published',
                    'Package selected' => $variant !== null,
                    'Package approved' => $variant?->approval_status === 'approved',
                    'Package published' => $variant?->status === 'published',
                    'Paid package with valid price' => $variant !== null && ($variant->availability ?? 'paid') === 'paid' && $variant->price_cents !== null,
                ];
                $checkoutReady = ! in_array(false, $requirements, true);
                $prices = $course->variants->filter(fn ($item) => $item->price_cents !== null)->map(fn ($item) => $item->priceFormatted())->unique();
            @endphp
            <article class="card overflow-hidden" wire:key="course-{{ $course->id }}">
                <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-ink-950">{{ $course->title }}</h2>
                            <span @class(['badge-green' => $course->status === 'published', 'badge-gray' => $course->status === 'draft', 'badge-amber' => $course->status === 'archived'])>{{ ucfirst($course->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-ink-600">{{ $course->programme->name }}</p>
                        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                            <div><dt class="folio">Curriculum</dt><dd class="mt-1 text-sm font-medium">{{ $course->modules_count }} {{ Str::plural('module', $course->modules_count) }} · {{ $course->lessons_count }} {{ Str::plural('lesson', $course->lessons_count) }}</dd></div>
                            <div><dt class="folio">Packages</dt><dd class="mt-1 text-sm font-medium">{{ $course->variants->count() }} total</dd></div>
                            <div class="col-span-2 sm:col-span-1"><dt class="folio">Pricing</dt><dd class="mt-1 text-sm font-medium">{{ $prices->isEmpty() ? 'No valid prices' : $prices->take(3)->join(' · ') }}</dd></div>
                        </dl>
                    </div>
                    <div class="flex flex-wrap content-start gap-2 lg:max-w-sm lg:justify-end">
                        <a href="{{ route('trainer.course.manage', $course) }}" class="btn-secondary btn-sm">Manage Curriculum</a>
                        <a href="{{ route('admin.course.pricing', $course) }}" class="btn-secondary btn-sm">Manage Pricing</a>
                        <button type="button" wire:click="editCourse({{ $course->id }})" class="btn-secondary btn-sm">Edit</button>
                        @if ($course->status !== 'archived')
                            <button type="button" wire:click="archive({{ $course->id }})" wire:confirm="Archive this course? Existing learner progress, pricing, and payment records will be retained." class="btn-ghost btn-sm !text-red-700">Archive</button>
                        @endif
                    </div>
                </div>

                <div class="border-t border-paper-200 bg-paper-50 px-5 py-4">
                    <div class="grid gap-4 xl:grid-cols-[minmax(15rem,22rem)_minmax(0,1fr)] xl:items-end">
                        <div>
                            <label for="checkout-package-{{ $course->id }}" class="label">Checkout package</label>
                            <select id="checkout-package-{{ $course->id }}" wire:change="assignCheckoutPackage({{ $course->id }}, $event.target.value)" class="input">
                                <option value="">No checkout package</option>
                                @foreach ($course->variants as $package)
                                    @php
                                        $packageSelectable = ($package->availability ?? 'paid') === 'paid'
                                            && $package->status === 'published'
                                            && $package->approval_status === 'approved'
                                            && $package->price_cents !== null;
                                    @endphp
                                    <option value="{{ $package->id }}" @selected($course->checkout_variant_id === $package->id) @disabled(! $packageSelectable)>
                                        {{ $package->name }} · {{ $package->priceFormatted() }}{{ $packageSelectable ? '' : ' · not ready' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div x-data="{ copied: false, failed: false, url: @js($checkoutUrl) }">
                            <label for="checkout-url-{{ $course->id }}" class="label">Permanent checkout URL</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input x-ref="url" id="checkout-url-{{ $course->id }}" type="url" readonly value="{{ $checkoutUrl }}" class="input min-w-0 flex-1 bg-white font-sans text-xs" x-on:focus="$el.select()">
                                <button type="button" class="btn-primary shrink-0" x-on:click="navigator.clipboard.writeText(url).then(() => { copied = true; failed = false; setTimeout(() => copied = false, 2200) }).catch(() => { failed = true; $refs.url.select() })">
                                    <span x-text="copied ? 'Copied' : 'Copy Checkout Link'">Copy Checkout Link</span>
                                </button>
                            </div>
                            <p class="sr-only" aria-live="polite" x-text="copied ? 'Checkout link copied to clipboard.' : (failed ? 'Copy failed. Select and copy the URL manually.' : '')"></p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-start justify-between gap-3 border-t border-paper-200 pt-4">
                        <div>
                            <p class="text-sm font-semibold {{ $checkoutReady ? 'text-emerald-800' : 'text-amber-800' }}">{{ $checkoutReady ? 'Checkout ready' : 'Checkout not ready' }}</p>
                            <p class="mt-0.5 text-xs text-ink-600">The permanent URL can be shared now; server-side checkout remains blocked until every requirement passes.</p>
                        </div>
                        <ul class="flex flex-wrap gap-x-4 gap-y-2" aria-label="Checkout requirements">
                            @foreach ($requirements as $label => $met)
                                <li class="flex items-center gap-1.5 text-xs font-medium {{ $met ? 'text-emerald-800' : 'text-amber-800' }}">
                                    @if ($met)
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4 10 4 4 8-9" /></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="7" /></svg>
                                    @endif
                                    {{ $label }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </article>
        @empty
            <x-ui.empty title="No courses yet" message="Add a course, then build its curriculum and pricing." />
        @endforelse
    </div>
    {{ $courses->links() }}

    <x-ui.modal open="showCourseModal" close="closeCourseModal" title-id="course-dialog-title" description-id="course-dialog-description" size="lg" :confirm-close="true">
        <x-slot:header>
            <h2 id="course-dialog-title" class="h2">{{ $editingId ? 'Edit course' : 'Add course' }}</h2>
            <p id="course-dialog-description" class="mt-1 text-sm text-ink-600">Curriculum, pricing, checkout mapping, and learner progress are managed separately and will not be changed here.</p>
        </x-slot:header>

        <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
            <div class="space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="course-title" class="label">Course title</label>
                    <input id="course-title" wire:model="title" class="input" required autofocus>
                    @error('title')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="course-programme" class="label">Programme</label><select id="course-programme" wire:model="programmeId" class="input" required><option value="">Choose programme</option>@foreach ($programmes as $programme)<option value="{{ $programme->id }}">{{ $programme->name }}</option>@endforeach</select>@error('programmeId')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="course-status" class="label">Publication status</label><select id="course-status" wire:model="status" class="input"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select>@error('status')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="course-delivery" class="label">Delivery types</label><input id="course-delivery" wire:model="deliveryTypes" class="input" placeholder="live, prerecorded">@error('deliveryTypes')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="course-access" class="label">Access days</label><input id="course-access" wire:model="accessDays" type="number" min="1" class="input" placeholder="No course-level limit">@error('accessDays')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="course-trainer" class="label">Primary trainer</label><select id="course-trainer" wire:model="trainerId" class="input"><option value="">No assignment</option>@foreach ($trainers as $trainer)<option value="{{ $trainer->id }}">{{ $trainer->name }}</option>@endforeach</select>@error('trainerId')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="course-order" class="label">Display order</label><input id="course-order" wire:model="sortOrder" type="number" min="0" class="input">@error('sortOrder')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                </div>
                <div><label for="course-description" class="label">Description</label><textarea id="course-description" wire:model="description" rows="4" class="input"></textarea>@error('description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="course-outcomes" class="label">Learning objectives <span class="font-normal text-ink-600">(one per line)</span></label><textarea id="course-outcomes" wire:model="learningOutcomes" rows="5" class="input"></textarea>@error('learningOutcomes')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            </div>
            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-paper-300 bg-paper-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button" x-on:click="close()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Create course' }}</span><span wire:loading wire:target="save">Saving…</span></button>
            </div>
        </form>
    </x-ui.modal>
</div>
