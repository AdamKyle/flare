import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";


export default class DangerOutlineButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button type="button"
                    className={'py-2 px-3 text-xs border-red-600 border-2 font-medium text-center text-gray-900 dark:text-white hover:text-gray-200 dark:hover:text-gray-300 hover:bg-red-700 rounded-sm focus:ring-4 focus:ring-red-300 dark:hover:bg-red-800 dark:focus:ring-red-800 disabled:bg-red-600 dark:disabled:bg-red-500 disabled:text-white ' + this.props.additional_css}
                    onClick={this.props.on_click}
                    disabled={this.props.disabled}>
                {this.props.button_label}
            </button>
        );
    }
}
