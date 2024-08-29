import React from "react";
import { Tab } from "@headlessui/react";
import classNames from "classnames";
import Fight from "./fighting/fight";

type TabState = "Fight" | "Character Sheet" | "Map";

interface GameCardState {
    activeTab: TabState;
}

// Define an empty interface for props
interface GameCardProps {}

export default class GameCard extends React.Component<
    GameCardProps,
    GameCardState
> {
    state: GameCardState = {
        activeTab: "Fight",
    };

    setActiveTab = (tab: TabState) => {
        this.setState({ activeTab: tab });
    };

    render() {
        const { activeTab } = this.state;

        return (
            <Tab.Group as="div" className="flex flex-col md:flex-row h-screen">
                <Tab.List
                    as="nav"
                    className="flex md:flex-col space-x-2 md:space-x-0 md:space-y-2 p-4 bg-gray-100 dark:bg-gray-800 border-b md:border-r dark:border-gray-700"
                >
                    <Tab
                        as="button"
                        className={classNames(
                            "flex items-center justify-start space-x-3 p-3 rounded-lg text-gray-800 dark:text-gray-200 transition-colors duration-300",
                            {
                                "bg-gray-300 dark:bg-gray-700":
                                    activeTab === "Fight",
                                "hover:bg-gray-200 dark:hover:bg-gray-600":
                                    activeTab !== "Fight",
                            },
                        )}
                        onClick={() => this.setActiveTab("Fight")}
                        title="Fight"
                    >
                        <i className="fas fa-fist-raised text-xl" />
                        <span className="hidden md:inline">Fight</span>
                    </Tab>
                    <Tab
                        as="button"
                        className={classNames(
                            "flex items-center justify-start space-x-3 p-3 rounded-lg text-gray-800 dark:text-gray-200 transition-colors duration-300",
                            {
                                "bg-gray-300 dark:bg-gray-700":
                                    activeTab === "Character Sheet",
                                "hover:bg-gray-200 dark:hover:bg-gray-600":
                                    activeTab !== "Character Sheet",
                            },
                        )}
                        onClick={() => this.setActiveTab("Character Sheet")}
                        title="Character Sheet"
                    >
                        <i className="fas fa-user-shield text-xl" />
                        <span className="hidden md:inline">
                            Character Sheet
                        </span>
                    </Tab>
                    <Tab
                        as="button"
                        className={classNames(
                            "flex items-center justify-start space-x-3 p-3 rounded-lg text-gray-800 dark:text-gray-200 transition-colors duration-300",
                            {
                                "bg-gray-300 dark:bg-gray-700":
                                    activeTab === "Map",
                                "hover:bg-gray-200 dark:hover:bg-gray-600":
                                    activeTab !== "Map",
                            },
                        )}
                        onClick={() => this.setActiveTab("Map")}
                        title="Map"
                    >
                        <i className="fas fa-map-marked-alt text-xl" />
                        <span className="hidden md:inline">Map</span>
                    </Tab>
                </Tab.List>
                <Tab.Panels className="flex-1 p-4">
                    {activeTab === "Fight" && (
                        <Tab.Panel>
                            <Fight />
                        </Tab.Panel>
                    )}
                    {activeTab === "Character Sheet" && (
                        <Tab.Panel>
                            {/* Content for Character Sheet */}
                            <h2 className="text-2xl text-gray-800 dark:text-gray-200">
                                Character Sheet
                            </h2>
                        </Tab.Panel>
                    )}
                    {activeTab === "Map" && (
                        <Tab.Panel>
                            {/* Content for Map */}
                            <h2 className="text-2xl text-gray-800 dark:text-gray-200">
                                Map
                            </h2>
                        </Tab.Panel>
                    )}
                </Tab.Panels>
            </Tab.Group>
        );
    }
}
