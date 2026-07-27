{{--
    Navigation inter-modules (sidebar commune).

    Inclusion opt-in depuis la sidebar d'un module :
        @include('core::partials.sidebar-modules', ['moduleCourant' => 'achat'])

    Chaque module déclare sa clé `navigation` dans config/config.php ;
    HasModulePermissions::getModuleNavigation() filtre par permission et par
    existence de route. Le module courant est omis (il a sa propre sidebar).
--}}
@auth
    @php
        $navigationModules = collect(auth()->user()->getModuleNavigation())
            ->except([$moduleCourant ?? null]);
    @endphp

    @if($navigationModules->isNotEmpty())
        <li class="nav-header">AUTRES MODULES</li>

        @foreach($navigationModules as $itemsModule)
            @foreach($itemsModule as $item)
                <li class="nav-item">
                    <a href="{{ route($item['route']) }}" class="nav-link">
                        <i class="nav-icon {{ $item['icon'] ?? 'bi bi-box' }}"></i>
                        <p>{{ $item['label'] }}</p>
                    </a>
                </li>
            @endforeach
        @endforeach
    @endif
@endauth
