// Menu
const menu = () => {
  const root = document.documentElement;

  const menuType = localStorage.getItem("menuType");

  const menuBar = document.querySelector(".menu-bar");
  const menuItems = document.querySelector(".menu-items");

  if (!menuBar) return;

  if (menuType) {
    root.classList.add(menuType);
    menuBar.classList.add(menuType);
  }

  // Hide Menu Detail
  const hideMenuDetail = () => {
    menuBar.querySelectorAll(".menu-detail.open").forEach((menuDetail) => {
      hideOverlay();

      if (!menuBar.classList.contains("menu-wide")) {
        menuDetail.classList.remove("open");
      }
    });
  };

  // Hide Menu - When Clicked Elsewhere
  document.addEventListener("click", (event) => {
    if (
      !event.target.closest(".menu-items a") &&
      !event.target.closest(".menu-detail") &&
      !menuBar.classList.contains("menu-wide")
    ) {
      hideMenuDetail();
    }
  });

  // Menu Links
  on(".menu-items", "click", ".link", (event) => {
    const menuLink = event.target.closest(".link");
    const menu = menuLink.dataset.target;
    const selectedMenu = menuBar.querySelector(menu);

    if (!menuBar.classList.contains("menu-wide")) {
      if (selectedMenu) {
        showOverlay(true);
        selectedMenu.classList.add("open");
      } else {
        hideOverlay();
      }

      hideMenuDetail();

      if (selectedMenu) {
        showOverlay(true);
        selectedMenu.classList.add("open");
      } else {
        hideOverlay();
      }
    }
  });

  // Toggle Menu
  const toggleMenu = () => {
    if (menuBar.classList.contains("menu-hidden")) {
      root.classList.remove("menu-hidden");
      menuBar.classList.remove("menu-hidden");
    } else {
      root.classList.add("menu-hidden");
      menuBar.classList.add("menu-hidden");
    }
  };

  on(".top-bar", "click", "[data-toggle='menu']", (event) => {
    toggleMenu(event);
  });

  // Switch Menu Type
  const switchMenuType = (type) => {
    const openMenu = menuBar.querySelector(".menu-detail.open");

    root.classList.remove("menu-icon-only");
    menuBar.classList.remove("menu-icon-only");

    root.classList.remove("menu-wide");
    menuBar.classList.remove("menu-wide");
    deactivateWide();

    root.classList.remove("menu-hidden");
    menuBar.classList.remove("menu-hidden");

    switch (type) {
      case "icon-only":
        root.classList.add("menu-icon-only");
        menuBar.classList.add("menu-icon-only");
        localStorage.setItem("menuType", "menu-icon-only");

        if (openMenu) {
          showOverlay(true);
        }

        break;
      case "wide":
        root.classList.add("menu-wide");
        menuBar.classList.add("menu-wide");
        localStorage.setItem("menuType", "menu-wide");

        activateWide();

        if (openMenu) {
          hideOverlay();
        }

        break;
      case "hidden":
        root.classList.add("menu-hidden");
        menuBar.classList.add("menu-hidden");
        localStorage.setItem("menuType", "menu-hidden");

        hideMenuDetail();

        break;
      default:
        localStorage.removeItem("menuType");

        if (openMenu) {
          showOverlay(true);
        }
    }
  };

  // Activate Wide
  const activateWide = () => {
    menuBar.querySelector(".menu-header").classList.remove("hidden");

    menuBar.querySelectorAll(".menu-items .link").forEach((menuLink) => {
      const target = menuLink.dataset.target;

      const selectedMenu = menuBar.querySelector(".menu-detail" + target);
      if (selectedMenu) {
        selectedMenu.classList.add("collapse");
        menuLink.setAttribute("data-toggle", "collapse");
        menuLink.after(selectedMenu);
      }
    });
  };

  // Deactivate Wide
  const deactivateWide = () => {
    root.classList.remove("menu-wide");
    menuBar.classList.remove("menu-wide");

    menuBar.querySelector(".menu-header").classList.add("hidden");

    menuBar.querySelectorAll(".menu-items .link").forEach((menuLink) => {
      const target = menuLink.dataset.target;

      const selectedMenu = menuBar.querySelector(".menu-detail" + target);
      if (selectedMenu) {
        selectedMenu.classList.remove("collapse");
        menuLink.removeAttribute("data-toggle", "collapse");
        menuItems.after(selectedMenu);
      }
    });
  };

  // Auto-activate Wide
  if (menuBar.classList.contains("menu-wide")) {
    activateWide();
  }

  on(".menu-bar", "click", "[data-toggle='menu-type']", (event) => {
    const type = event.target.closest("[data-toggle='menu-type']").dataset
      .value;
    switchMenuType(type);
  });

  on("#customizer", "click", "[data-toggle='menu-type']", (event) => {
    const type = event.target.closest("[data-toggle='menu-type']").dataset
      .value;
    switchMenuType(type);
  });
};

menu();

// Show Active Page
const showActivePage = () => {
  const pageUrl = window.location.href.split(/[?#]/)[0];

  const pageLinkSelector = ".menu-bar a";

  const pageLinks = document.querySelectorAll(pageLinkSelector);

  if (!pageLinks) return;

  pageLinks.forEach((pageLink) => {
    if (pageLink.href === pageUrl) {
      pageLink.classList.add("active");

      const mainMenuTrigger = pageLink.closest(".menu-detail");

      if (!mainMenuTrigger) return;

      const mainMenu = document.querySelector(
        '.menu-items .link[data-target="[data-menu=' +
          mainMenuTrigger.dataset.menu +
          ']"]'
      );

      mainMenu.classList.add("active");
    }
  });
};

showActivePage();
