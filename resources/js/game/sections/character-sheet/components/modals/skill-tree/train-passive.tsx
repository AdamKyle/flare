import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";

export default class TrainPassive extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        console.log(this.props.skill);
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.skill.name}
                      secondary_actions={null}
            >
                Train ...

            </Dialogue>
        );
    }
}
