import React from "react";

import SurveyAjax from "./ajax/survey-ajax";
import { serviceContainer } from "../../lib/containers/core-container";
import CloseSurvey from "./partials/close-survey";
import SurveyLoading from "./partials/survey-loading";
import SurveyForm from "./partials/survey-form";
import SurveyDialogueProps from "./types/survey-dialogue-props";
import SurveyDialogueState from "./types/survey-dialogue-state";

export default class SurveyDialogue extends React.Component<
    SurveyDialogueProps,
    SurveyDialogueState
> {
    private surveyAjax: SurveyAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            saving_survey: false,
            error_message: null,
            success_message: null,
            survey: {
                id: 0,
                title: "",
                description: "",
                sections: [],
            },
            section_inputs: {},
            all_sections_filled: false,
            showCloseConfirmation: false,
        };

        this.surveyAjax = serviceContainer().fetch(SurveyAjax);
    }

    componentDidMount() {
        this.surveyAjax.getSurvey(this, this.props.survey_id);
    }

    submitSurvey = () => {
        const { section_inputs } = this.state;

        this.setState(
            {
                saving_survey: true,
                error_message: null,
                success_message: null,
            },
            () => {
                this.surveyAjax.saveSurvey(
                    this,
                    this.state.survey.id,
                    this.props.character_id,
                    section_inputs,
                );
            },
        );
    };

    handleClose = () => {
        this.setState({ showCloseConfirmation: true });
    };

    confirmClose = () => {
        this.setState({ showCloseConfirmation: false });
        this.props.manage_modal();
    };

    confirmCloseWithSuccessMessage = (message: string) => {
        this.setState({ showCloseConfirmation: false });
        this.props.manage_modal();
        this.props.set_success_message(message);
    };

    retrieveInput(inputs: {
        [index: number]: {
            [key: string]: {
                value: string | boolean | string[];
                type: string;
            };
        };
    }) {
        this.setState({
            section_inputs: inputs,
        });
    }

    render() {
        const {
            loading,
            survey,
            section_inputs,
            all_sections_filled,
            showCloseConfirmation,
        } = this.state;

        return (
            <>
                {this.state.loading ? (
                    <SurveyLoading is_open={true} />
                ) : (
                    <>
                        <SurveyForm
                            is_open={!loading && !showCloseConfirmation}
                            survey={survey}
                            section_inputs={section_inputs}
                            all_sections_filled={all_sections_filled}
                            loading={loading}
                            submitSurvey={this.submitSurvey}
                            handleClose={this.handleClose}
                            error_message={this.state.error_message}
                            success_message={this.state.success_message}
                            retrieve_input={this.retrieveInput.bind(this)}
                            saving_survey={this.state.saving_survey}
                        />

                        <CloseSurvey
                            is_open={showCloseConfirmation}
                            handle_close={() =>
                                this.setState({ showCloseConfirmation: false })
                            }
                            confirm_close={this.confirmClose}
                        />
                    </>
                )}
            </>
        );
    }
}
