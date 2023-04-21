import CharacterInventoryTabs from "../../sections/character-sheet/components/character-inventory-tabs";
import CharacterSkillsTabs from "../../sections/character-sheet/components/character-skills-tabs";
import CharacterActiveBoons from "../../sections/character-sheet/components/character-active-boons";
import {Component, ReactComponentElement} from "react";
import ItemComparison from "../../sections/chat/modals/item-comparison";
import CharacterClassRanks from "../../sections/character-sheet/components/character-class-ranks";
import CharacterClassSpecialtiesModal
    from "../../sections/character-sheet/components/modals/character-class-specialties-modal";
import RankFightTops from "../../tops/rank-fight-tops";

/**
 * When dark mode is enabled set the dark_table to true on the table.
 *
 * @param component
 * @type [{component: Table}]
 */
export const watchForDarkModeInventoryChange = (component: CharacterInventoryTabs) => {
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

export const watchForDarkModeRankFightTopsChange = (component: RankFightTops) => {
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

export const watchForDarkModeClassRankChange = (component: CharacterClassRanks) => {
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

export const watchForDarkModeClassSpecialtyChange = (component: CharacterClassSpecialtiesModal) => {
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

export const watchForChatDarkModeComparisonChange = (component: ItemComparison) => {
    window.setInterval(() => {
        if (window.localStorage.hasOwnProperty('scheme') && component.state.dark_charts !== true) {
            component.setState({
                dark_charts: window.localStorage.scheme === 'dark'
            })
        } else if (!window.localStorage.hasOwnProperty('scheme') && component.state.dark_charts) {
            component.setState({
                dark_charts: false
            });
        }
    }, 10);
}

export const watchForDarkModeSkillsChange = (component: CharacterSkillsTabs) => {
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

export const watchForDarkModeTableChange = (component: Component<any, {dark_tables: boolean}>) => {
    window.setInterval(() => {
        if (window.localStorage.hasOwnProperty('scheme') && !component.state.dark_tables) {
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
