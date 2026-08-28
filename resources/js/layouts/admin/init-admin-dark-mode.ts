const DARK_MODE_STORAGE_KEY = 'darkMode';

export const initAdminDarkMode = (): void => {
  const toggleButton = document.getElementById('app-header-dark-mode-toggle');

  if (!toggleButton) {
    return;
  }

  toggleButton.addEventListener('click', () => {
    let isDarkModeEnabled: boolean;

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
