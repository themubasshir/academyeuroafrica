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

<aside @class(['flex w-64 shrink-0 flex-col bg-navy-900', 'hidden lg:flex' => ! $mobile]) aria-label="Main navigation">
    <div class="{{ $mobile ? '' : 'sticky top-0' }} flex min-h-0 flex-1 flex-col">
        @unless ($mobile)
            <div class="flex h-16 items-center border-b border-navy-800 px-5">
                <a href="{{ route('home') }}" aria-label="EAMN Academy home"><x-ui.brand dark size="sm" /></a>
            </div>
        @endunless

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <p class="register px-3 pb-3 !text-navy-100">{{ $role }} register</p>
            <ul class="space-y-px">
                @foreach ($items as [$label, $href, $icon])
                    @php $active = request()->routeIs($href.'*'); @endphp
                    <li>
                        <a href="{{ route($href) }}"
                           @class([
                               'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition',
                               'bg-navy-800 font-semibold text-white shadow-[inset_2px_0_0_0_var(--color-seal-400)]' => $active,
                               'font-medium text-navy-100 hover:bg-navy-850 hover:text-white' => ! $active,
                           ])
                           @if ($active) aria-current="page" @endif>
                            <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-seal-400' : 'text-navy-100/80 group-hover:text-navy-100' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-navy-800 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-navy-100/75 transition hover:bg-navy-850 hover:text-white lg:hidden">
                    <svg class="h-5 w-5 text-navy-100/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </div>
</aside>
