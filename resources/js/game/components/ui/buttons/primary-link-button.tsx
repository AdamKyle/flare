import React from "react";
import ButtonProps from "../../../lib/ui/types/buttons/button-props";

export default class PrimaryLinkButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return(
            <button className={'hover:text-blue-500 text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 ' +
                'disabled:text-blue-400 dark:disabled:bg-blue-400 disabled:line-through ' +
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:focus-visible:ring-white ' +
                'focus-visible:ring-opacity-75 ' + this.props.additional_css} onClick={this.props.on_click} disabled={this.props.disabled}>
                {this.props.button_label}
            </button>
        )
    }
}
