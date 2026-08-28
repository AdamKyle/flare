import AdminSidebarElements from './types/admin-sidebar-elements';

const SIDEBAR_FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

const resolveSidebarElements = (): AdminSidebarElements | null => {
  const toggleButton = document.getElementById('admin-sidebar-toggle');
  const sidebar = document.getElementById('admin-sidebar');

  if (!toggleButton || !sidebar) {
    return null;
  }

  return {
    toggle_button: toggleButton,
    sidebar,
    close_button: document.getElementById('admin-sidebar-close'),
    backdrop: document.getElementById('admin-sidebar-backdrop'),
    content_area: document.getElementById('app-content-area'),
  };
};

export const initAdminSidebar = (): void => {
  const elements = resolveSidebarElements();

  if (!elements) {
    return;
  }

  const {
    toggle_button: toggleButton,
    sidebar,
    close_button: closeButton,
    backdrop,
    content_area: contentArea,
  } = elements;

  const isOpen = (): boolean => sidebar.classList.contains('translate-x-0');

  const openSidebar = (): void => {
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebar.removeAttribute('aria-hidden');
    sidebar.removeAttribute('inert');
    toggleButton.setAttribute('aria-expanded', 'true');
    toggleButton.setAttribute('aria-label', 'Close Admin navigation');
    document.body.classList.add('overflow-hidden');
    backdrop?.classList.remove('hidden');
    backdrop?.classList.add('block');
    contentArea?.setAttribute('inert', '');

    const firstFocusable = sidebar.querySelector<HTMLElement>(
      SIDEBAR_FOCUSABLE_SELECTOR
    );
    firstFocusable?.focus();
  };

  const closeSidebar = (returnFocus: boolean): void => {
    if (!isOpen()) {
      return;
    }

    sidebar.classList.remove('translate-x-0');
    sidebar.classList.add('-translate-x-full');
    sidebar.setAttribute('aria-hidden', 'true');
    sidebar.setAttribute('inert', '');
    toggleButton.setAttribute('aria-expanded', 'false');
    toggleButton.setAttribute('aria-label', 'Open Admin navigation');
    document.body.classList.remove('overflow-hidden');
    backdrop?.classList.add('hidden');
    backdrop?.classList.remove('block');
    contentArea?.removeAttribute('inert');

    if (returnFocus) {
      toggleButton.focus();
    }
  };

  sidebar.setAttribute('aria-hidden', 'true');
  sidebar.setAttribute('inert', '');
  toggleButton.setAttribute('aria-expanded', 'false');

  toggleButton.addEventListener('click', (event) => {
    event.stopPropagation();

    if (isOpen()) {
      closeSidebar(true);
    } else {
      openSidebar();
    }
  });

  closeButton?.addEventListener('click', () => {
    closeSidebar(true);
  });

  backdrop?.addEventListener('click', () => {
    closeSidebar(true);
  });

  sidebar.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      closeSidebar(false);
    });
  });

  document.addEventListener('pointerdown', (event) => {
    if (!isOpen()) {
      return;
    }

    const target = event.target;

    if (!(target instanceof Node)) {
      return;
    }

    if (sidebar.contains(target) || toggleButton.contains(target)) {
      return;
    }

    const focusWasInsideSidebar = sidebar.contains(document.activeElement);

    closeSidebar(focusWasInsideSidebar);
  });

  document.addEventListener('keydown', (event) => {
    if (!isOpen()) {
      return;
    }

    if (event.key === 'Escape') {
      closeSidebar(true);

      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    const focusable = Array.from(
      sidebar.querySelectorAll<HTMLElement>(SIDEBAR_FOCUSABLE_SELECTOR)
    );

    if (focusable.length === 0) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
};
