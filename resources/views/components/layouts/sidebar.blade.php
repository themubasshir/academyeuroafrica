@props(['mobile' => false])
@php
    $user = auth()->user();
    $items = match (true) {
        $user->isAdmin() => [
            ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Programmes', 'admin.programmes', 'M4 6h16M4 12h16M4 18h16'],
            ['Courses', 'admin.courses', 'M12 6v12m6-6H6'],
            ['Users', 'admin.users', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 12a4 4 0 100-8 4 4 0 000 8'],
            ['Enrolments', 'admin.enrolments', 'M9 12l2 2 4-4'],
            ['Orders', 'admin.orders', 'M3 3h18l-1 9H4L3 3z'],
            ['Integrations', 'admin.integrations', 'M13.5 6a3 3 0 11-4.243 4.243M13.5 6L9.257 10.243M13.5 6a3 3 0 104.243 4.243M13.5 6H21'],
            ['Audit logs', 'admin.audit-logs', 'M12 8v4l3 3M5 3v18h14'],
        ],
        $user->isTrainer() => [
            ['Dashboard', 'trainer.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Availability', 'trainer.availability', 'M12 8v4l3 3'],
        ],
        default => [
            ['My Courses', 'student.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['My Results', 'student.results', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10'],
            ['Private Training', 'student.training', 'M16 7a4 4 0 11-8 0 4 4 0 018 0M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ],
    };
    $role = $user->isAdmin() ? 'Administration' : ($user->isTrainer() ? 'Trainer' : 'Student');
@endphp

<aside @class(['w-64 shrink-0 border-r border-gray-200 bg-white', 'hidden lg:block' => ! $mobile]) aria-label="Main navigation">
    <nav class="{{ $mobile ? '' : 'sticky top-16' }} flex flex-col gap-1 p-4">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $role }}</p>
        @foreach ($items as [$label, $href, $icon])
            @php $active = request()->routeIs($href.'*'); @endphp
            <a href="{{ route($href) }}"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
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

        <div class="mt-auto border-t border-gray-100 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </nav>
</aside>
