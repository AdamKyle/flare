import React from "react";
import DangerButton from "../../../../../ui/buttons/danger-button";
import {startCase} from "lodash";

export default class StatBreakDown extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <div className='flex justify-between'>
                    <h3>{
                        startCase(this.props.type.replace('-', ' '))
                    }</h3>
                    <DangerButton button_label={'Close'} on_click={this.props.close_section}/>
                </div>

                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                Content here ...
            </div>
        )
    }
}
