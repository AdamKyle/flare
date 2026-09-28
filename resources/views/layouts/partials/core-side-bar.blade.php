<x-sidebar.container>
    <button
        type="button"
        id="admin-sidebar-close"
        aria-label="Close Admin navigation"
        class="focus:ring-danube-500 absolute top-4 right-4 flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:ring-2 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800"
    >
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>

    <x-sidebar.header href="{{ route('home') }}" title="Admin" />

    <nav aria-label="Admin" class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-600 uppercase dark:text-gray-400">
                <span class="menu-group-title">Dashboard</span>
            </h3>
            <ul class="mb-6 flex flex-col gap-4">
                <li>
                    <a href="{{ route('home') }}" class="menu-item group menu-item-inactive">
                        <i
                            class="fas fa-th-large text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-600 uppercase dark:text-gray-400">
                <span class="menu-group-title">Manage</span>
            </h3>
            <div class="mb-6 flex flex-col gap-3">
                <section aria-labelledby="admin-nav-character-heading">
                    <button
                        type="button"
                        id="admin-nav-character-heading"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-character"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="fas fa-user-shield text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Character</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-character" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-4">
                        <li>
                            <a
                                href="{{ route('admin.races.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.races.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.races.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-hood" aria-hidden="true"></i>
                                <span>Races</span>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center gap-1">
                                <a
                                    href="{{ route('admin.classes.index') }}"
                                    class="menu-dropdown-item group min-w-0 flex-1 {{ request()->routeIs('admin.classes.*') || request()->routeIs('admin.class-masteries.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                    @if (request()->routeIs('admin.classes.*')) aria-current="page" @endif
                                >
                                    <i class="ra ra-sword" aria-hidden="true"></i>
                                    <span>Classes</span>
                                </a>
                                <button
                                    type="button"
                                    data-admin-navigation-toggle
                                    aria-label="Toggle Class Masteries"
                                    aria-controls="admin-nav-class-masteries"
                                    aria-expanded="false"
                                    class="focus:ring-danube-500 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:ring-2 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5"
                                >
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ul id="admin-nav-class-masteries" class="menu-dropdown mt-1 hidden flex-col gap-1 pl-5">
                                <li>
                                    <a
                                        href="{{ route('admin.class-masteries.index') }}"
                                        class="menu-dropdown-item group {{ request()->routeIs('admin.class-masteries.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                        @if (request()->routeIs('admin.class-masteries.*')) aria-current="page" @endif
                                    >
                                        <i class="ra ra-perspective-dice-six" aria-hidden="true"></i>
                                        <span>Class Masteries</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.skills.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.skills.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.skills.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-muscle-up" aria-hidden="true"></i>
                                <span>Skills</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.passive-skills.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.passive-skills.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.passive-skills.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-book" aria-hidden="true"></i>
                                <span>Passives</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.gem-abilities.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.gem-abilities.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.gem-abilities.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-gem-pendant" aria-hidden="true"></i>
                                <span>Gem Abilities</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="admin-nav-maps-heading">
                    <button
                        type="button"
                        id="admin-nav-maps-heading"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-maps"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="fas fa-map-marked-alt text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Maps</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-maps" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-4">
                        <li>
                            <div class="flex items-center gap-1">
                                <a
                                    href="{{ route('admin.game-maps.index') }}"
                                    class="menu-dropdown-item group min-w-0 flex-1 {{ request()->routeIs('admin.game-maps.*') || request()->routeIs('admin.map-gems.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                    @if (request()->routeIs('admin.game-maps.*')) aria-current="page" @endif
                                >
                                    <i class="fas fa-map" aria-hidden="true"></i>
                                    <span>Game Maps</span>
                                </a>
                                <button
                                    type="button"
                                    data-admin-navigation-toggle
                                    aria-label="Toggle Map Gems"
                                    aria-controls="admin-nav-map-gems"
                                    aria-expanded="false"
                                    class="focus:ring-danube-500 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:ring-2 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5"
                                >
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ul id="admin-nav-map-gems" class="menu-dropdown mt-1 hidden flex-col gap-1 pl-5">
                                <li>
                                    <a
                                        href="{{ route('admin.map-gems.index') }}"
                                        class="menu-dropdown-item group {{ request()->routeIs('admin.map-gems.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                        @if (request()->routeIs('admin.map-gems.*')) aria-current="page" @endif
                                    >
                                        <i class="ra ra-gem-pendant" aria-hidden="true"></i>
                                        <span>Map Gems</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <div class="flex items-center gap-1">
                                <a
                                    href="{{ route('admin.locations.index') }}"
                                    class="menu-dropdown-item group min-w-0 flex-1 {{ request()->routeIs('admin.locations.*') || request()->routeIs('admin.location-gems.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                    @if (request()->routeIs('admin.locations.*')) aria-current="page" @endif
                                >
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                    <span>Locations</span>
                                </a>
                                <button
                                    type="button"
                                    data-admin-navigation-toggle
                                    aria-label="Toggle Location Gems"
                                    aria-controls="admin-nav-location-gems"
                                    aria-expanded="false"
                                    class="focus:ring-danube-500 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:ring-2 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5"
                                >
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ul id="admin-nav-location-gems" class="menu-dropdown mt-1 hidden flex-col gap-1 pl-5">
                                <li>
                                    <a
                                        href="{{ route('admin.location-gems.index') }}"
                                        class="menu-dropdown-item group {{ request()->routeIs('admin.location-gems.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                        @if (request()->routeIs('admin.location-gems.*')) aria-current="page" @endif
                                    >
                                        <i class="ra ra-gem-pendant" aria-hidden="true"></i>
                                        <span>Location Gems</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.npcs.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.npcs.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.npcs.*')) aria-current="page" @endif
                            >
                                <i class="fas fa-user-friends" aria-hidden="true"></i>
                                <span>NPCs</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="admin-nav-craftable-heading">
                    <button
                        type="button"
                        id="admin-nav-craftable-heading"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-craftable"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="fas fa-hammer text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Craftable</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-craftable" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-4">
                        <li>
                            <a
                                href="{{ route('admin.items.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.items.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.items.*')) aria-current="page" @endif
                            >
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <span>Items</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="admin-nav-kingdoms-heading">
                    <button
                        type="button"
                        id="admin-nav-kingdoms-heading"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-kingdoms"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="ra ra-tower text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Kingdoms</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-kingdoms" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-4">
                        <li>
                            <a
                                href="{{ route('admin.kingdoms.units.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.kingdoms.units.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.kingdoms.units.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-crossed-swords" aria-hidden="true"></i>
                                <span>Units</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.kingdoms.buildings.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.kingdoms.buildings.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.kingdoms.buildings.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-tower" aria-hidden="true"></i>
                                <span>Buildings</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="admin-nav-misc-heading">
                    <button
                        type="button"
                        id="admin-nav-misc-heading"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-misc"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="fas fa-ellipsis-h text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Misc</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-misc" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-4">
                        <li>
                            <a
                                href="{{ route('admin.quests.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.quests.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.quests.*')) aria-current="page" @endif
                            >
                                <i class="fas fa-scroll" aria-hidden="true"></i>
                                <span>Quests</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monsters.index') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.monsters.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.monsters.*')) aria-current="page" @endif
                            >
                                <i class="ra ra-dragon" aria-hidden="true"></i>
                                <span>Monsters</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.guide-quests') }}"
                                class="menu-dropdown-item group {{ request()->routeIs('admin.guide-quests.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                @if (request()->routeIs('admin.guide-quests.*')) aria-current="page" @endif
                            >
                                <i class="fas fa-map-signs" aria-hidden="true"></i>
                                <span>Guide Quests</span>
                            </a>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-600 uppercase dark:text-gray-400">
                <span class="menu-group-title">Monitoring</span>
            </h3>
            <ul class="mb-6 flex flex-col gap-4">
                <li>
                    <button
                        type="button"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-monitoring"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="fas fa-chart-line text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Monitoring</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-monitoring" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-9">
                        <li>
                            <a
                                href="{{ route('admin.character-reward-queue') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Reward Queues
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monitoring.exploration') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Exploration
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monitoring.faction-loyalty') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Faction Loyalty
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monitoring.delve') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Delve
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monitoring.batch-crafting') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Batch Crafting
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monitoring.logs') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Logs
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</x-sidebar.container>

<div id="admin-sidebar-backdrop" class="fixed inset-0 z-[999998] hidden bg-gray-900/50" aria-hidden="true"></div>
