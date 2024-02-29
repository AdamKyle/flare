
import ItemTable from "../item-table";

export const watchForDarkModeChange = (component: ItemTable) => {
    window.setInterval(() => {
        if (window.localStorage.hasOwnProperty('scheme') && component.state.dark_tables !== true) {
            component.setState({
                dark_tables: window.localStorage.scheme === 'dark'
            })
        } else if (!window.localStorage.hasOwnProperty('scheme') && component.state.dark_tables) {
            component.setState({
                dark_tables: false
            });
        }
    }, 10);
}
