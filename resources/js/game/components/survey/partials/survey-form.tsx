import React from "react";
import Select from "react-select";
import Section from "../../../../admin/survey-builder/deffinitions/section";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import MarkdownElement from "../../ui/markdown-element/markdown-element";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import Dialogue from "../../ui/dialogue/dialogue";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";

interface SurveyFormProps {
    is_open: boolean;
    survey: {
        title: string;
        description: string;
        sections: Section[];
    };
    section_inputs: {
        [index: number]: {
            [key: string]: {
                value: string | boolean | string[];
                type: string;
            };
        };
    };
    all_sections_filled: boolean;
    loading: boolean;
    submitSurvey: () => void;
    handleClose: () => void;
    error_message: string | null;
    success_message: string | null;
    retrieve_input: (input: {
        [index: number]: {
            [key: string]: {
                value: string | boolean | string[];
                type: string;
            };
        };
    }) => void;
    saving_survey: boolean;
}

interface SurveyFormState {
    current_section_index: number;
    all_sections_filled: boolean;
    survey_input: {
        [index: number]: {
            [key: string]: {
                value: string | boolean | string[];
                type: string;
            };
        };
    };
}

interface SelectOption {
    value: string;
    label: string;
}

export default class SurveyForm extends React.Component<
    SurveyFormProps,
    SurveyFormState
