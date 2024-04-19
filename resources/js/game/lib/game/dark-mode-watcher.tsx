import CharacterInventoryTabs from "../../sections/character-sheet/components/character-inventory-tabs";
import CharacterSkillsTabs from "../../sections/character-sheet/components/character-skills-tabs";
import {Component} from "react";
import ItemComparison from "../../sections/chat/modals/item-comparison";
import CharacterClassRanks from "../../sections/character-sheet/components/character-class-ranks";
import CharacterClassRankSpecialtiesSection
    from "../../components/character-sheet/additional-stats-section/sections/character-class-rank-specialties-section";
import ItemView from "../../components/modals/item-details/item-view";

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

export const watchForDarkModeClassSpecialtyChange = (component: CharacterClassRankSpecialtiesSection) => {
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

export const watchForChatDarkModeItemViewChange = (component: ItemView) => {
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
    let previousScheme = window.localStorage.scheme;

    window.setInterval(() => {
      if (window.localStorage.hasOwnProperty('scheme')) {
        const currentScheme = window.localStorage.scheme;

        if (currentScheme === 'dark' && !component.state.dark_tables) {
          component.setState({
            dark_tables: true
          });
        } else if (currentScheme !== 'dark' && component.state.dark_tables) {
          component.setState({
            dark_tables: false
          });
        }

        previousScheme = currentScheme;
      } else if (component.state.dark_tables) {
        component.setState({
          dark_tables: false
        });
      }
    }, 10);
}
