import React from "react";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";

export default class SuggestionsAndBugs extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="mr-auto ml-auto w-full md:w-1/2">
                <BasicCard>
                    <div className="grid grid-cols-2">
                        <span>
                            <strong>Suggestions and Bugs</strong>
                        </span>
                        <div className="text-right cursor-pointer text-blue-500">
                            <button
                                onClick={this.props.manage_suggestions_and_bugs}
                            >
                                <i className="fas fa-plus-circle"></i>
                            </button>
                        </div>
                    </div>

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
                                    For Platform
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
                                    Description
                                </label>
                            </div>
                            <div className="w-3/4 p-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md focus-within:bg-white focus-within:dark:bg-gray-700">
                                <MarkdownElement onChange={() => {}} />
                            </div>
                        </div>
                    </div>
                </BasicCard>
            </div>
        );
    }
}
