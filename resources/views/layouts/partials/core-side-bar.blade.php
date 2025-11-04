<x-sidebar.container>
  <!-- SIDEBAR HEADER -->
  <x-sidebar.header href="#" title="Game Options" />
  <!-- SIDEBAR HEADER -->

  <div
    class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear"
  >
    <nav x-data="{ selected: $persist('Dashboard') }">
      <x-sidebar.menu-items.group-options-container headerTitle="Game Data Management">
        <x-sidebar.menu-items.nested-container
          name="Locations"
          key="Locations"
          activePages="['import','export','locations']"
          selected="Locations"
          icon="ra ra-wooden-sign"
        >
          <x-sidebar.menu-items.nested-menu-option
            href="{{route('locations.list')}}"
            pageKey="locations"
            icon="ra ra-wooden-sign"
          >
            Locations
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="import"
            icon="fas fa-file-import"
          >
            Import Locations
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="export"
            icon="fas fa-file-download"
          >
            Export Locations
          </x-sidebar.menu-items.nested-menu-option>
        </x-sidebar.menu-items.nested-container>
        <x-sidebar.menu-items.nested-container
          name="Items"
          key="Items"
          activePages="['import','export','items']"
          selected="Items"
          icon="ra ra-crossed-swords"
        >
          <x-sidebar.menu-items.nested-menu-option
            href="{{route('items.list')}}"
            pageKey="items"
            icon="ra ra-crossed-swords"
          >
            Items
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="import"
            icon="fas fa-file-import"
          >
            Import Locations
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="export"
            icon="fas fa-file-download"
          >
            Export Locations
          </x-sidebar.menu-items.nested-menu-option>
        </x-sidebar.menu-items.nested-container>
        <x-sidebar.menu-items.nested-container
          name="Monsters"
          key="Monsters"
          activePages="['import','export','items']"
          selected="Monsters"
          icon="ra ra-dragon"
        >
          <x-sidebar.menu-items.nested-menu-option
            href="{{route('monsters.list')}}"
            pageKey="monsters"
            icon="ra ra-dragon"
          >
            Monsters
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="import"
            icon="fas fa-file-import"
          >
            Import Locations
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="export"
            icon="fas fa-file-download"
          >
            Export Locations
          </x-sidebar.menu-items.nested-menu-option>
        </x-sidebar.menu-items.nested-container>
      </x-sidebar.menu-items.group-options-container>

      <x-sidebar.menu-items.group-options-container headerTitle="Guide Quests">

        <x-sidebar.menu-items.nested-container
          name="Guide Quests"
          key="guide-quests"
          activePages="['import','export','guide-quests']"
          selected="Guide Quests"
          icon="ra ra-wooden-sign"
        >
          <x-sidebar.menu-items.nested-menu-option
            href="{{route('admin.guide-quests')}}"
            pageKey="guide-quests"
            icon="ra ra-wooden-sign"
          >
            Manage Guide Quests
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="import"
            icon="fas fa-file-import"
          >
            Import Locations
          </x-sidebar.menu-items.nested-menu-option>
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="export"
            icon="fas fa-file-download"
          >
            Export Locations
          </x-sidebar.menu-items.nested-menu-option>
        </x-sidebar.menu-items.nested-container>

      </x-sidebar.menu-items.group-options-container>

      <!-- MENU GROUP -->
      <div>
        <h3 class="mb-6 text-xs leading-[20px] text-gray-400 uppercase">
          <span class="menu-group-title">MENU</span>
        </h3>
        <ul class="mb-6 flex flex-col gap-4">
          <!-- Dashboard -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Dashboard' ? '' : 'Dashboard'"
              class="menu-item group"
              :class="(selected === 'Dashboard' || ['ecommerce','analytics','marketing','crm','stocks'].includes(page)) ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="(selected === 'Dashboard' || ['ecommerce','analytics','marketing','crm','stocks'].includes(page))
                          ? 'fas fa-th-large text-danube-500 dark:text-danube-400'
                          : 'fas fa-th-large text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">Dashboard</span>
              <i
                :class="selected === 'Dashboard'
                          ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                          : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected === 'Dashboard' ? 'block translate transform overflow-hidden' : 'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="index.html"
                    class="menu-dropdown-item group"
                    :class="page === 'ecommerce' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    eCommerce
                  </a>
                </li>
                <li>
                  <a
                    href="analytics.html"
                    class="menu-dropdown-item group"
                    :class="page === 'analytics' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Analytics
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='analytics'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="marketing.html"
                    class="menu-dropdown-item group"
                    :class="page === 'marketing' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Marketing
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='marketing'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="crm.html"
                    class="menu-dropdown-item group"
                    :class="page === 'crm' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    CRM
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='crm'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="stocks.html"
                    class="menu-dropdown-item group"
                    :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Stocks
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='stocks'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      New
                    </span>
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='stocks'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Calendar -->
          <li>
            <a
              href="calendar.html"
              @click.prevent="selected = selected === 'Calendar' ? '' : 'Calendar'"
              class="menu-item group"
              :class="selected === 'Calendar' && page==='calendar' ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="selected === 'Calendar' && page==='calendar'
                          ? 'fas fa-calendar text-danube-500 dark:text-danube-400'
                          : 'fas fa-calendar text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">Calendar</span>
            </a>
          </li>

          <!-- User Profile -->
          <li>
            <a
              href="profile.html"
              @click.prevent="selected = selected === 'Profile' ? '' : 'Profile'"
              class="menu-item group"
              :class="selected === 'Profile' && page==='profile' ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="selected === 'Profile' && page==='profile'
                          ? 'fas fa-user text-danube-500 dark:text-danube-400'
                          : 'fas fa-user text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">User Profile</span>
            </a>
          </li>

          <!-- Task -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Task' ? '' : 'Task'"
              class="menu-item group"
              :class="(selected === 'Task' || ['taskList','taskKanban'].includes(page))
                        ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="(selected === 'Task' || ['taskList','taskKanban'].includes(page))
                          ? 'fas fa-tasks text-danube-500 dark:text-danube-400'
                          : 'fas fa-tasks text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">Task</span>
              <i
                :class="selected === 'Task'
                          ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                          : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected === 'Task' ? 'block translate transform overflow-hidden' : 'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="task-list.html"
                    class="menu-dropdown-item group"
                    :class="page === 'taskList' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    List
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='taskList'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="task-kanban.html"
                    class="menu-dropdown-item group"
                    :class="page === 'taskKanban' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Kanban
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='taskKanban'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Forms -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Forms' ? '' : 'Forms'"
              class="menu-item group"
              :class="(selected === 'Forms' || ['formElements','formLayout'].includes(page))
                        ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="(selected === 'Forms' || ['formElements','formLayout'].includes(page))
                          ? 'fas fa-edit text-danube-500 dark:text-danube-400'
                          : 'fas fa-edit text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">Forms</span>
              <i
                :class="selected === 'Forms'
                          ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                          : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected === 'Forms' ? 'block translate transform overflow-hidden' : 'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="form-elements.html"
                    class="menu-dropdown-item group"
                    :class="page === 'formElements' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Form Elements
                  </a>
                </li>
                <li>
                  <a
                    href="form-layout.html"
                    class="menu-dropdown-item group"
                    :class="page === 'formLayout' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Form Layout
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Tables -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Tables' ? '' : 'Tables'"
              class="menu-item group"
              :class="(selected === 'Tables' || ['basicTables','dataTables'].includes(page))
                        ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="(selected === 'Tables' || ['basicTables','dataTables'].includes(page))
                          ? 'fas fa-table text-danube-500 dark:text-danube-400'
                          : 'fas fa-table text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
              <span class="menu-item-text">Tables</span>
              <i
                :class="selected === 'Tables'
                          ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                          : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected === 'Tables' ? 'block translate transform overflow-hidden' : 'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="basic-tables.html"
                    class="menu-dropdown-item group"
                    :class="page === 'basicTables' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Basic Tables
                  </a>
                </li>
                <li>
                  <a
                    href="data-tables.html"
                    class="menu-dropdown-item group"
                    :class="page === 'dataTables' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'"
                  >
                    Data Tables
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='dataTables'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Pages -->
          <x-sidebar.menu-items.nested-container
            name="Pages"
            key="Pages"
            activePages="['fileManager','pricingTables','blank','page404','page500','page503','success','faq','comingSoon','termsCondition','maintenance']"
            selected="Pages"
            icon="fas fa-file"
          >
            <x-sidebar.menu-items.nested-menu-option
              href="#"
              pageKey="fileManager"
            >
              File Manager
            </x-sidebar.menu-items.nested-menu-option>
            <li>
              <a
                href="pricing-tables.html"
                class="menu-dropdown-item group"
                :class="page==='pricingTables'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Pricing Tables
                <span
                  class="menu-dropdown-badge"
                  :class="page==='pricingTables'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="faq.html"
                class="menu-dropdown-item group"
                :class="page==='faq'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Faq's
                <span
                  class="menu-dropdown-badge"
                  :class="page==='faq'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="blank.html"
                class="menu-dropdown-item group"
                :class="page==='blank'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Blank Page
                <span
                  class="menu-dropdown-badge"
                  :class="page==='blank'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="404.html"
                class="menu-dropdown-item group"
                :class="page==='page404'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                404 Error
                <span
                  class="menu-dropdown-badge"
                  :class="page==='page404'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="500.html"
                class="menu-dropdown-item group"
                :class="page==='page500'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                500 Error
                <span
                  class="menu-dropdown-badge"
                  :class="page==='page500'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="503.html"
                class="menu-dropdown-item group"
                :class="page==='page503'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                503 Error
                <span
                  class="menu-dropdown-badge"
                  :class="page==='page503'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="coming-soon.html"
                class="menu-dropdown-item group"
                :class="page==='comingSoon'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Coming Soon
                <span
                  class="menu-dropdown-badge"
                  :class="page==='comingSoon'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="maintenance.html"
                class="menu-dropdown-item group"
                :class="page==='termsCondition'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Maintenance
                <span
                  class="menu-dropdown-badge"
                  :class="page==='termsCondition'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
            <li>
              <a
                href="success.html"
                class="menu-dropdown-item group"
                :class="page==='success'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
              >
                Success
                <span
                  class="menu-dropdown-badge"
                  :class="page==='success'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                >
                  Pro
                </span>
              </a>
            </li>
          </x-sidebar.menu-items.nested-container>
        </ul>
      </div>

      <x-sidebar.menu-items.group-options-container headerTitle="Support">
        <x-sidebar.menu-items.group-option
          href="#"
          itemKey="Chat"
          pageKey="chat"
          icon="fas fa-file"
        >
          Chat
        </x-sidebar.menu-items.group-option>

        <x-sidebar.menu-items.nested-container
          name="Pages"
          key="Pages"
          activePages="['fileManager','pricingTables','blank','page404','page500','page503','success','faq','comingSoon','termsCondition','maintenance']"
          selected="Pages"
          icon="fas fa-file"
        >
          <x-sidebar.menu-items.nested-menu-option
            href="#"
            pageKey="fileManager"
          >
            File Manager
          </x-sidebar.menu-items.nested-menu-option>
          <li>
            <a
              href="pricing-tables.html"
              class="menu-dropdown-item group"
              :class="page==='pricingTables'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Pricing Tables
              <span
                class="menu-dropdown-badge"
                :class="page==='pricingTables'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="faq.html"
              class="menu-dropdown-item group"
              :class="page==='faq'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Faq's
              <span
                class="menu-dropdown-badge"
                :class="page==='faq'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="blank.html"
              class="menu-dropdown-item group"
              :class="page==='blank'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Blank Page
              <span
                class="menu-dropdown-badge"
                :class="page==='blank'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="404.html"
              class="menu-dropdown-item group"
              :class="page==='page404'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              404 Error
              <span
                class="menu-dropdown-badge"
                :class="page==='page404'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="500.html"
              class="menu-dropdown-item group"
              :class="page==='page500'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              500 Error
              <span
                class="menu-dropdown-badge"
                :class="page==='page500'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="503.html"
              class="menu-dropdown-item group"
              :class="page==='page503'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              503 Error
              <span
                class="menu-dropdown-badge"
                :class="page==='page503'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="coming-soon.html"
              class="menu-dropdown-item group"
              :class="page==='comingSoon'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Coming Soon
              <span
                class="menu-dropdown-badge"
                :class="page==='comingSoon'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="maintenance.html"
              class="menu-dropdown-item group"
              :class="page==='termsCondition'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Maintenance
              <span
                class="menu-dropdown-badge"
                :class="page==='termsCondition'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
          <li>
            <a
              href="success.html"
              class="menu-dropdown-item group"
              :class="page==='success'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
            >
              Success
              <span
                class="menu-dropdown-badge"
                :class="page==='success'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
              >
                Pro
              </span>
            </a>
          </li>
        </x-sidebar.menu-items.nested-container>
      </x-sidebar.menu-items.group-options-container>

      <!-- SUPPORT GROUP -->
      <div>
        <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
          <span class="menu-group-title">Support</span>
        </h3>
        <ul class="mb-6 flex flex-col gap-4">
          <!-- Chat -->
          <li>
            <a
              href="chat.html"
              @click.prevent="selected = selected === 'Chat' ? '' : 'Chat'"
              class="menu-item group"
              :class="selected === 'Chat' && page==='chat' ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <i
                :class="selected==='Chat'&&page==='chat'
                            ? 'fas fa-comments text-danube-500 dark:text-danube-400'
                            : 'fas fa-comments text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Chat</span>
            </a>
          </li>
          <!-- Email -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Email' ? '' : 'Email'"
              class="menu-item group"
              :class="(selected==='Email' || ['inbox','inboxDetails'].includes(page)) ? 'menu-item-active':'menu-item-inactive'"
            >
              <i
                :class="(selected==='Email' || ['inbox','inboxDetails'].includes(page))
                            ? 'fas fa-envelope text-danube-500 dark:text-danube-400'
                            : 'fas fa-envelope text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Email</span>
              <i
                :class="selected==='Email'
                            ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                            : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected==='Email' ? 'block translate transform overflow-hidden' : 'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="inbox.html"
                    class="menu-dropdown-item group"
                    :class="page==='inbox'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Inbox
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='inbox'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="inbox-details.html"
                    class="menu-dropdown-item group"
                    :class="page==='inboxDetails'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Details
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='inboxDetails'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <!-- Invoice -->
          <li>
            <a
              href="invoice.html"
              @click.prevent="selected = selected === 'Invoice' ? '' : 'Invoice'"
              class="menu-item group"
              :class="selected==='Invoice'&&page==='invoice'?'menu-item-active':'menu-item-inactive'"
            >
              <i
                :class="selected==='Invoice'&&page==='invoice'
                            ? 'fas fa-file-alt text-danube-500 dark:text-danube-400'
                            : 'fas fa-file-alt text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Invoice</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- OTHERS GROUP -->
      <div>
        <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
          <span class="menu-group-title">others</span>
        </h3>
        <ul class="mb-6 flex flex-col gap-4">
          <!-- Charts -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Charts' ? '' : 'Charts'"
              class="menu-item group"
              :class="(selected==='Charts' || ['lineChart','barChart','pieChart'].includes(page))?'menu-item-active':'menu-item-inactive'"
            >
              <i
                :class="(selected==='Charts'||['lineChart','barChart','pieChart'].includes(page))
                            ? 'fas fa-chart-pie text-danube-500 dark:text-danube-400'
                            : 'fas fa-chart-pie text-gray-500 group-hover:text-gray-700 dark=text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Charts</span>
              <i
                :class="selected==='Charts'
                            ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                            : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark=text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected==='Charts'?'block translate transform overflow-hidden':'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="line-chart.html"
                    class="menu-dropdown-item group"
                    :class="page==='lineChart'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Line Chart
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='lineChart'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="bar-chart.html"
                    class="menu-dropdown-item group"
                    :class="page==='barChart'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Bar Chart
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='barChart'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="pie-chart.html"
                    class="menu-dropdown-item group"
                    :class="page==='pieChart'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Pie Chart
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='pieChart'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- UI Elements -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'UiElements' ? '' : 'UiElements'"
              class="menu-item group"
              :class="(selected==='UiElements' || ['alerts','buttons','images','pagination'].includes(page))?'menu-item-active':'menu-item-inactive'"
            >
              <i
                :class="(selected==='UiElements'||['alerts','buttons','images','pagination'].includes(page))
                            ? 'fas fa-cubes text-danube-500 dark:text-danube-400'
                            : 'fas fa-cubes text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Ui Elements</span>
              <i
                :class="selected==='UiElements'
                            ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                            : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected==='UiElements'?'block translate transform overflow-hidden':'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="alerts.html"
                    class="menu-dropdown-item group"
                    :class="page==='alerts'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Alerts
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='alerts'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="buttons.html"
                    class="menu-dropdown-item group"
                    :class="page==='buttons'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Buttons
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='buttons'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="images.html"
                    class="menu-dropdown-item group"
                    :class="page==='images'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Images
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='images'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href="pagination.html"
                    class="menu-dropdown-item group"
                    :class="page==='pagination'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Pagination
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='pagination'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Authentication -->
          <li>
            <a
              href="#"
              @click.prevent="selected = selected === 'Authentication' ? '' : 'Authentication'"
              class="menu-item group"
              :class="(selected==='Authentication' || ['signin','signup','advancedChart'].includes(page))?'menu-item-active':'menu-item-inactive'"
            >
              <i
                :class="(selected==='Authentication'||['signin','signup','advancedChart'].includes(page))
                            ? 'fas fa-lock text-danube-500 dark:text-danube-400'
                            : 'fas fa-lock text-gray-500 group-hover:text-gray-700 dark=text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
              <span class="menu-item-text">Authentication</span>
              <i
                :class="selected==='Authentication'
                            ? 'fas fa-chevron-down ml-auto text-danube-500 dark:text-danube-400'
                            : 'fas fa-chevron-right ml-auto text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover=text-gray-300'"
              ></i>
            </a>
            <div
              :class="selected==='Authentication'?'block translate transform overflow-hidden':'hidden translate transform overflow-hidden'"
            >
              <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                <li>
                  <a
                    href="signin.html"
                    class="menu-dropdown-item group"
                    :class="page==='signin'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Sign In
                  </a>
                </li>
                <li>
                  <a
                    href="signup.html"
                    class="menu-dropdown-item group"
                    :class="page==='signup'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Sign Up
                  </a>
                </li>
                <li>
                  <a
                    href="advanced-chart.html"
                    class="menu-dropdown-item group"
                    :class="page==='advancedChart'?'menu-dropdown-item-active':'menu-dropdown-item-inactive'"
                  >
                    Advanced Chart
                    <span
                      class="menu-dropdown-badge"
                      :class="page==='advancedChart'?'menu-dropdown-badge-active':'menu-dropdown-badge-inactive'"
                    >
                      Pro
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  </div>
</x-sidebar.container>
