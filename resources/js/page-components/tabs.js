export const tabs = (tabSelector, contentSelector) => {
  let tabsContainer = document.querySelector(tabSelector);

  let tabTogglers = tabsContainer.querySelectorAll(tabSelector + " a");

  tabTogglers.forEach(function(toggler) {
    toggler.addEventListener("click", function(e) {
      e.preventDefault();

      let tabName = this.getAttribute("href");

      let tabContents = document.querySelector(contentSelector);

      for (let i = 0; i < tabContents.children.length; i++) {

        tabTogglers[i].parentElement.classList.remove("tw-border-t-sm", "tw-border-r", "tw-border-l", "tw--mb-px", "tw-bg-blue-500");
        tabContents.children[i].classList.remove("tw-hidden");
        tabTogglers[i].classList.remove("tw-text-white");

        if (tabContents.children[i].id === tabName) {

          e.target.classList.add("tw-text-white")
          continue;
        } else {
          tabContents.children[i].classList.add("tw-hidden");
        }

      }

      e.target.parentElement.classList.add("tw-border-t-sm", "tw-border-r", "tw-border-l", "tw--mb-px", "tw-bg-blue-500");

    });
  });
}

window.pageComponentTabs = tabs;



