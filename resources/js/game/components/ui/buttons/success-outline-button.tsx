import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";


export default class SuccessOutlineButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
            <button type="button"
                    className={'py-2 px-3 text-xs border-green-800 border-2 font-medium text-center text-gray-900 dark:text-gray-200 hover:text-gray-200 dark:hover:text-gray-300 hover:bg-green-900 rounded-sm focus:ring-4 focus:ring-green-300 dark:hover:bg-green-600 dark:focus:ring-green-800 ' + this.props.additional_css}
                    onClick={this.props.on_click}>
                {this.props.button_label}
            </button>
        );
    }
}
