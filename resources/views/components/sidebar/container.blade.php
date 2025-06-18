<aside
  x-cloak
  :class="sidebarToggle ? 'translate-x-0' : '-translate-x-full'"
  class="sidebar fixed left-0 top-0 z-[999999] flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 duration-300 ease-linear dark:border-gray-800 dark:bg-black"
  @click.outside="sidebarToggle = false"
>
  {{$slot}}
</aside>