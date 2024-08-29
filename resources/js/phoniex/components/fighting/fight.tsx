import React from "react";

interface FightState {
    fightInitiated: boolean;
}

export default class Fight extends React.Component<{}, FightState> {
    state: FightState = {
        fightInitiated: false,
    };

    initiateFight = () => {
        this.setState({ fightInitiated: true });
    };

    render() {
        const { fightInitiated } = this.state;

        return (
            <div className="max-w-md mx-auto p-4 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-md">
                {/* Base Section */}
                <img
                    src="https://placehold.co/600x200"
                    alt="Monster Banner"
                    className="w-full h-48 object-cover rounded-md"
                />
                <div className="flex justify-center mt-4">
                    <button
                        className="text-xs px-2 py-1 bg-blue-600 text-white rounded-full shadow hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        aria-label="Monsters look alike?"
                    >
                        Monsters look alike?
                    </button>
                </div>
                <h3 className="text-2xl text-center font-semibold text-gray-800 dark:text-gray-200 mt-4">
                    Goblin
                </h3>
                <div className="flex md:justify-between flex-col md:flex-row md:gap-4 mt-6 text-gray-700 dark:text-gray-300">
                    {/* Stats Column 1 */}
                    <div className="flex flex-col space-y-1 w-full md:w-1/2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">XP:</span>
                            <span className="ml-2">50</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">Max Level:</span>
                            <span className="ml-2">10</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">Current Level:</span>
                            <span className="ml-2">5</span>
                        </div>
                    </div>

                    {/* Stats Column 2 */}
                    <div className="flex flex-col space-y-1 w-full md:w-1/2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">HP:</span>
                            <span className="ml-2">100</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">Damage:</span>
                            <span className="ml-2">15</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">AC:</span>
                            <span className="ml-2">12</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">Accuracy:</span>
                            <span className="ml-2">80%</span>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-bold">Dodge:</span>
                            <span className="ml-2">20%</span>
                        </div>
                    </div>
                </div>

                <div className="mt-4">
                    <div className="flex justify-between">
                        <span className="text-gray-700 dark:text-gray-300">
                            Goblin
                        </span>
                        <span className="text-gray-700 dark:text-gray-300">
                            100/100 HP
                        </span>
                    </div>
                    <div className="bg-red-600 rounded-full h-4 mb-4">
                        <div
                            className="bg-green-500 h-4 rounded-full"
                            style={{ width: "100%" }}
                        ></div>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-gray-700 dark:text-gray-300">
                            Character
                        </span>
                        <span className="text-gray-700 dark:text-gray-300">
                            100/100 HP
                        </span>
                    </div>
                    <div className="bg-gray-600 rounded-full h-4">
                        <div
                            className="bg-blue-500 h-4 rounded-full"
                            style={{ width: "100%" }}
                        ></div>
                    </div>
                </div>

                {/* Attack Buttons Section */}
                {fightInitiated ? (
                    <div className="mt-6">
                        <div className="grid grid-cols-2 gap-4">
                            <button
                                className="flex items-center justify-center space-x-2 bg-gradient-to-b from-red-500 to-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Attack"
                            >
                                <i className="ra ra-sword" />
                                <span>Atk</span>
                            </button>
                            <button
                                className="flex items-center justify-center space-x-2 bg-gradient-to-b from-red-500 to-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Cast"
                            >
                                <i className="ra ra-burning-book" />
                                <span>Cast</span>
                            </button>
                            <button
                                className="flex items-center justify-center space-x-2 bg-gradient-to-b from-red-500 to-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Attack and Cast"
                            >
                                <i className="ra ra-crossed-swords" />
                                <span>Atk & Cast</span>
                            </button>
                            <button
                                className="flex items-center justify-center space-x-2 bg-gradient-to-b from-red-500 to-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Cast and Attack"
                            >
                                <i className="ra ra-crossed-swords" />
                                <span>Cast & Atk</span>
                            </button>
                            <button
                                className="col-span-2 flex items-center justify-center space-x-2 bg-gradient-to-b from-red-500 to-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Defend"
                            >
                                <i className="ra ra-heavy-shield" />
                                <span>Defend</span>
                            </button>
                        </div>
                        <div className="flex justify-center mt-4 space-x-4 text-gray-800 dark:text-gray-200">
                            <a
                                href="#"
                                className="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Help"
                            >
                                Help
                            </a>
                            <span>|</span>
                            <a
                                href="#"
                                className="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                aria-label="Additional Stats"
                            >
                                Additional stats
                            </a>
                        </div>
                    </div>
                ) : (
                    <div className="flex justify-center mt-6">
                        <button
                            onClick={this.initiateFight}
                            className="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 dark:focus:ring-red-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                            aria-label="Initiate Fight"
                        >
                            Initiate Fight
                        </button>
                    </div>
                )}

                {/* Attack Log Section */}
                <div className="mt-8 p-4 bg-gray-200 dark:bg-gray-700 rounded-lg shadow-md">
                    <h3 className="text-xl font-semibold text-center text-gray-800 dark:text-gray-200">
                        Attack Log
                    </h3>
                    <div className="flex flex-col items-center mt-4 space-y-2">
                        <p className="text-red-500">
                            Goblin strikes for 12 damage!
                        </p>
                        <p className="text-green-500">
                            Character dodges the attack!
                        </p>
                        <p className="text-blue-500">
                            Character strikes for 18 damage!
                        </p>
                        <p className="text-red-500">
                            Goblin blocks the attack!
                        </p>
                    </div>
                    <div className="flex justify-center mt-6">
                        <button className="px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-400 dark:focus:ring-green-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Revive
                        </button>
                    </div>
                </div>
            </div>
        );
    }
}
