// Toasts
const toasts = () => {
  const toastsContainer = document.getElementById("toasts-container");

  const toastCloseSelector = '[data-dismiss="toast"]';

  // Toast
  const createToast = (toast) => {
    const title = toast.dataset.title;
    const content = toast.dataset.content;
    const time = toast.dataset.time;
    let newToast =
      '<div class="toast mb-5">' +
      '<div class="toast-header">' +
      "<h5>" +
      title +
      "</h5>" +
      "<small>" +
      time +
      "</small>" +
      '<button type="button" class="close" data-dismiss="toast">&times</button>' +
      "</div>" +
      '<div class="toast-body">' +
      content +
      "</div>" +
      "</div>";

    newToast = new DOMParser().parseFromString(newToast, "text/html").body
      .firstChild;

    toastsContainer.appendChild(newToast);
    animateCSS(newToast, "fadeInUp");
  };

  on("body", "click", '[data-toggle="toast"]', (event) => {
    const toast = event.target;
    createToast(toast);
  });

  // Close Toast
  const closeToast = (toast) => {
    toast.style.overflowY = "hidden";
    toast.style.height = toast.offsetHeight + "px";

    animateCSS(toast, "fadeOutUp").then(() => {
      toast.style.transitionProperty =
        "height, margin, padding, border, opacity";
      toast.style.transitionDuration = "200ms";
      toast.style.transitionTimingFunction = "linear";

      toast.style.opacity = 0;
      toast.style.height = 0;
      toast.style.marginTop = 0;
      toast.style.marginBottom = 0;
      toast.style.paddingTop = 0;
      toast.style.paddingBottom = 0;
      toast.style.border = 0;
    });

    toast.addEventListener(
      "transitionend",
      () => {
        toast.parentNode ? toast.parentNode.removeChild(toast) : false;
      },
      { once: true }
    );
  };

  on("body", "click", toastCloseSelector, (event) => {
    const toast = event.target.closest(".toast");
    closeToast(toast);
  });
};

toasts();
