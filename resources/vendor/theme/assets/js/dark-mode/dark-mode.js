// Dark Mode
const darkMode = () => {
  const root = document.documentElement;

  const scheme = localStorage.getItem("scheme");

  scheme && root.classList.add(scheme);

};

darkMode();
