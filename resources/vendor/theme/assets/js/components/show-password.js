// Show Password
const showPassword = () => {
  // Toggle Show Password
  const toggleShowPassword = (showPasswordBtn) => {
    const password = showPasswordBtn
      .closest(".form-control-addon-within")
      .querySelector("input");

    if (password.type === "password") {
      password.type = "text";
      showPasswordBtn.classList.remove("text-gray-600", "dark:text-gray-600");
      showPasswordBtn.classList.add("text-primary", "dark:text-primary");
    } else {
      password.type = "password";
      showPasswordBtn.classList.remove("text-primary", "dark:text-primary");
      showPasswordBtn.classList.add("text-gray-600", "dark:text-gray-600");
    }
  };

  on("body", "click", '[data-toggle="password-visibility"]', (event) => {
    const showPasswordBtn = event.target.closest(
      '[data-toggle="password-visibility"]'
    );
    toggleShowPassword(showPasswordBtn);
  });
};

showPassword();
