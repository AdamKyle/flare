import React from "react";
import SurveyPreview from "./component/survey-preview";
import CreateNewSurvey from "./ajax/create-new-survey";
import { surveyBuilderContainer } from "./container/survey-builder-container";
import EditSurvey from "./ajax/edit-survey";
import { serviceContainer } from "../../game/lib/containers/core-container";
import SurveyBuilderState from "./types/survey-builder-state";
import SurveyBuilderProps from "./types/survey-builder-props";
import InputField from "./deffinitions/input-field";
import { InputType } from "./deffinitions/input-type";
import InputFieldComponent from "./component/input-field-component";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../components/ui/alerts/simple-alerts/success-alert";
import SuccessOutlineButton from "../components/ui/buttons/success-outline-button";
import PrimaryOutlineButton from "../components/ui/buttons/primary-outline-button";
import OrangeOutlineButton from "../components/ui/buttons/orange-outline-button";

export default class SurveyBuilder extends React.Component<
    SurveyBuilderProps,
    SurveyBuilderState
> {
    private createSurveyAjax: CreateNewSurvey;

    private editSurveyAjax: EditSurvey;

    constructor(props: SurveyBuilderProps) {
        super(props);

        this.state = {
            title: "",
            description: "",
            sections: [],
            showPreview: false,
            processing: false,
            loading: false,
            success_message: null,
            error_message: null,
        };

        this.createSurveyAjax = surveyBuilderContainer().fetch(CreateNewSurvey);

        this.editSurveyAjax = serviceContainer().fetch(EditSurvey);
    }

    componentDidMount() {
        this.editSurveyAjax.fetchSurvey(this);
    }

    handleAddSection = () => {
        const sections = [...this.state.sections];
        sections.push({ title: "", input_types: [] });
        this.setState({ sections });
    };

    handleRemoveSection = (sectionIndex: number) => {
        const sections = [...this.state.sections];
        sections.splice(sectionIndex, 1);
        this.setState({ sections });
    };

    handleUpdateField = (
        sectionIndex: number,
        fieldIndex: number,
        updatedField: InputField,
    ) => {
        const sections = [...this.state.sections];
        sections[sectionIndex].input_types[fieldIndex] = updatedField;
        this.setState({ sections });
    };

    handleAddField = (sectionIndex: number) => {
        const sections = [...this.state.sections];
        sections[sectionIndex].input_types.push({
            type: InputType.Text,
            label: "",
        });
        this.setState({ sections });
    };

    handleRemoveField = (sectionIndex: number, fieldIndex: number) => {
        const sections = [...this.state.sections];
        sections[sectionIndex].input_types.splice(fieldIndex, 1);
        this.setState({ sections });
    };

    manageSurvey = () => {
        this.setState(
            {
                processing: true,
                success_message: null,
                error_message: null,
            },
            () => {
                if (this.props.survey_id) {
                    this.editSurveyAjax.saveSurvey(this);

                    return;
                }

                this.createSurveyAjax.createNewSurvey(this);
            },
        );
    };

    togglePreview = () => {
        this.setState((prevState) => ({ showPreview: !prevState.showPreview }));
    };

    // Helper method to check if the form is ready for preview
    isFormReadyForPreview = () => {
        return (
            this.state.title.trim() !== "" &&
            this.state.sections.some(
                (section) =>
                    section.title.trim() !== "" &&
                    section.input_types.some(
                        (field) => field.label.trim() !== "",
                    ),
            )
        );
    };

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        const { title, description, sections, showPreview } = this.state;

        // Determine if form is ready for buttons
        const isTitlePresent = title.trim() !== "";
        const isPreviewEnabled = this.isFormReadyForPreview();

        return (
            <div>
                <div className="p-6 bg-gray-100 dark:bg-gray-900 rounded-lg">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Survey Title:
                    </label>
                    <input
                        type="text"
                        value={title}
                        onChange={(e) =>
                            this.setState({ title: e.target.value })
                        }
                        className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                    />

                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
                        Description:
                    </label>
                    <textarea
                        value={description}
                        onChange={(e) =>
                            this.setState({ description: e.target.value })
                        }
                        className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                    />

                    {sections.map((section, sectionIndex) => (
                        <div
                            key={`section-${sectionIndex}`}
                            className="mb-6 border p-4 rounded-md bg-white dark:bg-gray-800 mt-6"
                        >
                            <h3 className="text-xl font-bold mb-4 dark:text-gray-300">
                                Section {sectionIndex + 1}
                            </h3>

                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Section Title:
                            </label>
                            <input
                                type="text"
                                value={section.title}
                                onChange={(e) => {
                                    const sections = [...this.state.sections];
                                    sections[sectionIndex].title =
                                        e.target.value;
                                    this.setState({ sections });
                                }}
                                className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                            />

                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
                                Section Description (Optional):
                            </label>
                            <textarea
                                value={section.description || ""}
                                onChange={(e) => {
                                    const sections = [...this.state.sections];
                                    sections[sectionIndex].description =
                                        e.target.value;
                                    this.setState({ sections });
                                }}
                                className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                            />

                            <div className="relative pl-4 border-l-4 dark:border-gray-700 border-gray-300">
                                {section.input_types.map(
                                    (field, fieldIndex) => (
                                        <InputFieldComponent
                                            key={`field-${sectionIndex}-${fieldIndex}`}
                                            sectionIndex={sectionIndex}
                                            fieldIndex={fieldIndex}
                                            field={field}
                                            onUpdateField={
                                                this.handleUpdateField
                                            }
                                            onRemoveField={
                                                this.handleRemoveField
                                            }
                                        />
                                    ),
                                )}
                            </div>

                            <button
                                onClick={() =>
                                    this.handleAddField(sectionIndex)
                                }
                                className="mt-4 px-4 py-2 bg-blue-500 text-white font-semibold rounded-md shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Add Field
                            </button>

                            <button
                                onClick={() =>
                                    this.handleRemoveSection(sectionIndex)
                                }
                                className="ml-4 mt-4 px-4 py-2 bg-red-500 text-white font-semibold rounded-md shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                Remove Section
                            </button>
                        </div>
                    ))}

                    {this.state.processing ? <LoadingProgressBar /> : null}

                    {this.state.error_message !== null ? (
                        <DangerAlert additional_css={"my-4"}>
                            {this.state.error_message}
                        </DangerAlert>
                    ) : null}

                    {this.state.success_message !== null ? (
                        <SuccessAlert additional_css={"my-4"}>
                            {this.state.success_message}
                        </SuccessAlert>
                    ) : null}

                    <div className="flex space-x-4 my-4">
                        <SuccessOutlineButton
                            button_label={"Add Section"}
                            on_click={this.handleAddSection.bind(this)}
                            disabled={!isTitlePresent}
                        />
                        <PrimaryOutlineButton
                            button_label={
                                this.props.survey_id
                                    ? "Save Survey"
                                    : "Create New Survey"
                            }
                            on_click={this.manageSurvey.bind(this)}
                            disabled={!isPreviewEnabled}
                        />
                        <OrangeOutlineButton
                            button_label={"Preview Survey"}
                            on_click={this.togglePreview.bind(this)}
                            disabled={!isPreviewEnabled}
                        />
                    </div>

                    {showPreview && (
                        <SurveyPreview
                            sections={sections}
                            survey_title={this.state.title}
                            survey_description={this.state.description}
                        />
                    )}
                </div>
            </div>
        );
    }
}
