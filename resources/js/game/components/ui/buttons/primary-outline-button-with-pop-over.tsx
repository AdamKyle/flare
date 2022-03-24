import React from 'react';
import ButtonProps from "../../../lib/ui/types/buttons/button-props";
import PopOverContainer from "../popover/pop-over-container";
import PrimaryOutLineButtonWithPopOverProps
    from "../../../lib/ui/types/buttons/primary-out-line-button-with-pop-over-props";


export default class PrimaryOutlineButtonWithPopOver extends React.Component<PrimaryOutLineButtonWithPopOverProps, {}> {

    constructor(props: PrimaryOutLineButtonWithPopOverProps) {
        super(props);
    }

    render() {
        return (
            <div className='flex items-center'>
                <button className={'py-2 px-3 text-xs border-blue-500 border-2 font-medium text-center text-gray-900 dark:text-gray-200 hover:text-gray-200 dark:hover:text-gray-300 hover:bg-blue-600 rounded-sm focus:ring-4 focus:ring-blue-300 dark:hover:bg-blue-600 dark:focus:ring-blue-800 ' + this.props.additional_css}
                        onClick={this.props.on_click}
                >
                    {this.props.button_label}
                </button>
                <div>
                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} make_small={this.props.make_popover_small}>
                        {this.props.children}
                    </PopOverContainer>
                </div>
            </div>
        );
    }
}
