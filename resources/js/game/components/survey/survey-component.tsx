import React from "react";
import SurveyEventDefinition from "./event-listeners/survey-event-definition";
import { serviceContainer } from "../../lib/containers/core-container";
import SurveyEvent from "./event-listeners/survey-event";
import SurveyDialogue from "./survey-dialogue";

export default class SurveyComponent extends React.Component<any, any> {
    private surveyEventListener: SurveyEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            show_survey: false,
            survey_id: 2,
        };

        this.surveyEventListener =
            serviceContainer().fetch<SurveyEventDefinition>(SurveyEvent);

        this.surveyEventListener.initialize(this, this.props.user_id);
        this.surveyEventListener.register();
    }

    closeModal() {
        this.setState({
            show_survey: false,
        });

        this.props.close_survey();
    }

    componentDidMount() {
        this.surveyEventListener.listen();
    }

    render() {
        if (this.state.show_survey || this.props.open_survey) {
            return (
                <SurveyDialogue
                    survey_id={this.state.survey_id}
                    is_open={true}
                    manage_modal={this.closeModal.bind(this)}
                    character_id={this.props.character_id}
                />
            );
        }

        return null;
    }
}
