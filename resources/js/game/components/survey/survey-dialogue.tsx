import React from "react";
import Dialogue from "../ui/dialogue/dialogue";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import SurveyAjax from "./ajax/survey-ajax";
import { serviceContainer } from "../../lib/containers/core-container";
import Section from "../../../admin/survey-builder/deffinitions/section";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import InfoAlert from "../ui/alerts/simple-alerts/info-alert";

interface SurveyDialogueState {
    loading: boolean;
    error_message: string | null;
    success_message: string | null;
    survey: {
        title: string;
        description: string;
        sections: Section[] | [];
    };
    current_section_index: number;
    section_inputs: { [key: number]: { [key: string]: string | boolean } };
    all_sections_filled: boolean;
    showCloseConfirmation: boolean;
}

export default class SurveyDialogue extends React.Component<
    any,
    SurveyDialogueState
> {
    private surveyAjax: SurveyAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            success_message: null,
            survey: {
                title: "",
                description: "",
                sections: [],
            },
            current_section_index: 0,
            section_inputs: {},
            all_sections_filled: false,
            showCloseConfirmation: false,
        };

        this.surveyAjax = serviceContainer().fetch(SurveyAjax);
    }

    componentDidMount() {
        this.surveyAjax.getSurvey(this, this.props.survey_id);
    }

    goToNextSection() {
        const { current_section_index, survey } = this.state;
        if (current_section_index < survey.sections.length - 1) {
            this.setState({ current_section_index: current_section_index + 1 });
        }
    }

    goToPreviousSection() {
        const { current_section_index } = this.state;
        if (current_section_index > 0) {
            this.setState({ current_section_index: current_section_index - 1 });
        }
    }

    handleInputChange(
        sectionIndex: number,
        fieldKey: string,
        value: string | boolean,
    ) {
        const { section_inputs } = this.state;
        const updatedSectionInputs = {
            ...section_inputs,
            [sectionIndex]: {
                ...section_inputs[sectionIndex],
                [fieldKey]: value,
            },
        };
        this.setState(
            { section_inputs: updatedSectionInputs },
            this.checkAllSectionsFilled,
        );
    }

    checkAllSectionsFilled() {
        const { survey, section_inputs } = this.state;
        const allSectionsFilled = survey.sections.every((section, index) => {
            const inputs = section_inputs[index] || {};
            return section.input_types.every((input) => {
                const value = inputs[input.label];
                return input.type === "radio" ? Boolean(value) : true;
            });
        });
        this.setState({ all_sections_filled: allSectionsFilled });
    }

    submitSurvey() {
        console.log("Survey submission state:", this.state);
        // Implement actual survey submission logic here
    }

    handleClose() {
        this.setState({ showCloseConfirmation: true });
    }

    confirmClose() {
        this.setState({ showCloseConfirmation: false }, () => {
            console.log("Should be closing ...");
            this.props.manage_modal();
        });
    }

    cancelClose() {
        this.setState({ showCloseConfirmation: false });
    }

    render() {
        const {
            loading,
            survey,
            current_section_index,
            section_inputs,
            all_sections_filled,
            showCloseConfirmation,
        } = this.state;

        if (loading) {
            return (
                <Dialogue
                    is_open={this.props.is_open}
                    handle_close={this.handleClose.bind(this)}
                    title={"Feedback for Tlessa"}
                    large_modal={false}
                    primary_button_disabled={loading}
                >
                    <LoadingProgressBar />
                </Dialogue>
            );
        }

        const currentSection = survey.sections[current_section_index];
        const isNextDisabled = !currentSection.input_types.every((input) => {
            const value = section_inputs[current_section_index]?.[input.label];
            return input.type === "radio" ? Boolean(value) : true;
        });

        return (
            <>
                <Dialogue
                    is_open={this.props.is_open}
                    handle_close={this.handleClose.bind(this)}
                    title={"Feedback for Tlessa"}
                    large_modal={false}
                    primary_button_disabled={loading}
                    secondary_actions={{
                        secondary_button_disabled:
                            loading || !all_sections_filled,
                        secondary_button_label: "Submit",
                        handle_action: this.submitSurvey.bind(this),
                    }}
                >
                    <div className="max-h-[450px] overflow-y-scroll p-4">
                        <div className="transition-all ease-in-out duration-300">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                {this.state.survey.title}
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 mt-2 mb-6">
                                {this.state.survey.description}
                            </p>
                            <InfoAlert>
                                All fields, with the exception of text fields,
                                are required. If there are further sections to
                                the survey, scroll down to see the next and
                                previous buttons. You can submit when all
                                sections are filled out. You may only do the
                                survey once.
                            </InfoAlert>
                            <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                            {currentSection.input_types.map((input, index) => (
                                <div key={index} className="mb-6">
                                    {input.type === "radio" ? (
                                        <div>
                                            <label
                                                className="block text-gray-900 dark:text-gray-100 font-medium mb-2"
                                                onClick={() =>
                                                    this.handleInputChange(
                                                        current_section_index,
                                                        input.label,
                                                        true,
                                                    )
                                                }
                                            >
                                                {input.label}
                                            </label>
                                            {input.options?.map(
                                                (
                                                    option: string,
                                                    optIndex: number,
                                                ) => (
                                                    <div
                                                        key={optIndex}
                                                        className="flex items-center mb-2"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name={input.label}
                                                            value={option}
                                                            onChange={() =>
                                                                this.handleInputChange(
                                                                    current_section_index,
                                                                    input.label,
                                                                    option,
                                                                )
                                                            }
                                                            checked={
                                                                section_inputs[
                                                                    current_section_index
                                                                ]?.[
                                                                    input.label
                                                                ] === option
                                                            }
                                                            className="form-radio h-4 w-4 text-blue-600 dark:text-blue-400 transition duration-150 ease-in-out"
                                                        />
                                                        <span
                                                            className="ml-2 text-gray-900 dark:text-gray-100 cursor-pointer"
                                                            onClick={() =>
                                                                this.handleInputChange(
                                                                    current_section_index,
                                                                    input.label,
                                                                    option,
                                                                )
                                                            }
                                                        >
                                                            {option}
                                                        </span>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <div>
                                            <label className="block text-gray-900 dark:text-gray-100 font-medium mb-2">
                                                {input.label}
                                            </label>
                                            <MarkdownElement
                                                onChange={(value) =>
                                                    this.handleInputChange(
                                                        current_section_index,
                                                        input.label,
                                                        value,
                                                    )
                                                }
                                                initialValue={
                                                    (section_inputs[
                                                        current_section_index
                                                    ]?.[
                                                        input.label
                                                    ] as string) || ""
                                                }
                                                should_reset={false}
                                                on_reset={() => {}}
                                            />
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-between mt-6">
                            <PrimaryOutlineButton
                                button_label={"Previous"}
                                on_click={this.goToPreviousSection.bind(this)}
                                disabled={current_section_index === 0}
                            />
                            <SuccessOutlineButton
                                button_label={"Next"}
                                on_click={this.goToNextSection.bind(this)}
                                disabled={
                                    isNextDisabled ||
                                    current_section_index ===
                                        survey.sections.length - 1
                                }
                            />
                        </div>
                    </div>
                </Dialogue>
                {showCloseConfirmation && (
                    <Dialogue
                        is_open={true}
                        handle_close={this.cancelClose.bind(this)}
                        title={"Are you sure?"}
                        large_modal={false}
                        primary_button_disabled={false}
                        secondary_actions={{
                            secondary_button_disabled: false,
                            secondary_button_label: "Yes I am sure",
                            handle_action: this.confirmClose.bind(this),
                        }}
                    >
                        <p className="text-gray-700 dark:text-gray-300">
                            If you close this dialogue, you will have to start
                            over, but you can complete the survey at any time to
                            get that shiny epic mythical item!
                        </p>
                    </Dialogue>
                )}
            </>
        );
    }
}
