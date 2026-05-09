<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MyProject') — MyProject</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 14px; background: #f5f5f5; color: #333; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Nav */
        .navbar { background: #1e293b; color: #fff; display: flex; align-items: center; padding: 0 1.5rem; height: 52px; gap: 2rem; }
        .navbar .brand { font-weight: 700; font-size: 1.1rem; color: #fff; }
        .navbar nav { display: flex; gap: 0.25rem; flex: 1; }
        .navbar nav a { color: #94a3b8; padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 13px; }
        .navbar nav a:hover, .navbar nav a.active { color: #fff; background: #334155; text-decoration: none; }
        .navbar .user { font-size: 13px; color: #94a3b8; display: flex; align-items: center; gap: 1rem; }
        .navbar .user strong { color: #e2e8f0; }

        /* Layout */
        .page { max-width: 1200px; margin: 0 auto; padding: 1.5rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 600; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 1rem; border-radius: 5px; border: 1px solid transparent; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; }
        .btn:hover { text-decoration: none; filter: brightness(0.93); }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #fff; color: #374151; border-color: #d1d5db; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-sm { padding: 0.3rem 0.65rem; font-size: 12px; }

        /* Cards & Tables */
        .card { background: #fff; border-radius: 8px; border: 1px solid #e5e7eb; }
        .card-body { padding: 1.25rem; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; background: #f9fafb; padding: 0.25rem 0.5rem; border-bottom: 1px solid #e5e7eb; }
        td { padding: 0.25rem 0.5rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* Forms */
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 0.3rem; }
        input[type=text], input[type=password], input[type=email], input[type=date], input[type=number], select, textarea {
            width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 5px; font-size: 14px; background: #fff; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px #bfdbfe; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .form-actions { margin-top: 1.5rem; display: flex; gap: 0.5rem; }

        /* Alerts */
        .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 13px; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* Badges */
        .badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 99px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-gray { background: #f3f4f6; color: #374151; }

        /* Breadcrumb */
        .breadcrumb { font-size: 12px; color: #6b7280; margin-bottom: 1rem; display: flex; gap: 0.4rem; align-items: center; }
        .breadcrumb a { color: #6b7280; }
        .breadcrumb span::before { content: '/'; margin-right: 0.4rem; }

        /* Empty state */
        .empty { text-align: center; padding: 3rem 1rem; color: #9ca3af; }
        .empty p { margin-top: 0.5rem; font-size: 13px; }
        .hidden-form { display: none !important; }
        details.cat-card > summary { border-radius:4px 4px 0 0; }
        details.cat-card[open] > summary .cat-caret { transform: rotate(90deg); }
        details.cat-card > summary::-webkit-details-marker { display:none; }

        /* Project switcher */
        .project-switcher { position: relative; }
        .project-btn { background: #334155; border: none; color: #fff; padding: .35rem .75rem; border-radius: 5px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: .35rem; white-space: nowrap; }
        .project-btn:hover { background: #475569; }
        .project-label { color: #94a3b8; font-size: 11px; }
        .project-name { color: #f1f5f9; font-weight: 600; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
        .project-dropdown { display: none; position: absolute; top: calc(100% + 6px); left: 0; background: #1e293b; border: 1px solid #334155; border-radius: 7px; min-width: 280px; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,.4); padding: .25rem; }
        .project-dropdown.open { display: block; }
        .project-dropdown-header { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; padding: .4rem .5rem .2rem; }
        .project-option { display: block; width: 100%; text-align: left; background: none; border: none; color: #cbd5e1; padding: .45rem .5rem; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .project-option:hover { background: #334155; color: #fff; text-decoration: none; }
        .project-option.active { background: #1d4ed8; color: #fff; }
        /* Group switcher */
        .group-switcher { position: relative; }
        .group-btn { background: none; border: none; color: #64748b; padding: 0; cursor: pointer; font-size: 10px; display: flex; align-items: center; gap: .2rem; letter-spacing: .03em; }
        .group-btn:hover { color: #94a3b8; }
        .group-dropdown { display: none; position: absolute; top: calc(100% + 4px); left: 0; background: #1e293b; border: 1px solid #334155; border-radius: 7px; min-width: 220px; z-index: 101; box-shadow: 0 8px 24px rgba(0,0,0,.4); padding: .25rem; }
        .group-dropdown.open { display: block; }
        /* Lang switcher */
        .lang-switcher { display: flex; gap: .25rem; }
        .lang-btn { background: none; border: 1px solid #475569; color: #94a3b8; padding: .15rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 700; cursor: pointer; letter-spacing: .03em; }
        .lang-btn:hover { border-color: #94a3b8; color: #e2e8f0; }
        .lang-btn.active { background: #475569; color: #fff; border-color: #475569; }
        /* Nav dropdown */
        .nav-item { position: relative; }
        .nav-item > a, .nav-item > button { color: #94a3b8; padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 13px; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: .25rem; }
        .nav-item > a:hover, .nav-item > button:hover, .nav-item > a.active, .nav-item > button.active { color: #fff; background: #334155; text-decoration: none; }
        .nav-dd { display: none; position: absolute; top: calc(100% + 4px); left: 0; background: #1e293b; border: 1px solid #334155; border-radius: 7px; min-width: 200px; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,.4); padding: .25rem; }
        .nav-dd.open { display: block; }
        .nav-dd a { display: block; color: #cbd5e1; padding: .4rem .6rem; border-radius: 4px; font-size: 13px; text-decoration: none; white-space: nowrap; }
        .nav-dd a:hover { background: #334155; color: #fff; }
        .nav-dd a.active { background: #1d4ed8; color: #fff; }
        .nav-dd-sep { border-top: 1px solid #334155; margin: .25rem 0; }
    </style>
</head>
<body>

<header class="navbar">
    <div style="display:flex;flex-direction:column;line-height:1.2">
        <a class="brand" href="{{ route('contracts.index') }}">MyProject</a>
        @if($currentGroup)
            @if(auth()->user()->isSuperAdmin())
                <div class="group-switcher" id="groupSwitcher">
                    <button class="group-btn" onclick="document.getElementById('groupDropdown').classList.toggle('open')">
                        {{ $currentGroup->name }} <span style="opacity:.5">▾</span>
                    </button>
                    <div class="group-dropdown" id="groupDropdown">
                        <div class="project-dropdown-header">{{ __('Switch group') }}</div>
                        @foreach(\App\Models\Group::orderBy('name')->get() as $g)
                            <form method="POST" action="{{ route('group.switch', $g) }}">
                                @csrf
                                <button type="submit" class="project-option {{ $currentGroup->id === $g->id ? 'active' : '' }}">
                                    {{ $g->name }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @else
                <span style="font-size:10px;color:#64748b;font-weight:400;letter-spacing:.03em">{{ $currentGroup->name }}</span>
            @endif
        @endif
    </div>

    {{-- Project switcher --}}
    <div class="project-switcher" id="projectSwitcher">
        <button class="project-btn" onclick="document.getElementById('projectDropdown').classList.toggle('open')">
            @if($currentProject)
                <span class="project-label">{{ __('Switch project') }}:</span>
                <span class="project-name">{{ $currentProject->name }}</span>
            @else
                <span class="project-label" style="color:#f59e0b">{{ __('Select project') }}</span>
            @endif
            <span style="margin-left:.3rem;opacity:.6">▾</span>
        </button>
        <div class="project-dropdown" id="projectDropdown">
            <div class="project-dropdown-header">{{ __('Switch project') }}</div>
            @php
                $allProjects = \App\Models\Project::select('id','name','code')
                    ->where('active', true)
                    ->when($currentGroup, fn ($q) => $q->where('id_group', $currentGroup->id))
                    ->when(auth()->user()->level < 5, fn ($q) => $q->whereHas('userRights', fn ($q2) => $q2->where('user_id', auth()->id())))
                    ->orderBy('name')->get();
            @endphp
            @foreach($allProjects as $p)
                <form method="POST" action="{{ route('project.switch', $p) }}">
                    @csrf
                    <button type="submit" class="project-option {{ $currentProject?->id === $p->id ? 'active' : '' }}">
                        <code style="font-size:10px;color:#94a3b8;margin-right:.4rem">{{ $p->code }}</code>
                        {{ $p->name }}
                    </button>
                </form>
            @endforeach
            <div style="border-top:1px solid #334155;margin-top:.25rem;padding-top:.25rem">
                <a href="{{ route('projects.index') }}" class="project-option" style="font-size:12px;color:#94a3b8">
                    {{ __('+ Manage projects') }}
                </a>
            </div>
        </div>
    </div>

    <nav>
        @php
            $contractsActive = request()->routeIs('contracts.*','amendments.*','change-orders.*','change-requests.*');
        @endphp
        <div class="nav-item">
            <button onclick="toggleNavDd('contractsDd')" class="{{ $contractsActive ? 'active' : '' }}">
                {{ __('Contracts') }} <span style="opacity:.5;font-size:10px">▾</span>
            </button>
            <div class="nav-dd" id="contractsDd">
                <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.index') ? 'active' : '' }}">{{ __('Contracts') }}</a>
                <div class="nav-dd-sep"></div>
                <a href="{{ route('change-orders.index') }}" class="{{ request()->routeIs('change-orders.*') ? 'active' : '' }}">{{ __('Change orders') }}</a>
                <a href="{{ route('amendments.index') }}" class="{{ request()->routeIs('amendments.*') ? 'active' : '' }}">{{ __('Amendments') }}</a>
                <a href="{{ route('change-requests.index') }}" class="{{ request()->routeIs('change-requests.*') ? 'active' : '' }}">{{ __('Change requests') }}</a>
                <div class="nav-dd-sep"></div>
                <a href="{{ route('contracts.underbilled') }}" class="{{ request()->routeIs('contracts.underbilled') ? 'active' : '' }}">{{ __('Underbilled') }}</a>
                <a href="{{ route('contracts.overbilled') }}" class="{{ request()->routeIs('contracts.overbilled') ? 'active' : '' }}">{{ __('Overbilled') }}</a>
            </div>
        </div>
        <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}">{{ __('Invoices') }}</a>
        <a href="{{ route('budgets.index') }}" class="{{ request()->routeIs('budgets.*') ? 'active' : '' }}">{{ __('Budgets') }}</a>
        <a href="{{ route('files.index') }}" class="{{ request()->routeIs('files.index') ? 'active' : '' }}">{{ __('Files') }}</a>
        <a href="{{ route('companies.index') }}" class="{{ request()->routeIs('companies.*') ? 'active' : '' }}">{{ __('Companies') }}</a>
        <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">{{ __('Projects') }}</a>
        @if(auth()->user()->isGroupAdmin())
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">{{ __('Users') }}</a>
            <a href="{{ route('activity-log.index') }}" class="{{ request()->routeIs('activity-log.*') ? 'active' : '' }}">{{ __('Activity log') }}</a>
        @endif
        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('groups.index') }}" class="{{ request()->routeIs('groups.*') ? 'active' : '' }}">{{ __('Groups') }}</a>
        @endif
    </nav>

    <div class="user">
        {{-- Language switcher --}}
        <div class="lang-switcher">
            @foreach(['en' => 'EN', 'cs' => 'CS'] as $code => $label)
                <form method="POST" action="{{ route('locale.switch', $code) }}">
                    @csrf
                    <button type="submit" class="lang-btn {{ app()->getLocale() === $code ? 'active' : '' }}">{{ $label }}</button>
                </form>
            @endforeach
        </div>
        <strong>{{ auth()->user()->full_name }}</strong>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">{{ __('Log out') }}</button>
        </form>
    </div>
</header>

<main class="page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.2rem">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script>
    function toggleNavDd(id) {
        const el = document.getElementById(id);
        const wasOpen = el.classList.contains('open');
        document.querySelectorAll('.nav-dd.open').forEach(d => d.classList.remove('open'));
        if (!wasOpen) el.classList.add('open');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item')) {
            document.querySelectorAll('.nav-dd.open').forEach(d => d.classList.remove('open'));
        }
        const projectSwitcher = document.getElementById('projectSwitcher');
        const projectDropdown = document.getElementById('projectDropdown');
        if (projectSwitcher && projectDropdown && !projectSwitcher.contains(e.target)) {
            projectDropdown.classList.remove('open');
        }
        const groupSwitcher = document.getElementById('groupSwitcher');
        const groupDropdown = document.getElementById('groupDropdown');
        if (groupSwitcher && groupDropdown && !groupSwitcher.contains(e.target)) {
            groupDropdown.classList.remove('open');
        }
    });

    function toggleCat(catId) {
        const header = document.querySelector(`tr[data-cat="${catId}"]`);
        if (!header) return;
        const isOpen = header.dataset.open === '1';
        if (isOpen) {
            hideDescendants(catId);
            header.dataset.open = '0';
        } else {
            showChildren(catId);
            header.dataset.open = '1';
        }
        const caret = header.querySelector('.cat-caret');
        if (caret) caret.style.transform = isOpen ? '' : 'rotate(90deg)';
    }

    function hideDescendants(catId) {
        document.querySelectorAll(`tr[data-parent="${catId}"]`).forEach(row => {
            row.style.display = 'none';
            if (row.dataset.cat) hideDescendants(row.dataset.cat);
        });
    }

    function showChildren(catId) {
        document.querySelectorAll(`tr[data-parent="${catId}"]`).forEach(row => {
            row.style.display = '';
            if (row.dataset.cat && row.dataset.open === '1') showChildren(row.dataset.cat);
        });
    }
</script>
</body>
</html>
