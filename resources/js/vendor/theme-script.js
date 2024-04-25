import tippy, {delegate} from 'tippy.js';

// Event delegation
const on = (selector, eventType, childSelector, eventHandler) => {
    const elements = document.querySelectorAll(selector);
    for (const element of elements) {
        element.addEventListener(eventType, (eventOnElement) => {
            if (eventOnElement.target.closest(childSelector)) {
                eventHandler(eventOnElement);
            }
        });
    }
};

// AnimateCSS
const animateCSS = (element, animation, prefix = "animate__") => {
    return new Promise((resolve, reject) => {
        const animationName = `${prefix}${animation}`;
        const node = element;

        node.classList.add(`${prefix}animated`, `${prefix}faster`, animationName);

        const handleAnimationEnd = (event) => {
            event.stopPropagation();
            node.classList.remove(
                `${prefix}animated`,
                `${prefix}faster`,
                animationName
            );
            resolve("Animation Ended.");
        };

        node.addEventListener("animationend", handleAnimationEnd, { once: true });
    });
};

// Viewport Width
// Define our viewportWidth variable
let viewportWidth;

// Set/update the viewportWidth value
const setViewportWidth = () => {
    viewportWidth = window.innerWidth || document.documentElement.clientWidth;
};

// Set our initial width
setViewportWidth();

// On resize events, recalculate
window.addEventListener(
    "resize",
    () => {
        setViewportWidth();
    },
    false
);

// Open Collapse
const openCollapse = (collapse, callback) => {
    collapse.style.transitionProperty = "height, opacity";
    collapse.style.transitionDuration = "200ms";
    collapse.style.transitionTimingFunction = "ease-in-out";

    setTimeout(() => {
        collapse.style.height = collapse.scrollHeight + "px";
        collapse.style.opacity = 1;
    }, 200);

    collapse.addEventListener(
        "transitionend",
        () => {
            collapse.classList.add("open");

            collapse.style.removeProperty("height");
            collapse.style.removeProperty("opacity");

            collapse.style.removeProperty("transition-property");
            collapse.style.removeProperty("transition-duration");
            collapse.style.removeProperty("transition-timing-function");

            if (typeof callback === "function") callback();
        },
        { once: true }
    );
};

// Collapse
const collapse = () => {
    const selector = '[data-toggle="collapse"]';

    // Toggle Collapse
    const toggleCollapse = (collapseTrigger) => {
        collapseTrigger.classList.toggle("active");

        // Collapse
        const collapses = document.querySelectorAll(collapseTrigger.dataset.target);
        collapses.forEach((collapse) => {
            if (collapse.classList.contains("open")) {
                closeCollapse(collapse);
            } else {
                openCollapse(collapse);
            }
        });

        // Accordion
        const accordion = collapseTrigger.closest(".accordion");
        if (accordion) {
            const accordionTriggers = accordion.querySelectorAll(selector);
            accordionTriggers.forEach((accordionTrigger) => {
                if (accordionTrigger !== collapseTrigger) {
                    accordionTrigger.classList.remove("active");
                }
            });

            const accordions = accordion.querySelectorAll(".collapse");
            accordions.forEach((accordion) => {
                if (accordion.classList.contains("open")) {
                    closeCollapse(accordion);
                }
            });
        }
    };

    on("body", "click", selector, (event) => {
        const collapseTrigger = event.target.closest(selector);
        toggleCollapse(collapseTrigger);
    });
};

collapse();


// Close Collapse
const closeCollapse = (collapse, callback) => {
    collapse.style.overflowY = "hidden";
    collapse.style.height = collapse.scrollHeight + "px";

    collapse.style.transitionProperty = "height, opacity";
    collapse.style.transitionDuration = "200ms";
    collapse.style.transitionTimingFunction = "ease-in-out";

    setTimeout(() => {
        collapse.style.height = 0;
        collapse.style.opacity = 0;
    }, 200);

    collapse.addEventListener(
        "transitionend",
        () => {
            collapse.classList.remove("open");

            collapse.style.removeProperty("overflow-y");
            collapse.style.removeProperty("height");
            collapse.style.removeProperty("opacity");

            collapse.style.removeProperty("transition-property");
            collapse.style.removeProperty("transition-duration");
            collapse.style.removeProperty("transition-timing-function");

            if (typeof callback === "function") callback();
        },
        { once: true }
    );
};