> {
    private scrollContainerRef = React.createRef<HTMLDivElement>();

    constructor(props: SurveyFormProps) {
        super(props);
        this.state = {
            current_section_index: 0,
            all_sections_filled: false,
            survey_input: {},
        };
    }

    componentDidUpdate(prevProps: SurveyFormProps) {
        if (
            prevProps.section_inputs !== this.props.section_inputs ||
            prevProps.survey !== this.props.survey
        ) {
            this.checkAllSectionsFilled();
        }
    }

    goToNextSection = () => {
        const { current_section_index } = this.state;
        const { survey } = this.props;

        if (current_section_index < survey.sections.length - 1) {
            this.setState(
                { current_section_index: current_section_index + 1 },
                () => {
                    this.scrollContainerRef.current?.scrollTo({
                        top: 0,
                        behavior: "smooth",
                    });
                },
            );
        }
    };

    goToPreviousSection = () => {
        const { current_section_index } = this.state;

        if (current_section_index > 0) {
            this.setState(
                { current_section_index: current_section_index - 1 },
                () => {
                    this.scrollContainerRef.current?.scrollTo({
                        top: 0,
                        behavior: "smooth",
                    });
                },
            );
        }
    };

    handleInputChange = (
        sectionIndex: number,
        fieldKey: string,
        value: string | boolean | string[],
        inputType: string, // Add this parameter
    ) => {
        const updatedSectionInputs = {
            ...this.props.section_inputs,
            [sectionIndex]: {
                ...this.props.section_inputs[sectionIndex],
                [fieldKey]: {
                    value,
                    type: inputType, // Store the field type
                },
            },
        };

        this.setState({
            survey_input: updatedSectionInputs,
        });

        // Can we move on to the next or submit?
        this.checkAllSectionsFilled();

        // Send the input back to the parent.
        this.props.retrieve_input(updatedSectionInputs);
    };

    checkAllSectionsFilled = () => {
        const { survey, section_inputs } = this.props;

        const allFilled = survey.sections.every((section, index) => {
            return section.input_types
                .filter((input) => input.type !== "markdown") // Exclude markdown inputs
                .every((input) => {
                    const value = section_inputs[index]?.[input.label];
                    return input.type === "radio"
                        ? Boolean(value)
                        : Array.isArray(value)
                          ? value.length > 0
                          : Boolean(value);
                });
        });

        this.setState({ all_sections_filled: allFilled });
    };

    renderInputField = (input: any, index: number) => {
        const { section_inputs } = this.props;
        const { current_section_index } = this.state;

        const sectionInput =
            section_inputs[current_section_index]?.[input.label];
        const value = sectionInput ? sectionInput.value : undefined;

        switch (input.type) {
            case "radio":
                return (
                    <div key={index} className="mb-4">
                        <label className="block text-gray-900 dark:text-gray-100 font-bold mb-2">
                            {input.label}
                        </label>
                        {input.options?.map(
                            (option: string, optIndex: number) => (
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
                                                input.type,
                                            )
                                        }
                                        checked={value === option}
                                        className="form-radio h-4 w-4 text-blue-600 dark:text-blue-400 transition duration-150 ease-in-out"
                                    />
                                    <span
                                        className="ml-2 text-gray-900 dark:text-gray-100 cursor-pointer"
                                        onClick={() =>
                                            this.handleInputChange(
                                                current_section_index,
                                                input.label,
                                                option,
                                                input.type,
                                            )
                                        }
                                    >
                                        {option}
                                    </span>
                                </div>
                            ),
                        )}
                    </div>
                );

            case "checkbox":
                return (
                    <div key={index} className="mb-4">
                        <label className="block text-gray-900 dark:text-gray-100 font-bold mb-2">
                            {input.label}
                        </label>
                        {input.options?.map(
                            (option: string, optIndex: number) => (
                                <div
                                    key={optIndex}
                                    className="flex items-center mb-2"
                                >
                                    <input
                                        type="checkbox"
                                        name={input.label}
                                        value={option}
                                        onChange={(e) => {
                                            const updatedValue = Array.isArray(
                                                value,
                                            )
                                                ? e.target.checked
                                                    ? [...value, option]
                                                    : value.filter(
                                                          (item) =>
                                                              item !== option,
                                                      )
                                                : e.target.checked
                                                  ? [option]
                                                  : [];
                                            this.handleInputChange(
                                                current_section_index,
                                                input.label,
                                                updatedValue,
                                                input.type,
                                            );
                                        }}
                                        checked={
                                            Array.isArray(value) &&
                                            value.includes(option)
                                        }
                                        className="form-checkbox h-4 w-4 text-blue-600 dark:text-blue-400 transition duration-150 ease-in-out"
                                    />
                                    <span
                                        className="ml-2 text-gray-900 dark:text-gray-100 cursor-pointer"
                                        onClick={() => {
                                            const updatedValue = Array.isArray(
                                                value,
                                            )
                                                ? !value.includes(option)
                                                    ? [...value, option]
                                                    : value.filter(
                                                          (item) =>
                                                              item !== option,
                                                      )
                                                : [option];
                                            this.handleInputChange(
                                                current_section_index,
                                                input.label,
                                                updatedValue,
                                                input.type,
                                            );
                                        }}
                                    >
                                        {option}
                                    </span>
                                </div>
                            ),
                        )}
                    </div>
                );

            case "select":
                const options: SelectOption[] = (input.options || []).map(
                    (option: string) => ({
                        value: option,
                        label: option,
                    }),
                );

                return (
                    <div key={index} className="mb-4">
                        <label className="block text-gray-900 dark:text-gray-100 font-bold mb-2">
                            {input.label}
                        </label>
                        <Select
                            options={options}
                            onChange={(selectedOption: SelectOption | null) =>
                                this.handleInputChange(
                                    current_section_index,
                                    input.label,
                                    selectedOption?.value || "",
                                    input.type,
                                )
                            }
                            value={options.find(
                                (option) => option.value === value,
                            )}
                            className="basic-single mt-1"
                            classNamePrefix="select"
                        />
                    </div>
                );

            default:
                return (
                    <div key={index} className="mb-4">
                        <label className="block text-gray-900 dark:text-gray-100 font-bold mb-2">
                            {input.label}
                        </label>
                        <MarkdownElement
                            onChange={(newValue) =>
                                this.handleInputChange(
                                    current_section_index,
                                    input.label,
                                    newValue,
                                    input.type,
                                )
                            }
                            initialValue={
                                sectionInput &&
                                typeof sectionInput.value === "string"
                                    ? sectionInput.value
                                    : ""
                            }
                            should_reset={
                                !(
                                    sectionInput &&
                                    typeof sectionInput.value === "string"
                                )
                            }
                            on_reset={() => {}}
                        />
                    </div>
                );
        }
    };

    render() {
        const {
            is_open,
            survey,
            loading,
            submitSurvey,
            handleClose,
            error_message,
            success_message,
        } = this.props;
        const { current_section_index } = this.state;
        const currentSection = survey.sections[current_section_index];

        const isNextDisabled = !currentSection.input_types
            .filter((input) => input.type !== "markdown") // Exclude markdown fields
            .every((input) => {
                const sectionInput =
                    this.props.section_inputs[current_section_index]?.[
                        input.label
                    ];
                const value = sectionInput?.value;
                return input.type === "radio"
                    ? Boolean(value)
                    : Array.isArray(value)
                      ? value.length > 0
                      : Boolean(value);
            });

        console.log(currentSection);

        return (
            <Dialogue
                is_open={is_open}
                handle_close={handleClose}
                title={"Feedback for Tlessa"}
                large_modal={false}
                primary_button_disabled={loading}
                secondary_actions={{
                    secondary_button_disabled:
                        loading || !this.state.all_sections_filled,
                    secondary_button_label: "Submit",
                    handle_action: submitSurvey,
                }}
            >
                <div
                    ref={this.scrollContainerRef}
                    className="p-4 max-h-[450px] overflow-y-scroll"
                >
                    {error_message && (
                        <DangerAlert>{error_message}</DangerAlert>
                    )}
                    {success_message && (
                        <SuccessAlert>{success_message}</SuccessAlert>
                    )}

                    <h3 className="text-lg font-semibold mb-4">
                        {survey.title}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-300 mb-6">
                        {survey.description}
                    </p>
                    <InfoAlert additional_css={"my-4"}>
                        <strong>Please note:</strong> All fields are required
                        except the optional editor fields. You will be abe to
                        move forward and backwards at the bottom of the survey
                        (scroll down). Once the fields on a particular section
                        are filled out you may continue to the next section
                        (again at the bottom of the survey). Please feel free to
                        provide as much feedback as possible to help Tlessa
                        become the best PBBG around!
                    </InfoAlert>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    <h4 className="text-lg font-semibold mb-4">
                        {currentSection?.title}
                    </h4>
                    <p className="text-gray-600 dark:text-gray-300 mb-6">
                        {currentSection?.description}
                    </p>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                    {currentSection?.input_types.map(this.renderInputField)}

                    <div className="flex justify-between mt-6">
                        <PrimaryOutlineButton
                            on_click={this.goToPreviousSection}
                            button_label="Previous"
                            disabled={current_section_index === 0}
                        />
                        <SuccessOutlineButton
                            on_click={this.goToNextSection}
                            button_label="Next"
                            disabled={isNextDisabled}
                        />
                    </div>

                    {this.props.saving_survey ? <LoadingProgressBar /> : null}

                    {error_message && (
                        <DangerAlert>{error_message}</DangerAlert>
                    )}
                    {success_message && (
                        <SuccessAlert>{success_message}</SuccessAlert>
                    )}
                </div>
            </Dialogue>
        );
    }
}
