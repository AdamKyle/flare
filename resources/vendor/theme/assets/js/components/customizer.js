// Customizer
const customizer = () => {
  const checkSettings = () => {
    const sidebarCustomizer = document.getElementById("customizer");

    if (sidebarCustomizer) {
      // RTL
      const dir = localStorage.getItem("dir");

      if (dir) {
        document.dir = dir;

        const rtl = sidebarCustomizer.querySelector('[data-toggle="rtl"]');

        if (dir === "rtl") {
          rtl.checked = true;
        } else {
          rtl.checked = false;
        }
      }

      // Dark Mode
      const scheme = localStorage.getItem("scheme");

      if (scheme) {
        const darkModeToggler = sidebarCustomizer.querySelector(
          '[data-toggle="darkMode"]'
        );

        if (scheme === "dark") {
          darkModeToggler.checked = true;
        } else {
          darkModeToggler.checked = false;
        }
      }

      // Menu
      let menuType = localStorage.getItem("menuType");

      if (menuType) {
        menuType = menuType.replace("menu-", "");

        const menuTypeInput = sidebarCustomizer.querySelector(
          "[data-value='" + menuType + "']"
        );

        menuTypeInput.checked = true;
      }
    }
  };

  // Toggle RTL
  const toggleRTL = () => {
    if (document.dir === "ltr") {
      document.dir = "rtl";
      localStorage.setItem("dir", "rtl");
    } else {
      document.dir = "ltr";
      localStorage.setItem("dir", "ltr");
    }
  };

  // Toggle Customizer
  const toggleCustomizer = () => {
    const customizer = document.getElementById("customizer");
    if (customizer.classList.contains("open")) {
      customizer.classList.remove("open");
      hideOverlay();
    } else {
      customizer.classList.add("open");
      showOverlay();
    }
  };

  on("#customizer", "click", '[data-toggle="darkMode"]', () => {
    const darkModeToggler = document.getElementById("darkModeToggler");
    darkModeToggler.click();
  });

  on("#customizer", "click", '[data-toggle="rtl"]', () => {
    toggleRTL();
  });

  on("#customizer", "click", '[data-toggle="sidebar"]', () => {
    checkSettings();
  });

  on("body", "click", '[data-toggle="customizer"]', () => {
    toggleCustomizer();
  });

  checkSettings();
};

customizer();