document.addEventListener("DOMContentLoaded", () => {
    // Sortable
    let element = null;

    element = document.getElementById("sortable-style-1");
    if (element) {
        const sortable = Sortable.create(element, {
            animation: 150,
        });
    }

    element = document.getElementById("sortable-style-2");
    if (element) {
        const sortable = Sortable.create(element, {
            handle: ".handle",
            animation: 150,
        });
    }

    element = document.getElementById("sortable-style-3");
    if (element) {
        const sortable = Sortable.create(element, {
            animation: 150,
        });
    }

    // Editors
    // CKEditor
    const editor = document.getElementById("ckeditor");
    if (editor) {
        ClassicEditor.create(editor);
    }

    // Carousel
    const carousel = document.getElementById("carousel-style-1");
    if (carousel) {
        const dir = () => {
            if (document.dir == "rtl") {
                return "rtl";
            } else {
                return "ltr";
            }
        };

        new Glide(carousel, {
            direction: dir(),
            type: "carousel",
            perView: 4,
            gap: 20,
            breakpoints: {
                640: {
                    perView: 1,
                },
                768: {
                    perView: 2,
                },
            },
        }).mount();
    }
});

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

// Overlay
// Show
const showOverlay = (workspace) => {
    if (document.querySelector(".overlay")) return;

    document.body.classList.add("overlay-show");

    const overlay = document.createElement("div");
    if (workspace) {
        overlay.setAttribute("class", "overlay workspace");
    } else {
        overlay.setAttribute("class", "overlay");
    }

    document.body.appendChild(overlay);
    overlay.classList.add("active");
};

// Hide
const hideOverlay = () => {
    const overlayToRemove = document.querySelector(".overlay");

    if (!overlayToRemove) return;

    document.body.classList.remove("overlay-show");

    overlayToRemove.classList.remove("active");
    document.body.removeChild(overlayToRemove);
};

// Sidebar
const sidebar = () => {
    // Toggle Sidebar
    const toggleSidebar = () => {
        const sidebar = document.querySelector(".sidebar:not(.sidebar_customizer)");
        if (sidebar.classList.contains("open")) {
            sidebar.classList.remove("open");
            hideOverlay();
        } else {
            sidebar.classList.add("open");
            showOverlay(true);
        }
    };

    on("body", "click", '[data-toggle="sidebar"]', () => {
        toggleSidebar();
    });
};

sidebar();

// Tabs
const tabs = () => {
    let toggling = false;

    on("body", "click", '[data-toggle="tab"]', (event) => {
        const trigger = event.target.closest('[data-toggle="tab"]');

        const tabs = trigger.closest(".tabs");
        const activeTabTrigger = tabs.querySelector(".tab-nav .active");
        const activeTab = tabs.querySelector(".collapse.open");
        const targetedTab = tabs.querySelector(trigger.dataset.target);

        if (toggling) return;
        if (activeTabTrigger === trigger) return;

        // Trigger
        activeTabTrigger.classList.remove("active");
        trigger.classList.add("active");

        // Tab
        // Close
        toggling = true;

        closeCollapse(activeTab, () => {
            openCollapse(targetedTab, () => {
                toggling = false;
            });
        });
    });

    // Wizard (Previous/Next)
    on("body", "click", '[data-toggle="wizard"]', (event) => {
        const wizard = event.target.closest(".wizard");
        const direction = event.target.dataset.direction;
        const tabLinks = wizard.querySelectorAll(".nav-link");
        const activeLink = wizard.querySelector(".nav-link.active");

        let activeIndex = 0;

        tabLinks.forEach((link, index) => {
            if (link === activeLink) {
                activeIndex = index;
            }
        });

        switch (direction) {
            case "next":
                if (tabLinks[activeIndex + 1]) {
                    tabLinks[activeIndex + 1].click();
                }
                break;
            case "previous":
                if (tabLinks[activeIndex - 1]) {
                    tabLinks[activeIndex - 1].click();
                }
                break;
        }
    });
};

