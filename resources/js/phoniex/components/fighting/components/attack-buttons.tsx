import React from "react";
import AttackButtonsProps from "./types/attack-buttons-props";
export default class AttackButtons extends React.Component<AttackButtonsProps> {
    constructor(props: AttackButtonsProps) {
        super(props);
    }

    render() {
        return (
            <div className="mt-6">
                <div className="grid grid-cols-2 gap-4">
                    <button
                        className="flex items-center justify-center space-x-2 bg-gradient-to-b from-rose-500 to-regent-st-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:opacity-90 dark:hover:opacity-100"
                        aria-label="Attack"
                    >
                        <i className="ra ra-sword text-opacity-90 text-white font-semibold" />
                        <span className="text-opacity-90 text-white font-semibold">
                            Atk
                        </span>
                    </button>
                    <button
                        className="flex items-center justify-center space-x-2 bg-gradient-to-b from-rose-500 to-regent-st-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:opacity-90 dark:hover:opacity-100"
                        aria-label="Cast"
                    >
                        <i className="ra ra-burning-book text-opacity-90 text-white font-semibold" />
                        <span className="text-opacity-90 text-white font-semibold">
                            Cast
                        </span>
                    </button>
                    <button
                        className="flex items-center justify-center space-x-2 bg-gradient-to-b from-rose-500 to-regent-st-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:opacity-90 dark:hover:opacity-100"
                        aria-label="Attack and Cast"
                    >
                        <i className="ra ra-crossed-swords text-opacity-90 text-white font-semibold" />
                        <span className="text-opacity-90 text-white font-semibold">
                            Atk & Cast
                        </span>
                    </button>
                    <button
                        className="flex items-center justify-center space-x-2 bg-gradient-to-b from-rose-500 to-regent-st-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:opacity-90 dark:hover:opacity-100"
                        aria-label="Cast and Attack"
                    >
                        <i className="ra ra-crossed-swords text-opacity-90 text-white font-semibold" />
                        <span className="text-opacity-90 text-white font-semibold">
                            Cast & Atk
                        </span>
                    </button>
                    <button
                        className="col-span-2 flex items-center justify-center space-x-2 bg-gradient-to-b from-rose-500 to-regent-st-blue-300 rounded-full p-4 text-white text-lg shadow-md focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:opacity-90 dark:hover:opacity-100"
                        aria-label="Defend"
                    >
                        <i className="ra ra-heavy-shield text-opacity-90 text-white font-semibold" />
                        <span className="text-opacity-90 text-white font-semibold">
                            Defend
                        </span>
                    </button>
                </div>
                <div className="flex justify-center mt-4 space-x-4 text-gray-800 dark:text-gray-200">
                    <a
                        href="#"
                        className="hover:underline focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        aria-label="Help"
                    >
                        Help
                    </a>
                    <span>|</span>
                    <a
                        href="#"
                        className="hover:underline focus:outline-none focus:ring-2 focus:ring-regent-st-blue-300 dark:focus:ring-regent-st-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        aria-label="Additional Stats"
                    >
                        Additional stats
                    </a>
                </div>
            </div>
        );
    }
}
