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
            <ul class="mb-6 flex flex-col gap-4">
                <li>
                    <button
                        type="button"
                        data-admin-navigation-toggle
                        aria-controls="admin-nav-manage"
                        aria-expanded="false"
                        class="menu-item group menu-item-inactive w-full text-left"
                    >
                        <i
                            class="ra ra-castle text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300"
                            aria-hidden="true"
                        ></i>
                        <span class="menu-item-text">Manage</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
                    </button>
                    <ul id="admin-nav-manage" class="menu-dropdown mt-2 hidden flex-col gap-1 pl-9">
                        <li>
                            <a
                                href="{{ route('admin.game-maps.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Game Maps
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.locations.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Locations
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.npcs.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                NPCs
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.monsters.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Monsters
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.items.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Items
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('admin.quests.index') }}"
                                class="menu-dropdown-item menu-dropdown-item-inactive"
                            >
                                Quests
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
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
