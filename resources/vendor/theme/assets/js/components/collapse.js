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
