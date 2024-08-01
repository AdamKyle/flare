import { Component } from "react";
import CharacterClassRankSpecialtiesSection from "../../components/character-sheet/additional-stats-section/sections/character-class-rank-specialties-section";
import CharacterSkillsTabs from "../../components/character-sheet/character-skills-tabs/character-skills-tabs";
import ItemView from "../../components/modals/item-details/item-view";
import CharacterClassRanks from "../../sections/character-sheet/components/character-class-ranks";
import CharacterInventoryTabs from "../../sections/character-sheet/components/character-inventory-tabs";
import ItemComparison from "../../sections/chat/modals/item-comparison";

/**
 * When dark mode is enabled set the dark_table to true on the table.
 *
 * @param component
 * @type [{component: Table}]
 */
export const watchForDarkModeInventoryChange = (
    component: CharacterInventoryTabs,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_tables;

        if (shouldUpdate) {
            component.setState({
                dark_tables: isDarkMode,
            });
        }
    }, 10);
};

export const watchForDarkModeClassRankChange = (
    component: CharacterClassRanks,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_tables;

        if (shouldUpdate) {
            component.setState({
                dark_tables: isDarkMode,
            });
        }
    }, 10);
};

export const watchForDarkModeClassSpecialtyChange = (
    component: CharacterClassRankSpecialtiesSection,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_tables;

        if (shouldUpdate) {
            component.setState({
                dark_tables: isDarkMode,
            });
        }
    }, 10);
};

export const watchForChatDarkModeComparisonChange = (
    component: ItemComparison,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_charts;

        if (shouldUpdate) {
            component.setState({
                dark_charts: isDarkMode,
            });
        }
    }, 10);
};

export const watchForChatDarkModeItemViewChange = (component: ItemView) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_charts;

        if (shouldUpdate) {
            component.setState({
                dark_charts: isDarkMode,
            });
        }
    }, 10);
};

export const watchForDarkModeSkillsChange = (
    component: CharacterSkillsTabs,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_tables;

        if (shouldUpdate) {
            component.setState({
                dark_tables: isDarkMode,
            });
        }
    }, 10);
};

export const watchForDarkModeTableChange = (
    component: Component<any, { dark_tables: boolean }>,
) => {
    window.setInterval(() => {
        const isDarkMode = window.localStorage.getItem("scheme") === "dark";
        const shouldUpdate = isDarkMode !== component.state.dark_tables;

        if (shouldUpdate) {
            component.setState({
                dark_tables: isDarkMode,
            });
        }
    }, 10);
};
