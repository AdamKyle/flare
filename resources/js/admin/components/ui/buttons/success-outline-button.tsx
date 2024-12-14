import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class SuccessOutlineButton extends React.Component<
    ButtonProps,
    {}
> {
    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button
                type="button"
                className={
                    "py-2 px-3 text-xs border-green-600 border-2 font-medium text-center text-gray-900 " +
                    "dark:text-white hover:text-gray-200 dark:hover:text-gray-300 hover:bg-green-700 rounded-sm " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 disabled:bg-green-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 " +
                    "dark:disabled:bg-green-500 disabled:text-white " +
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
