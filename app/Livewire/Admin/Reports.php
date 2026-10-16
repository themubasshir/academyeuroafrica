<?php

namespace App\Livewire\Admin;

use App\Models\Certificate;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Reports extends Component
{
    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function render()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $enrolments = Enrolment::whereBetween('enrolled_at', [$from, $to]);
        $paidOrders = Order::where('status', 'paid')->whereBetween('updated_at', [$from, $to]);

        return view('livewire.admin.reports', [
            'students' => User::whereHas('roles', fn ($query) => $query->where('name', 'student'))->count(),
            'enrolments' => (clone $enrolments)->count(),
            'activeEnrolments' => (clone $enrolments)->where('status', 'active')->count(),
            'paidOrders' => (clone $paidOrders)->count(),
            'revenueCents' => (clone $paidOrders)->sum('total_cents'),
            'refundsCents' => Refund::where('status', 'completed')->whereBetween('created_at', [$from, $to])->sum('amount_cents'),
            'certificates' => Certificate::whereBetween('issued_at', [$from, $to])->count(),
            'completionByCourse' => DB::table('courses')
                ->join('course_variants', 'courses.id', '=', 'course_variants.course_id')
                ->join('enrolments', 'course_variants.id', '=', 'enrolments.course_variant_id')
                ->whereBetween('enrolments.enrolled_at', [$from, $to])
                ->select('courses.title', DB::raw('COUNT(enrolments.id) as enrolments'), DB::raw("SUM(CASE WHEN enrolments.status = 'completed' THEN 1 ELSE 0 END) as completed"))
                ->groupBy('courses.id', 'courses.title')
                ->orderByDesc('enrolments')
                ->limit(20)
                ->get(),
        ])->layout('layouts::app', ['title' => 'Reports']);
    }
}
