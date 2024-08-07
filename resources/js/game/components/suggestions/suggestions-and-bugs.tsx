import React, { createRef } from "react";
import Select, { ActionMeta, SingleValue } from "react-select";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import { capitalize } from "lodash";
import DangerButton from "../ui/buttons/danger-button";
import SuccessButton from "../ui/buttons/success-button";
import FileUploaderElement from "../ui/file-uploader/file-uploader-element";
import FileWithPreview from "../ui/file-uploader/deffinitions/file-with-preview";
import SuggestionsAndBugsProps from "./types/suggestions-and-bugs-props";
import SuggestionsAndBugsState from "./types/suggestions-and-bugs-state";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";

const file_types = ["JPG", "PNG", "GIF"];

export default class SuggestionsAndBugs extends React.Component<
    SuggestionsAndBugsProps,
    SuggestionsAndBugsState
> {
    overlay_ref = createRef<HTMLDivElement>(); // Create a ref for the overlay

    constructor(props: SuggestionsAndBugsProps) {
        super(props);

        this.state = {
            title: "",
            type: "",
            platform: "",
            description: "",
            files: [],
            overlay_image: null,
            current_image_index: 0,
            processing_submission: false,
            error_message: null,
            success_message: null,
        };
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

    setSelectedType(
        newValue: SingleValue<{ label: string; value: string }>,
        actionMeta: ActionMeta<{ label: string; value: string }>,
    ) {
        if (newValue == null) {
            return;
        }

        if (newValue.value === "") {
            return;
        }

        this.setState({
            type: newValue.value,
        });
    }

    setSelectedPlatform(
        newValue: SingleValue<{ label: string; value: string }>,
        actionMeta: ActionMeta<{ label: string; value: string }>,
    ) {
        if (newValue === null) {
            return;
        }

        if (newValue.value === "") {
            return;
        }

        this.setState({
            platform: newValue.value,
        });
    }

    updateFiles(files: File[] | []) {
        this.setState({
            files: files,
        });
    }

    submitForum() {
        const params = {
            title: this.state.title,
            type: this.state.type,
            platform: this.state.platform,
            description: this.state.description,
            files: this.state.files,
        };
        console.log(params);
    }

    render() {
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

                    {this.state.processing_submission ? (
                        <LoadingProgressBar />
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
                                />
                            </div>
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
                                on_files_change={this.updateFiles.bind(this)}
                            />
                        </div>
                    </div>

                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>

                    <p className={"my-4 italic"}>
                        Abusing this system by submitting spam can and will get
                        your account banned. Please only use this system to
                        submit bugs and suggestions to improve the game.
                    </p>

                    <div className="flex justify-end">
                        <DangerButton
                            button_label="Cancel"
                            on_click={this.props.manage_suggestions_and_bugs}
                        />
                        <SuccessButton
                            button_label="Submit"
                            on_click={this.submitForum.bind(this)}
                            additional_css={"mr-2"}
                        />
                    </div>
                </BasicCard>
            </div>
        );
    }
}
