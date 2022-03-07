import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class DangerButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return(
            <button className={'inline-flex justify-center hover:bg-red-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-red-600 dark:bg-red-700 text-white dark:hover:bg-red-600 dark:hover:text-white font-semibold\n' +
                '  py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-red-400 dark:disabled:bg-red-400 ' + this.props.additional_css} onClick={this.props.on_click} disabled={this.props.disabled}>
                {this.props.button_label}
            </button>
        )
    }
}
