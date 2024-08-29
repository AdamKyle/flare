import React from "react";
import DangerButtonProps from "./types/danger-button-props";

export default class DangerButton extends React.Component<DangerButtonProps> {
    constructor(props: DangerButtonProps) {
        super(props);
    }

    render() {
        return (
            <button
                onClick={this.props.on_click}
                className="px-4 py-2 bg-rose-600 text-white rounded-lg shadow hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 dark:focus:ring-rose-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                aria-label="Initiate Fight"
            >
                {this.props.label}
            </button>
        )
    }
}
