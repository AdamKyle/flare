import React, { createRef } from "react";
import Select from "react-select";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import { capitalize } from "lodash";
import DangerButton from "../ui/buttons/danger-button";
import SuccessButton from "../ui/buttons/success-button";
import { FileUploader } from "react-drag-drop-files";

const fileTypes = ["JPG", "PNG", "GIF"];

interface ClassNameProps {
    manage_suggestions_and_bugs: () => void;
    cancel: () => void;
    submit: () => void;
}

interface FileWithPreview extends File {
    preview?: string;
}

interface ClassNameState {
    title: string;
    type: string;
    platform: string;
    description: string;
    files: FileWithPreview[];
    overlayImage: FileWithPreview | null;
    currentImageIndex: number;
}

export default class SuggestionsAndBugs extends React.Component<
    ClassNameProps,
    ClassNameState
> {
    overlayRef = createRef<HTMLDivElement>(); // Create a ref for the overlay

    constructor(props: ClassNameProps) {
        super(props);

        this.state = {
            title: "",
            type: "",
            platform: "",
            description: "",
            files: [],
            overlayImage: null,
            currentImageIndex: 0,
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

    handleFileChange = (files: FileList | File | null) => {
        if (!files) return;

        // Convert FileList to an array if necessary
        const fileArray =
            files instanceof FileList
                ? Array.from(files)
                : files instanceof File
                  ? [files]
                  : [];

        const validFiles = fileArray.filter((file) => file instanceof File);

        const filesWithPreview = validFiles.map((file) => ({
            ...file,
            preview: URL.createObjectURL(file),
        }));

        this.setState((prevState) => ({
            files: [...prevState.files, ...filesWithPreview],
        }));
    };

    handleRemoveFile = (
        index: number,
        event: React.MouseEvent<HTMLButtonElement>,
    ) => {
        event.stopPropagation();
        const files = [...this.state.files];
        URL.revokeObjectURL(files[index].preview || ""); // Clean up URL object
        files.splice(index, 1);
        this.setState({ files });
    };

    handleImageClick = (index: number) => {
        this.setState(
            {
                overlayImage: this.state.files[index],
                currentImageIndex: index,
            },
            () => {
                // Focus on the overlay when it opens
                this.overlayRef.current?.focus();
            },
        );
    };

    handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (event.key === "Escape") {
            this.closeOverlay();
        } else if (event.key === "ArrowLeft") {
            this.goToPreviousImage();
        } else if (event.key === "ArrowRight") {
            this.goToNextImage();
        }
    };

    closeOverlay = () => {
        this.setState({ overlayImage: null });
    };

    goToPreviousImage = () => {
        const prevIndex =
            (this.state.currentImageIndex - 1 + this.state.files.length) %
            this.state.files.length;
        this.setState({
            currentImageIndex: prevIndex,
            overlayImage: this.state.files[prevIndex],
        });
    };

    goToNextImage = () => {
        const nextIndex =
            (this.state.currentImageIndex + 1) % this.state.files.length;
        this.setState({
            currentImageIndex: nextIndex,
            overlayImage: this.state.files[nextIndex],
        });
    };

    setCurrentImage = (index: number) => {
        this.setState({
            currentImageIndex: index,
            overlayImage: this.state.files[index],
        });
    };

    render() {
        const showArrows = this.state.files.length > 1;
        const showDots = this.state.files.length > 1;

        const overlayImage = this.state.overlayImage;
        const imageName = overlayImage ? overlayImage.name : "";
        const imageSize = overlayImage
            ? `${(overlayImage.size / 1024).toFixed(2)} KB`
            : "";

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
                        reports or flush out your suggestions.
                        <strong>Please be as descriptive as possible</strong>
                    </p>

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
                                    onChange={(option) => {}}
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
                                    onChange={(option) => {}}
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
                            <div className="w-full md:w-3/4 p-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md focus-within:bg-white focus-within:dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <MarkdownElement
                                    onChange={(content) =>
                                        this.setState({ description: content })
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex flex-col items-center my-4">
                            <label
                                className="label block mb-2 text-gray-900 dark:text-gray-300"
                                htmlFor="fileUploader"
                            >
                                Upload or drop a file right here
                                <br />
                                JPG, PNG, GIF
                            </label>
                            <div className="flex justify-center w-full">
                                <FileUploader
                                    id="fileUploader"
                                    handleChange={this.handleFileChange}
                                    name="files"
                                    types={fileTypes}
                                    multiple={true}
                                    classes="w-full p-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            {this.state.files.length > 0 && (
                                <div className="flex flex-wrap mt-4 items-center justify-center gap-2">
                                    {this.state.files.map((file, index) => (
                                        <div
                                            key={index}
                                            className="relative w-1/4 p-2"
                                        >
                                            <img
                                                src={file.preview}
                                                alt={`preview-${index}`}
                                                className="w-full h-auto cursor-pointer"
                                                onClick={() =>
                                                    this.handleImageClick(index)
                                                }
                                            />
                                            <button
                                                type="button"
                                                className="absolute top-0 right-0 p-1 bg-red-600 text-white rounded-full"
                                                onClick={(e) =>
                                                    this.handleRemoveFile(
                                                        index,
                                                        e,
                                                    )
                                                }
                                            >
                                                <i className="fas fa-times"></i>
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {this.state.overlayImage && (
                            <div
                                className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
                                onKeyDown={this.handleKeyDown}
                                ref={this.overlayRef}
                                tabIndex={-1}
                            >
                                <div className="relative w-11/12 md:w-1/2 bg-white dark:bg-gray-800 rounded-lg p-4">
                                    {showArrows && (
                                        <>
                                            <button
                                                type="button"
                                                className="absolute top-1/2 left-0 transform -translate-y-1/2 p-2 bg-gray-800 text-white rounded-full cursor-pointer"
                                                onClick={this.goToPreviousImage}
                                            >
                                                <i className="fas fa-chevron-left"></i>
                                            </button>
                                            <button
                                                type="button"
                                                className="absolute top-1/2 right-0 transform -translate-y-1/2 p-2 bg-gray-800 text-white rounded-full cursor-pointer"
                                                onClick={this.goToNextImage}
                                            >
                                                <i className="fas fa-chevron-right"></i>
                                            </button>
                                        </>
                                    )}
                                    <img
                                        src={overlayImage.preview}
                                        alt="Overlay"
                                        className="w-full h-auto"
                                    />
                                    <div className="absolute top-2 right-2">
                                        <button
                                            type="button"
                                            className="p-2 bg-gray-700 text-red-500 rounded-full"
                                            onClick={this.closeOverlay}
                                        >
                                            <i className="fas fa-minus-circle"></i>
                                        </button>
                                    </div>
                                    <div className="flex flex-col items-center mt-4">
                                        <div className="text-gray-900 dark:text-gray-100 mb-2">
                                            <span className="block text-sm">
                                                {imageName}
                                            </span>
                                            <span className="block text-sm">
                                                {imageSize}
                                            </span>
                                        </div>
                                        {showDots && (
                                            <div className="flex justify-center mt-2">
                                                {this.state.files.map(
                                                    (_, index) => (
                                                        <button
                                                            key={index}
                                                            type="button"
                                                            className={`w-3 h-3 mx-1 rounded-full cursor-pointer ${
                                                                index ===
                                                                this.state
                                                                    .currentImageIndex
                                                                    ? "bg-blue-500"
                                                                    : "bg-gray-400"
                                                            }`}
                                                            onClick={() =>
                                                                this.setCurrentImage(
                                                                    index,
                                                                )
                                                            }
                                                        ></button>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="text-right mt-4"></div>
                </BasicCard>
            </div>
        );
    }
}
