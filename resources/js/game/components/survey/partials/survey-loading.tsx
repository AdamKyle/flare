import React from "react";
import Dialogue from "../../ui/dialogue/dialogue";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";

interface SurveyLoadingProps {
    is_open: boolean;
}

export default class SurveyLoading extends React.Component<SurveyLoadingProps> {
    constructor(props: SurveyLoadingProps) {
        super(props);
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={() => {}}
                title={"Feedback for Tlessa"}
                large_modal={false}
                primary_button_disabled={true}
            >
                <LoadingProgressBar />
            </Dialogue>
        );
    }
}
