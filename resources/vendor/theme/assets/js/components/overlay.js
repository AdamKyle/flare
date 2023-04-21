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
  overlayToRemove = document.querySelector(".overlay");

  if (!overlayToRemove) return;

  document.body.classList.remove("overlay-show");

  overlayToRemove.classList.remove("active");
  document.body.removeChild(overlayToRemove);
};
