import React from "react";
import TopsProfileTabsProps from "../types/tops-profile-tabs-props";

export default class TopsProfileTabs extends React.Component<TopsProfileTabsProps> {
    selectedTabClasses(): string {
        return "border-regent-st-blue-600 bg-regent-st-blue-50 text-regent-st-blue-900 dark:border-regent-st-blue-300 dark:bg-regent-st-blue-950 dark:text-regent-st-blue-100";
    }

    inactiveTabClasses(): string {
        return "border-gray-200 bg-white text-gray-800 hover:border-regent-st-blue-500 hover:text-regent-st-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-regent-st-blue-300 dark:hover:text-regent-st-blue-200";
    }

    focusClasses(): string {
        return "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900";
    }

    labelFor(tab: string): string {
        const labels: { [key: string]: string } = {
            overview: "Overview",
            stats: "Stats",
            equipment: "Equipment",
            skills: "Skills",
            factions: "Factions",
            reincarnation: "Reincarnation",
            activity: "Activity",
            quests: "Quests",
            kingdoms: "Kingdoms",
            analytics: "Analytics",
        };

        return labels[tab] ?? tab;
    }

    render() {
        return (
            <div
                role="tablist"
                aria-label="Profile sections"
                className="my-4 flex flex-wrap gap-2"
            >
                {this.props.tabs.map((tab: string) => (
                    <button
                        key={tab}
                        type="button"
                        role="tab"
                        aria-selected={this.props.active === tab}
                        className={
                            "rounded-sm border px-3 py-2 text-sm font-semibold " +
                            this.focusClasses() +
                            " " +
                            (this.props.active === tab
                                ? this.selectedTabClasses()
                                : this.inactiveTabClasses())
                        }
                        onClick={() => this.props.onChange(tab)}
                    >
                        {this.labelFor(tab)}
                    </button>
                ))}
            </div>
        );
    }
}
