import { Component } from "react";

export const watchForDarkMode = (
    component: Component<any, { dark_tables: boolean }>,
) => {
    window.setInterval(() => {
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
