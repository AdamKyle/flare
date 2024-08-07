import React from "react";
import Select from "react-select";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import { capitalize } from "lodash";
import DangerButton from "../ui/buttons/danger-button";
import SuccessButton from "../ui/buttons/success-button";

export default class SuggestionsAndBugs extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            title: "",
            type: "",
            platform: "",
            description: "",
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
        if (this.state.type === "") {
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

    render() {
        return (
            <div className="mr-auto ml-auto w-full md:w-1/2">
                <BasicCard>
                    <div className="grid grid-cols-2">
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
                        reports or flush out your suggestions.
                        <strong>Please be as descriptive as possible</strong>
                    </p>

                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>

                    <div>
                        <div className="flex flex-row flex-wrap items-center">
                            <div className="w-1/4">
                                <label
                                    className="label block mt-2 md:mt-0 mb-2 mr-3"
                                    htmlFor="search"
                                >
                                    Title
                                </label>
                            </div>
                            <div className="w-3/4">
                                <input
                                    type="text"
                                    name="search"
                                    className="form-control"
                                    onChange={() => {}}
                                />
                            </div>
                        </div>

                        <div className="flex flex-row flex-wrap items-center my-2">
                            <div className="w-1/4">
                                <label
                                    className="label block mt-2 md:mt-0 mb-2 mr-3"
                                    htmlFor="search"
                                >
                                    Type
                                </label>
                            </div>
                            <div className="w-3/4">
                                <Select
                                    onChange={() => {}}
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

                        <div className="flex flex-row flex-wrap items-center my-2">
                            <div className="w-1/4">
                                <label
                                    className="label block mt-2 md:mt-0 mb-2 mr-3"
                                    htmlFor="search"
                                >
                                    For Platform
                                </label>
                            </div>
                            <div className="w-3/4">
                                <Select
                                    onChange={() => {}}
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

                        <div className="flex flex-row flex-wrap items-center my-2">
                            <div className="w-1/4">
                                <label
                                    className="label block mt-2 md:mt-0 mb-2 mr-3"
                                    htmlFor="search"
                                >
                                    Description
                                </label>
                            </div>
                            <div className="w-3/4 p-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md focus-within:bg-white focus-within:dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <MarkdownElement onChange={() => {}} />
                            </div>
                        </div>

                        <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>

                        <p className="my-6 italic">
                            Abusing this system can get you banned. Please only
                            submit meaningful suggestions and bugs with as much
                            detail as possible. Using this to submit spam can
                            see your account temporarily or permanently banned.
                        </p>

                        <div className="flex flex-row flex-wrap justify-end my-4">
                            <DangerButton
                                button_label={"Cancel"}
                                on_click={() =>
                                    this.props.manage_suggestions_and_bugs
                                }
                                additional_css={"mr-2"}
                            />
                            <SuccessButton
                                button_label={"Submit"}
                                on_click={() => {}}
                            />
                        </div>
                    </div>
                </BasicCard>
            </div>
        );
    }
}
