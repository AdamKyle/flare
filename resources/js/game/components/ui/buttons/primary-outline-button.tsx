import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";


export default class PrimaryOutlineButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
          <button className={'border-b-2 border-b-blue-500 hover:border-2 hover:rounded-sm hover:border-blue-600 hover:bg-blue-500 hover:cursor-pointer hover:text-white px-2 ' + this.props.additional_css}
               onClick={this.props.on_click}
          >
              {this.props.button_label}
          </button>
        );
    }
}
