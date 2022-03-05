import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";


export default class SuccessOutlineButton extends React.Component<ButtonProps, {}> {

    constructor(props: ButtonProps) {
        super(props);
    }

    render() {
        return (
          <div className={'border-b-2 border-b-green-700 hover:border-2 hover:rounded-sm hover:bg-green-700 hover:border-green-800 hover:cursor-pointer hover:text-white ' + this.props.additional_css}
               onClick={this.props.on_click}
          >
              {this.props.button_label}
          </div>
        );
    }
}
