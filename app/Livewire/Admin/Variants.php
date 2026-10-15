<?php

namespace App\Livewire\Admin;

use App\Models\CourseVariant;
use Livewire\Component;

/**
 * Review + approve the imported commercial variants.
 *
 * Approval and publication are two separate gates: a variant only becomes
 * purchasable when approval_status=approved AND status=published (enforced in
 * CourseVariant::isPurchasable and re-checked in OrderService::create).
 */
class Variants extends Component
{
    /** Inline-edit buffer keyed by variant id. */
    public array $draft = [];

    public function edit(int $id): void
    {
        $v = CourseVariant::findOrFail($id);
        $this->draft[$id] = [
            'name' => $v->name,
            'price' => $v->price_cents !== null ? number_format($v->price_cents / 100, 2, '.', '') : '',
            'duration' => $v->duration ?? '',
        ];
    }

    public function save(int $id): void
    {
        $data = $this->validate([
            "draft.{$id}.name" => 'required|string|max:255',
            "draft.{$id}.price" => 'nullable|numeric|min:0',
            "draft.{$id}.duration" => 'nullable|string|max:120',
        ])['draft'][$id];

        CourseVariant::findOrFail($id)->update([
            'name' => $data['name'],
            'price_cents' => $data['price'] === '' ? null : (int) round($data['price'] * 100),
            'duration' => $data['duration'] ?: null,
        ]);
        unset($this->draft[$id]);
    }

    /** Approve = the commercial content has been reviewed. Still not public. */
    public function approve(int $id): void
    {
        $v = CourseVariant::findOrFail($id);
        abort_if($v->price_cents === null, 422, 'Set a price before approving.');
        $v->update(['approval_status' => 'approved']);
    }

    public function unapprove(int $id): void
    {
        CourseVariant::findOrFail($id)->update([
            'approval_status' => 'pending',
            'status' => 'draft', // unpublish on de-approval — never leave a live unreviewed price
        ]);
    }

    public function togglePublish(int $id): void
    {
        $v = CourseVariant::findOrFail($id);
        if ($v->status !== 'published') {
            abort_unless($v->approval_status === 'approved' && $v->price_cents !== null, 422,
                'Approve with a price before publishing.');
            $v->update(['status' => 'published']);
        } else {
            $v->update(['status' => 'draft']);
        }
    }

    public function render()
    {
        return view('livewire.admin.variants', [
            'variants' => CourseVariant::with('course.programme')
                ->orderBy('course_id')->orderBy('price_cents')->get(),
        ])->layout('layouts::app', ['title' => 'Course Packages']);
    }
}
