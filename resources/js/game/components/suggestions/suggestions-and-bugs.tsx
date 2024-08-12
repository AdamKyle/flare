import React from "react";
import Select, { ActionMeta, SingleValue } from "react-select";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import { capitalize } from "lodash";
import DangerButton from "../ui/buttons/danger-button";
import SuccessButton from "../ui/buttons/success-button";
import FileUploaderElement from "../ui/file-uploader/file-uploader-element";
import SuggestionsAndBugsProps from "./types/suggestions-and-bugs-props";
import SuggestionsAndBugsState from "./types/suggestions-and-bugs-state";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import SuggestionsAndBugsAjax from "./ajax/suggestions-and-bugs-ajax";
import { serviceContainer } from "../../lib/containers/core-container";

interface FileError {
    fileName: string;
    sizeKB: number;
    errorMessage: string;
}

export default class SuggestionsAndBugs extends React.Component<
    SuggestionsAndBugsProps,
    SuggestionsAndBugsState
> {
    private readonly suggestionsAndBugsAjax: SuggestionsAndBugsAjax;

    constructor(props: SuggestionsAndBugsProps) {
        super(props);

        this.state = {
            title: "",
            type: "",
            platform: "",
            description: "",
            files: [],
            file_errors: [], // Added state
            overlay_image: null,
            current_image_index: 0,
            processing_submission: false,
            error_message: null,
            success_message: null,
            should_reset_markdown_element: false,
            should_reset_file_upload: false,
        };

        this.suggestionsAndBugsAjax = serviceContainer().fetch(
            SuggestionsAndBugsAjax,
        );
    }

    getTypeValue() {
        if (this.state.type === "") {
            return [
                {
                    label: "Please select a type",
                    value: "",
                },
            ];
        }

        return [
            {
                label: capitalize(this.state.type),
                value: this.state.type,
            },
        ];
    }

    getPlatformValue() {
        if (this.state.platform === "") {
            return [
                {
                    label: "Please select a platform",
                    value: "",
                },
            ];
        }

        return [
            {
                label: capitalize(this.state.platform),
                value: this.state.platform,
            },
        ];
    }

    setSelectedType(newValue: SingleValue<{ label: string; value: string }>) {
        if (newValue == null || newValue.value === "") {
            return;
        }

        this.setState({
            type: newValue.value,
        });
    }

    setSelectedPlatform(
        newValue: SingleValue<{ label: string; value: string }>,
    ) {
        if (newValue === null || newValue.value === "") {
            return;
        }

        this.setState({
            platform: newValue.value,
        });
    }

    validateFileSizes(files: File[]): {
        validFiles: File[];
        fileErrors: FileError[];
    } {
        const maxSizeKB = 2048;
        const maxSizeBytes = maxSizeKB * 1024;
        const fileErrors: FileError[] = [];

        const validFiles = files.filter((file) => {
            if (file.size > maxSizeBytes) {
                fileErrors.push({
                    fileName: file.name,
                    sizeKB: Math.round(file.size / 1024),
                    errorMessage: `${file.name} is larger than ${maxSizeKB}kb - actual size: ${Math.round(file.size / 1024)}kb`,
                });
                return false;
            }
            return true;
        });

        return { validFiles, fileErrors };
    }

    updateFiles(files: File[] | []) {
        if (Array.isArray(files)) {
            const { validFiles, fileErrors } = this.validateFileSizes(files);
            const isSubmitDisabled =
                fileErrors.length > 0 || this.isSubmitDisabled();

            this.setState({
                files: validFiles,
                file_errors: fileErrors,
                error_message:
                    fileErrors.length > 0 ? null : this.state.error_message,
            });
        }
    }

    submitForum() {
        const params = {
            title: this.state.title,
            type: this.state.type,
            platform: this.state.platform,
            description: this.state.description,
            files: this.state.files,
        };

        this.setState(
            {
                processing_submission: true,
                error_message: null,
                success_message: null,
            },
            () => {
                this.suggestionsAndBugsAjax.submitFeedback(
                    this,
                    this.props.character_id,
                    params,
                );
            },
        );
    }

    isSubmitDisabled() {
        if (this.state.title === "") {
            return true;
        }

        if (this.state.type === "") {
            return true;
        }

        if (this.state.platform === "") {
            return true;
        }

        if (this.state.file_errors.length > 0) {
            return true;
        }

        return this.state.description === "";
    }

    onMarkdownElementReset() {
        this.setState({
            should_reset_markdown_element: false,
        });
    }

    fileUploaderElementReset() {
        this.setState({
            should_reset_file_upload: false,
        });
    }

    render() {
        const errorMessages = this.state.error_message
            ? this.state.error_message.split(" ")
            : [];

        return (
            <div className="mr-auto ml-auto w-full md:w-1/2">
                <BasicCard>
                    <div className="grid grid-cols-2 gap-4">
                        <span>
                            <strong>Suggestions and Bugs</strong>
                        </span>
                        <div className="text-right cursor-pointer text-red-500">
                            <button
                                onClick={this.props.manage_suggestions_and_bugs}
                            >
                                <i className="fas fa-minus-circle"></i>
                            </button>
                        </div>
                    </div>

                    <p className="my-4">
                        Below you can submit a bug report or a suggestion to
                        help make the game better. Any and all feedback is
                        welcome. You may also upload images to help with bug
                        reports or flush out your suggestions.{" "}
                        <strong>Please be as descriptive as possible</strong>
                    </p>

                    <p className="mb-4">
                        <strong>For bug reports</strong>: Please list out as
                        many steps as possible to replicate the issue.
                    </p>

                    {this.state.processing_submission ? (
                        <LoadingProgressBar />
                    ) : null}

                    {this.state.file_errors.length > 0 ? (
                        <DangerAlert>
                            <ul className="list-disc pl-5">
                                {this.state.file_errors.map((error, index) => (
                                    <li key={index}>{error.errorMessage}</li>
                                ))}
                            </ul>
                        </DangerAlert>
                    ) : null}

                    {this.state.success_message != null ? (
                        <SuccessAlert>
                            {this.state.success_message}
                        </SuccessAlert>
                    ) : null}

                    {this.state.error_message != null ? (
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                    ) : null}

                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>

                    <div>
                        <div className="flex flex-col md:flex-row items-start gap-4">
                            <div className="w-full md:w-1/4">
                                <label
                                    className="label block mb-2 md:mb-0"
                                    htmlFor="title"
                                >
                                    Title
                                </label>
                            </div>
                            <div className="w-full md:w-3/4">
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    className="form-control"
                                    onChange={(e) =>
                                        this.setState({ title: e.target.value })
                                    }
                                    value={this.state.title}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col md:flex-row items-start gap-4 my-2">
                            <div className="w-full md:w-1/4">
                                <label
                                    className="label block mb-2 md:mb-0"
                                    htmlFor="type"
                                >
                                    Type
                                </label>
                            </div>
                            <div className="w-full md:w-3/4">
                                <Select
                                    id="type"
                                    onChange={this.setSelectedType.bind(this)}
                                    options={[
                                        {
                                            label: "Bug",
                                            value: "bug",
                                        },
                                        {
                                            label: "Suggestion",
                                            value: "suggestion",
                                        },
                                    ]}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.getTypeValue()}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col md:flex-row items-start gap-4 my-2">
                            <div className="w-full md:w-1/4">
                                <label
                                    className="label block mb-2 md:mb-0"
                                    htmlFor="platform"
                                >
                                    For Platform
                                </label>
                            </div>
                            <div className="w-full md:w-3/4">
                                <Select
                                    id="platform"
                                    onChange={this.setSelectedPlatform.bind(
                                        this,
                                    )}
                                    options={[
                                        {
                                            label: "Mobile",
                                            value: "mobile",
                                        },
                                        {
                                            label: "Desktop",
                                            value: "desktop",
                                        },
                                        {
                                            label: "Both",
                                            value: "both",
                                        },
                                    ]}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.getPlatformValue()}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col md:flex-row items-start gap-4 my-2">
                            <div className="w-full md:w-1/4">
                                <label
                                    className="label block mb-2 md:mb-0"
                                    htmlFor="description"
                                >
                                    Description
                                </label>
                            </div>
                            <div className="w-full md:w-3/4">
                                <MarkdownElement
                                    onChange={(value) =>
                                        this.setState({ description: value })
                                    }
                                    initialValue={this.state.description}
                                    should_reset={
                                        this.state.should_reset_markdown_element
                                    }
                                    on_reset={this.onMarkdownElementReset.bind(
                                        this,
                                    )}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col md:flex-row items-start gap-4 my-2">
                            <div className="w-full md:w-1/4">
                                <label className="label block mb-2 md:mb-0">
                                    Attach Images
                                </label>
                            </div>
                            <div className="w-full md:w-3/4">
                                <p
                                    className={
                                        "mb-4 text-blue-700 dark:text-blue-500"
                                    }
                                >
                                    You can upload multiple images.
                                </p>
                                <FileUploaderElement
                                    on_files_change={this.updateFiles.bind(
                                        this,
                                    )}
                                    file_errors={this.state.file_errors}
                                    should_reset={
                                        this.state.should_reset_file_upload
                                    }
                                    on_reset={this.fileUploaderElementReset.bind(
                                        this,
                                    )}
                                />
                            </div>
                        </div>

                        <div className="flex justify-end gap-4 mt-4">
                            <DangerButton
                                button_label="Cancel"
                                additional_css={"mr-2"}
                                on_click={
                                    this.props.manage_suggestions_and_bugs
                                }
                            />
                            <SuccessButton
                                button_label="Submit"
                                on_click={this.submitForum.bind(this)}
                                disabled={this.isSubmitDisabled()}
                            />
                        </div>
                    </div>
                </BasicCard>
            </div>
        );
    }
}