tabs();

const toggleDarkMode = () => {
    const root = document.documentElement;
    const scheme = localStorage.getItem("scheme");

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
}

toggleDarkMode();

// Tippy
const customTippy = () => {

    // Menu tooltip
    delegate("body", {
        target: '.menu-icon-only [data-toggle="tooltip-menu"]',
        touch: ["hold", 500],
        theme: "light-border tooltip",
        offset: [0, 12],
        interactive: true,
        animation: "scale",
        placement: "right",
        appendTo: () => document.body,
    });

    // General tooltip
    tippy('[data-toggle="tooltip"]', {
        theme: "light-border tooltip",
        touch: ["hold", 500],
        offset: [0, 12],
        interactive: true,
        animation: "scale",
    });

    // Popover
    tippy('[data-toggle="popover"]', {
        theme: "light-border popover",
        offset: [0, 12],
        interactive: true,
        allowHTML: true,
        trigger: "click",
        animation: "shift-toward-extreme",
        content: (reference) => {
            const title = reference.dataset.popoverTitle;
            const content = reference.dataset.popoverContent;
            const popover =
                "<h5>" + title + "</h5>" + '<div class="mt-5">' + content + "</div>";
            return popover;
        },
    });

    // Dropdown
    tippy('[data-toggle="dropdown-menu"]', {
        theme: "light-border",
        zIndex: 25,
        offset: [0, 8],
        arrow: false,
        placement: "bottom-start",
        interactive: true,
        allowHTML: true,
        animation: "shift-toward-extreme",
        content: (reference) => {
            let dropdownMenu = reference
                .closest(".dropdown")
                .querySelector(".dropdown-menu");
            dropdownMenu = dropdownMenu.outerHTML;
            return dropdownMenu;
        },
    });

    // Custom Dropdown
    tippy('[data-toggle="custom-dropdown-menu"]', {
        theme: "light-border",
        zIndex: 25,
        offset: [0, 8],
        arrow: false,
        placement: "bottom-start",
        interactive: true,
        allowHTML: true,
        animation: "shift-toward-extreme",
        content: (reference) => {
            let dropdownMenu = reference
                .closest(".dropdown")
                .querySelector(".custom-dropdown-menu");
            dropdownMenu = dropdownMenu.outerHTML;
            return dropdownMenu;
        },
    });

    // Search & Select
    tippy('[data-toggle="search-select"]', {
        theme: "light-border",
        offset: [0, 8],
        maxWidth: "none",
        arrow: false,
        placement: "bottom-start",
        trigger: "click",
        interactive: true,
        allowHTML: true,
        animation: "shift-toward-extreme",
        content: (reference) => {
            let dropdownMenu = reference
                .closest(".search-select")
                .querySelector(".search-select-menu");
            dropdownMenu = dropdownMenu.outerHTML;
            return dropdownMenu;
        },
        appendTo(reference) {
            return reference.closest(".search-select");
        },
    });
};

customTippy();

// Modal
const modal = () => {
    // Show
    const showModal = (modal) => {
        showOverlay();
        modal.classList.add("active");
        const animation = modal.dataset.animations.split(", ")[0];
        const modalContent = modal.querySelector(".modal-content");
        animateCSS(modalContent, animation);

        modal.addEventListener("click", (event) => {
            if (modal.dataset.staticBackdrop !== undefined) return;
            if (modal !== event.target) return;
            closeModal(modal);
        });
    };

    on("body", "click", '[data-toggle="modal"]', (event) => {
        const modal = document.querySelector(event.target.dataset.target);
        showModal(modal);
    });

    // Close
    const closeModal = (modal) => {
        hideOverlay();
        const animation = modal.dataset.animations.split(", ")[1];
        const modalContent = modal.querySelector(".modal-content");
        animateCSS(modalContent, animation).then(() => {
            modal.classList.remove("active");
        });
    };

    on(".modal", "click", '[data-dismiss="modal"]', (event) => {
        const modal = event.target.closest(".modal");
        closeModal(modal);
    });
};

modal();


