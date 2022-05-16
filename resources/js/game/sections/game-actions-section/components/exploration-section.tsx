import React from "react";
import DangerButton from "../../../components/ui/buttons/danger-button";

export default class ExplorationSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <div>
                <DangerButton button_label={'Close Exploration'} on_click={this.props.manage_exploration} />
            </div>
        )
    }
}
