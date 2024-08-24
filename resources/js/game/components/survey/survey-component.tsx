import React from "react";
import SurveyEventDefinition from "./event-listeners/survey-event-definition";
import { serviceContainer } from "../../lib/containers/core-container";
import SurveyEvent from "./event-listeners/survey-event";
import SurveyDialogue from "./survey-dialogue";
import SurveyComponentProps from "./types/survey-component-props";
import SurveyComponentState from "./types/survey-component-state";

export default class SurveyComponent extends React.Component<
    SurveyComponentProps,
    SurveyComponentState
> {
    private surveyEventListener: SurveyEventDefinition;

    constructor(props: SurveyComponentProps) {
        super(props);

        this.state = {
            show_survey: false,
            survey_id: null,
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

        this.setState({
            survey_id: this.props.survey_id,
        });
    }

    componentDidUpdate(prevProps: SurveyComponentProps) {
        if (this.props.survey_id !== null && this.state.survey_id === null) {
            this.setState({
                survey_id: this.props.survey_id,
            });
        }
    }

    render() {
        if (this.state.show_survey || this.props.open_survey) {
            return (
                <SurveyDialogue
                    survey_id={this.state.survey_id}
                    is_open={true}
                    manage_modal={this.closeModal.bind(this)}
                    character_id={this.props.character_id}
                    set_success_message={this.props.set_success_message}
                />
            );
        }

        return null;
    }
}
