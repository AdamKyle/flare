/**
 * Plain-JavaScript behavior for the shared player header used by both the ordinary
 * (non-admin) Blade shell and the dedicated game layout.
 *
 * Replaces the Alpine-driven mobile menu toggle and dark-mode toggle that previously lived in
 * `resources/js/vendor/alpine.js` + `x-data`/`x-init`/`@click` directives in the Blade templates.
 */

const DARK_MODE_STORAGE_KEY = 'darkMode';

const initDarkModeToggle = (): void => {
  const toggleButton = document.getElementById('app-header-dark-mode-toggle');

  if (!toggleButton) {
    return;
  }

  toggleButton.addEventListener('click', () => {
    let isDarkModeEnabled = false;

    try {
      isDarkModeEnabled = Boolean(
        JSON.parse(localStorage.getItem(DARK_MODE_STORAGE_KEY) ?? 'false')
      );
    } catch {
      isDarkModeEnabled = false;
    }

    const nextIsDarkModeEnabled = !isDarkModeEnabled;

    localStorage.setItem(
      DARK_MODE_STORAGE_KEY,
      JSON.stringify(nextIsDarkModeEnabled)
    );
    document.documentElement.classList.toggle('dark', nextIsDarkModeEnabled);
  });
};

const initHeaderMenuToggle = (): void => {
  const toggleButton = document.getElementById('app-header-menu-toggle');
  const menu = document.getElementById('app-header-menu');

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

    const target = event.target as Node;

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

const initProfileMenuToggle = (): void => {
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

    const target = event.target as Node;

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

document.addEventListener('DOMContentLoaded', () => {
  initDarkModeToggle();
  initHeaderMenuToggle();
  initProfileMenuToggle();
});
