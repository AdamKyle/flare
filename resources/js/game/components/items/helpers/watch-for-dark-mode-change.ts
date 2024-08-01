import ItemTable from "../item-table";

export const watchForDarkModeChange = (component: ItemTable) => {
    window.setInterval(() => {
        const hasScheme = Object.prototype.hasOwnProperty.call(
            window.localStorage,
            "scheme",
        );
        const isDarkMode = hasScheme && window.localStorage.scheme === "dark";

        if (hasScheme && component.state.dark_tables !== true) {
            component.setState({
                dark_tables: isDarkMode,
            });
        } else if (!hasScheme && component.state.dark_tables) {
            component.setState({
                dark_tables: false,
            });
        }
    }, 10);
};
