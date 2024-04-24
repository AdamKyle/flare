import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class PrimaryButton extends React.Component<ButtonProps, {}> {
    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button
                className={
                    "hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 " +
                    "bg-blue-600 dark:bg-blue-700 text-white dark:hover:bg-blue-600 dark:hover:text-white font-semibold " +
                    "py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-blue-400 dark:disabled:bg-blue-400 focus:outline-none focus-visible:ring-2 " +
                    "focus-visible:ring-blue-200 dark:focus-visible:ring-white focus-visible:ring-opacity-75 " +
                    this.props.additional_css
                }
                onClick={this.props.on_click}
                disabled={this.props.disabled}
            >
                {this.props.button_label}
            </button>
        );
    }
}
