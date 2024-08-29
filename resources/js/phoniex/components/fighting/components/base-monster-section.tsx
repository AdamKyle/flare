import React from "react";
import BaseMonsterSectionProps from "./types/baee-monster-section-props";
export default class BaseMonsterSection extends React.Component<BaseMonsterSectionProps> {

    constructor(props: BaseMonsterSectionProps) {
        super(props);
    }

    render() {
        return (
            <>
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
                <div
                    className="flex md:justify-between flex-col md:flex-row md:gap-4 mt-6 text-gray-700 dark:text-gray-300">
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
            </>
        )
    }
}
