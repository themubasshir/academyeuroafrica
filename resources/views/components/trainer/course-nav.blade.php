@props(['course', 'active' => ''])
@php
    $tabs = [
        'manage'      => ['Content',     'trainer.course.manage'],
        'sessions'    => ['Sessions',    'trainer.sessions'],
        'groups'      => ['Groups',      'trainer.groups'],
        'assignments' => ['Assignments', 'trainer.assignments'],
        'quizzes'     => ['Quizzes',     'trainer.quizzes'],
        'students'    => ['Students',    'trainer.students'],
    ];
@endphp
<nav class="flex flex-wrap items-center gap-1 border-b border-paper-300" aria-label="Manage course">
    @foreach ($tabs as $key => [$label, $route])
        @php $on = $active === $key; @endphp
        <a href="{{ route($route, $course) }}"
           @class([
               'relative -mb-px border-b-2 px-3 py-2.5 text-sm transition',
               'border-seal-500 font-semibold text-ink-950' => $on,
               'border-transparent font-medium text-ink-500 hover:border-paper-300 hover:text-ink-900' => ! $on,
           ])
           @if ($on) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
