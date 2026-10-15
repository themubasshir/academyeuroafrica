@php
    $user = auth()->user();
    $items = match (true) {
        $user->isAdmin() => [
            ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Programmes', '#', 'M4 6h16M4 12h16M4 18h16'],
            ['Courses', '#', 'M12 6v12m6-6H6'],
            ['Users', '#', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 12a4 4 0 100-8 4 4 0 000 8'],
            ['Orders', '#', 'M3 3h18l-1 9H4L3 3z'],
            ['Enrolments', '#', 'M9 12l2 2 4-4'],
            ['Reports', '#', 'M3 3v18h18'],
            ['Settings', '#', 'M12 15a3 3 0 100-6 3 3 0 000 6'],
        ],
        $user->isTrainer() => [
            ['Dashboard', 'trainer.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['My Courses', '#', 'M12 6v12m6-6H6'],
            ['Students', '#', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 12a4 4 0 100-8 4 4 0 000 8'],
            ['Groups', '#', 'M17 8a4 4 0 11-8 0 4 4 0 018 0M3 21v-2a4 4 0 014-4h4'],
            ['Live Sessions', '#', 'M15 10l4.5-4.5M21 3l-4.5 4.5M15 21a9 9 0 110-18'],
            ['Assignments', '#', 'M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2'],
            ['Grading', '#', 'M5 13l4 4L19 7'],
            ['Availability', '#', 'M12 8v4l3 3'],
        ],
        default => [
            ['My Courses', 'student.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Continue Learning', '#', 'M5 3l14 9-14 9V3'],
            ['Live Classes', '#', 'M15 10l4.5-4.5M21 3l-4.5 4.5M15 21a9 9 0 110-18'],
            ['Assignments', '#', 'M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2'],
            ['Quizzes', '#', 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01'],
            ['My Results', '#', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10'],
            ['Private Training', '#', 'M16 7a4 4 0 11-8 0 4 4 0 018 0M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['Certificates', '#', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ],
    };
@endphp

<aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:block" aria-label="Main navigation">
    <nav class="sticky top-16 flex flex-col gap-1 p-4">
        @foreach ($items as [$label, $href, $icon])
            @php $active = $href !== '#' && request()->routeIs($href); @endphp
            <a href="{{ $href === '#' ? '#' : route($href) }}"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-eamn-50 text-eamn-800' => $active,
                   'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>
</aside>
