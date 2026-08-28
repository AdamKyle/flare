export const initAdminProfileMenu = (): void => {
  const toggleButton = document.getElementById('app-profile-menu-toggle');
  const menu = document.getElementById('app-profile-menu');

  if (!toggleButton || !menu) {
    return;
  }

  const closeMenu = (): void => {
    menu.classList.add('hidden');
    menu.classList.remove('flex');
    toggleButton.setAttribute('aria-expanded', 'false');
  };

  const openMenu = (): void => {
    menu.classList.remove('hidden');
    menu.classList.add('flex');
    toggleButton.setAttribute('aria-expanded', 'true');
  };

  const isMenuOpen = (): boolean => !menu.classList.contains('hidden');

  toggleButton.addEventListener('click', (event) => {
    event.stopPropagation();

    if (isMenuOpen()) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  document.addEventListener('click', (event) => {
    if (!isMenuOpen()) {
      return;
    }

    const target = event.target;

    if (!(target instanceof Node)) {
      return;
    }

    if (menu.contains(target) || toggleButton.contains(target)) {
      return;
    }

    closeMenu();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !isMenuOpen()) {
      return;
    }

    closeMenu();
    toggleButton.focus();
  });
};
