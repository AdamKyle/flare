import React from "react";
import Dialogue from "./dialogue";

export default class HelpDialogue extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      secondary_actions={null}
            >
                <div className='max-h-[375px] overflow-x-scroll'>
                    {this.props.children}
                </div>
            </Dialogue>
        );
    }
}
