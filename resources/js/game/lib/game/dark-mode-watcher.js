export var watchForDarkModeInventoryChange = function (component) {
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
export var watchForDarkModeClassRankChange = function (component) {
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
export var watchForDarkModeClassSpecialtyChange = function (component) {
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
export var watchForChatDarkModeComparisonChange = function (component) {
    window.setInterval(function () {
        if (
            window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_charts !== true
        ) {
            component.setState({
                dark_charts: window.localStorage.scheme === "dark",
            });
        } else if (
            !window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_charts
        ) {
            component.setState({
                dark_charts: false,
            });
        }
    }, 10);
};
export var watchForChatDarkModeItemViewChange = function (component) {
    window.setInterval(function () {
        if (
            window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_charts !== true
        ) {
            component.setState({
                dark_charts: window.localStorage.scheme === "dark",
            });
        } else if (
            !window.localStorage.hasOwnProperty("scheme") &&
            component.state.dark_charts
        ) {
            component.setState({
                dark_charts: false,
            });
        }
    }, 10);
};
export var watchForDarkModeSkillsChange = function (component) {
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
export var watchForDarkModeTableChange = function (component) {
    var previousScheme = window.localStorage.scheme;
    window.setInterval(function () {
        if (window.localStorage.hasOwnProperty("scheme")) {
            var currentScheme = window.localStorage.scheme;
            if (currentScheme === "dark" && !component.state.dark_tables) {
                component.setState({
                    dark_tables: true,
                });
            } else if (
                currentScheme !== "dark" &&
                component.state.dark_tables
            ) {
                component.setState({
                    dark_tables: false,
                });
            }
            previousScheme = currentScheme;
        } else if (component.state.dark_tables) {
            component.setState({
                dark_tables: false,
            });
        }
    }, 10);
};
//# sourceMappingURL=dark-mode-watcher.js.map
