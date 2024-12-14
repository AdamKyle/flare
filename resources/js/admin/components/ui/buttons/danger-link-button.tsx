import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class DangerLinkButton extends React.Component<ButtonProps, {}> {
    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button
                className={
                    "hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 " +
                    "disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
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
