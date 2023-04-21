// Fullscreen
const fullscreen = () => {
  const fullScreenToggler = document.getElementById("fullScreenToggler");

  if (!fullScreenToggler) return;

  const element = document.documentElement;

  // Open fullscreen
  const openFullscreen = () => {
    if (element.requestFullscreen) {
      element.requestFullscreen();
    } else if (element.mozRequestFullScreen) {
      element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
      element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
      element.msRequestFullscreen();
    }
  };

  // Close fullscreen
  const closeFullscreen = () => {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
  };

  // Check fullscreen
  const checkFullscreen = () => {
    if (
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.mozFullScreenElement ||
      document.msFullscreenElement
    ) {
      return true;
    }

    return false;
  };

  // Toggle Button Icon
  const togglerBtnIcon = () => {
    if (fullScreenToggler.classList.contains("la-expand-arrows-alt")) {
      fullScreenToggler.classList.remove("la-expand-arrows-alt");
      fullScreenToggler.classList.add("la-compress-arrows-alt");
    } else {
      fullScreenToggler.classList.remove("la-compress-arrows-alt");
      fullScreenToggler.classList.add("la-expand-arrows-alt");
    }
  };

  on("body", "click", "#fullScreenToggler", () => {
    if (checkFullscreen()) {
      closeFullscreen();
    } else {
      openFullscreen();
    }

    togglerBtnIcon();
  });
};

fullscreen();
