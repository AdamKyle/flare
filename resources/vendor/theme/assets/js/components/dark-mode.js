// Dark Mode
const darkMode = () => {
  const root = document.documentElement;

  const scheme = localStorage.getItem("scheme");

  scheme && root.classList.add(scheme);

  const darkModeToggler = document.getElementById("darkModeToggler");

  if (!darkModeToggler) return;

  if (scheme === "dark") {
    darkModeToggler.checked = "checked";
  }

  // Enable Dark Mode
  const enableDarkMode = () => {
    root.classList.remove("light");
    root.classList.add("dark");
    localStorage.setItem("scheme", "dark");
  };

  // Disable Dark Mode
  const disableDarkMode = () => {
    root.classList.remove("dark");
    root.classList.add("light");
    localStorage.removeItem("scheme");
  };

  // Check Dark Mode
  const checkDarkMode = () => {
    if (root.classList.contains("dark")) {
      return true;
    } else {
      return false;
    }
  };

  on("body", "change", "#darkModeToggler", () => {
    if (checkDarkMode()) {
      disableDarkMode();
    } else {
      enableDarkMode();
    }
  });
};

darkMode();
