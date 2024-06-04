export var watchForDarkModeChange = function (component) {
    window.setInterval(function () {
        if (
            window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_tables !== true
        ) {
            component.setState({
                dark_tables: window.localStorage.scheme === "dark",
            });
        } else if (
            !window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_tables
        ) {
            component.setState({
                dark_tables: false,
            });
        }
    }, 10);
};
//# sourceMappingURL=watch-for-dark-mode-change.js.map
