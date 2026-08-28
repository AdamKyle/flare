export const initAdminNavigationGroups = (): void => {
  const toggles = document.querySelectorAll<HTMLElement>(
    '[data-admin-navigation-toggle]'
  );

  const expandGroup = (toggle: HTMLElement, panel: HTMLElement): void => {
    panel.classList.remove('hidden');
    panel.classList.add('flex');
    toggle.setAttribute('aria-expanded', 'true');
  };

  const collapseGroup = (toggle: HTMLElement, panel: HTMLElement): void => {
    panel.classList.add('hidden');
    panel.classList.remove('flex');
    toggle.setAttribute('aria-expanded', 'false');
  };

  toggles.forEach((toggle) => {
    const panelId = toggle.getAttribute('aria-controls');
    const panel = panelId ? document.getElementById(panelId) : null;

    if (!panel) {
      return;
    }

    const containsCurrentPage = Array.from(
      panel.querySelectorAll<HTMLAnchorElement>('a[href]')
    ).some((link) => link.pathname === window.location.pathname);

    if (containsCurrentPage) {
      expandGroup(toggle, panel);
    }

    toggle.addEventListener('click', () => {
      const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

      if (isExpanded) {
        collapseGroup(toggle, panel);
      } else {
        expandGroup(toggle, panel);
      }
    });
  });
};
