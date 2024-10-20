import React from "react";
import UnitTopLevelActionsProps from "../../types/partials/unit-management/unit-top-level-actions-props";
import SuccessOutlineButton from "../../../../ui/buttons/success-outline-button";
import DangerOutlineButton from "../../../../ui/buttons/danger-outline-button";

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
                    disabled={this.props.actions_disabled}
                />

                <div className="flex space-x-2 my-4">
                    <SuccessOutlineButton
                        button_label={"Send Orders"}
                        on_click={this.props.send_orders}
                        disabled={this.props.actions_disabled}
                    />
                    <DangerOutlineButton
                        button_label={"Reset"}
                        on_click={this.props.reset}
                        disabled={this.props.actions_disabled}
                    />
                </div>
            </>
        );
    }
}
