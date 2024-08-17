import React from "react";
import Dialogue from "../ui/dialogue/dialogue";

export default class SurveyDialogue extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Feed back for tlessa"}
                large_modal={true}
                primary_button_disabled={this.state.action_loading}
            >
                Content ...
            </Dialogue>
        );
    }
}
