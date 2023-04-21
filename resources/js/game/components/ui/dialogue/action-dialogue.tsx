import React from "react";
import Dialogue from "./dialogue";
import LoadingProgressBar from "../progress-bars/loading-progress-bar";
import ActionDialogueProps from "../../../lib/ui/types/dialogue/action-dialogue-props";

export default class ActionDialogue extends React.Component<ActionDialogueProps, { }> {

    constructor(props: ActionDialogueProps) {
        super(props);
    }

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      primary_button_disabled={this.props.loading}
                      secondary_actions={{
                          secondary_button_disabled: this.props.loading,
                          secondary_button_label: 'Yes. I understand.',
                          handle_action: this.props.do_action.bind(this),
                      }}
            >
                {this.props.children}

                {
                    this.props.loading ?
                        <LoadingProgressBar />
                    : null
                }

            </Dialogue>
        );
    }
}
