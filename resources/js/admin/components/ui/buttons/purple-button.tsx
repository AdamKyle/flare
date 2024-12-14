import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class PurpleButton extends React.Component<ButtonProps, {}> {
    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button
                className={
                    "hover:bg-fuchsia-600 hover:drop-shadow-md dark:text-white hover:text-gray-300 " +
                    "bg-fuchsia-500 dark:bg-fuchsia-600 text-white dark:hover:bg-fuchsia-600 dark:hover:text-white font-semibold " +
                    "py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-fuchsia-400 dark:disabled:bg-fuchsia-400 " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-fuchsia-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
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
