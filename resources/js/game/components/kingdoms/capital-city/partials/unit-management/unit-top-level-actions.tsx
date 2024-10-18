import React from "react";
import UnitTopLevelActionsProps from "../../types/partials/unit-management/unit-top-level-actions-props";

export default class UnitTopLevelActions extends React.Component<UnitTopLevelActionsProps> {
    constructor(props: UnitTopLevelActionsProps) {
        super(props);
    }

    render() {
        return (
            <>
                <input
                    type="text"
                    value={this.props.search_term}
                    onChange={this.props.handle_search_change}
                    placeholder="Search by kingdom name, unit name, or map name"
                    className="w-full mb-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom name, unit name, or map name"
                />

                <div className="flex space-x-2 my-4">
                    <button
                        className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        onClick={this.props.send_orders}
                    >
                        Send Orders
                    </button>
                    <button
                        className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                        onClick={this.props.reset_queue}
                    >
                        Reset Queue
                    </button>
                    <button
                        className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        onClick={this.props.reset_filters}
                    >
                        Reset Filters
                    </button>
                </div>
            </>
        );
    }
}
