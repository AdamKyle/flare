import React from "react";
import Dialogue from "../../ui/dialogue/dialogue";

interface CloseSurveyProps {
    is_open: boolean;
    handle_close: () => void;
    confirm_close: () => void;
}

export default class CloseSurvey extends React.Component<CloseSurveyProps> {
    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Are you sure?"}
                large_modal={false}
                primary_button_disabled={false}
                secondary_actions={{
                    secondary_button_disabled: false,
                    secondary_button_label: "Yes I am sure",
                    handle_action: this.props.confirm_close,
                }}
            >
                <p className="text-gray-700 dark:text-gray-300">
                    If you close this dialogue, you will have to start over, but
                    you can complete the survey at any time to get that shiny
                    epic mythical item!
                </p>
            </Dialogue>
        );
    }
}
