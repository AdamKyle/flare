import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";


export default class SuccessOutlineButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
          <button className={'border-b-2 border-b-green-600 hover:border-2 hover:rounded-sm hover:bg-green-600 hover:border-green-700 hover:cursor-pointer hover:text-white px-2 ' + this.props.additional_css}
               onClick={this.props.on_click}
          >
              {this.props.button_label}
          </button>
        );
    }
}
