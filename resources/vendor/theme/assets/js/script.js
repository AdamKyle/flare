// Event delegation
const on = (selector, eventType, childSelector, eventHandler) => {
  const elements = document.querySelectorAll(selector);
  for (element of elements) {
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
