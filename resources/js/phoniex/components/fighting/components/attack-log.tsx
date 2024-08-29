import React from "react";
import AttackLogProps from "./types/attack-log-props";

export default class AttackLog extends React.Component<AttackLogProps> {
    constructor(props: AttackLogProps) {
        super(props);
    }

    render() {
        return (
            <div className="mt-8 p-4 bg-gray-200 dark:bg-gray-700 rounded-lg shadow-md">
                <h3 className="text-xl font-semibold text-center text-gray-800 dark:text-gray-200">
                    Attack Log
                </h3>
                <div className="flex flex-col items-center mt-4 space-y-2">
                    <p className="text-rose-500 dark:text-rose-300">
                        Goblin strikes for 12 damage!
                    </p>
                    <p className="text-emerald-500 dark:text-emerald-300">
                        Character dodges the attack!
                    </p>
                    <p className="text-regent-st-blue-500 dark:text-regent-st-blue-300">
                        Character strikes for 18 damage!
                    </p>
                    <p className="text-rose-500 dark:text-rose-300">
                        Goblin blocks the attack!
                    </p>
                </div>
                <div className="flex justify-center mt-6">
                    <button
                        className="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 dark:focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Revive
                    </button>
                </div>
            </div>
        )
    }
}
