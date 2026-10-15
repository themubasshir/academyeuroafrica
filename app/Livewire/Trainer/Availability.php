<?php

namespace App\Livewire\Trainer;

use App\Models\TrainerAvailability;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Availability extends Component
{
    public string $date = '';

    public string $startTime = '';

    public string $endTime = '';

    public function add(): void
    {
        $this->validate([
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
        ]);

        $tz = auth()->user()->timezone ?? 'UTC';
        TrainerAvailability::create([
            'trainer_id' => auth()->id(),
            'starts_at' => Carbon::parse("{$this->date} {$this->startTime}", $tz)->utc(),
            'ends_at' => Carbon::parse("{$this->date} {$this->endTime}", $tz)->utc(),
            'timezone' => $tz,
        ]);

        $this->reset(['date', 'startTime', 'endTime']);
        session()->flash('saved', 'Availability added.');
    }

    public function remove(int $id): void
    {
        TrainerAvailability::where('id', $id)->where('trainer_id', auth()->id())->delete();
    }

    public function render()
    {
        return view('livewire.trainer.availability', [
            'slots' => TrainerAvailability::where('trainer_id', auth()->id())
                ->where('ends_at', '>', now())->orderBy('starts_at')->get(),
        ])->layout('layouts::app', ['title' => 'My availability']);
    }
}
