import React, { createRef } from "react";
import Select from "react-select";
import BasicCard from "../ui/cards/basic-card";
import MarkdownElement from "../ui/markdown-element/markdown-element";
import { capitalize } from "lodash";
import DangerButton from "../ui/buttons/danger-button";
import SuccessButton from "../ui/buttons/success-button";
import { FileUploader } from "react-drag-drop-files";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";

const fileTypes = ["JPG", "PNG", "GIF"];

interface ClassNameProps {
    manage_suggestions_and_bugs: () => void;
    cancel: () => void;
    submit: () => void;
}

interface FileWithPreview extends File {
    preview?: string;
    name: string;
    size: number;
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
            name: file.name,
            size: file.size,
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
        console.log("overlayImage", overlayImage);
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
                        reports or flush out your suggestions.{" "}
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
                                    onChange={(option) => {
                                    }}
                                    options={[
                                        {
                                            label: "Bug",
                                            value: "bug"
                                        },
                                        {
                                            label: "Suggestion",
                                            value: "suggestion"
                                        }
                                    ]}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000"
                                        })
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
                                    onChange={(option) => {
                                    }}
                                    options={[
                                        {
                                            label: "Mobile",
                                            value: "mobile"
                                        },
                                        {
                                            label: "Desktop",
                                            value: "desktop"
                                        },
                                        {
                                            label: "Both",
                                            value: "both"
                                        }
                                    ]}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base: any) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000"
                                        })
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
                                You can upload multiple images, click them to
                                preview and use the right/left arrow keys to
                                navigate and press esc to close the preview.
                            </p>
                            <FileUploader
                                handleChange={this.handleFileChange}
                                name="file"
                                types={fileTypes}
                                multiple
                                hoverTitle="Drop Here"
                            />
                            <div className="mt-2 flex flex-wrap gap-2">
                                {this.state.files.map((file, index) => (
                                    <div
                                        key={index}
                                        className="relative cursor-pointer"
                                        onClick={() =>
                                            this.handleImageClick(index)
                                        }
                                    >
                                        <img
                                            src={file.preview}
                                            alt={file.name}
                                            className="w-20 h-20 object-contain"
                                        />
                                        <button
                                            className="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1"
                                            onClick={(event) =>
                                                this.handleRemoveFile(
                                                    index,
                                                    event
                                                )
                                            }
                                        >
                                            <i className="fas fa-times"></i>
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>

                    <p className={"my-4 italic"}>
                        Abusing this system by submitting spam can and will get
                        your account banned. Please only use this system to
                        submit bugs and suggestions to improve the game.
                    </p>

                    <div className="flex justify-end">
                        <SuccessButton
                            button_label="Submit"
                            on_click={this.props.submit}
                            additional_css={"mr-2"}
                        />
                        <DangerButton
                            button_label="Cancel"
                            on_click={this.props.cancel}
                        />
                    </div>
                </BasicCard>

                {this.state.overlayImage && (
                    <div
                        ref={this.overlayRef} // Assign the ref to the overlay div
                        className="fixed inset-0 bg-black bg-opacity-75 flex justify-center items-center z-50"
                        tabIndex={0}
                        onKeyDown={this.handleKeyDown}
                        onClick={this.closeOverlay}
                    >
                        <div
                            className="relative p-8 bg-white dark:bg-gray-800 rounded"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <button
                                className="absolute top-0 right-0 m-2 p-2 bg-red-500 text-white rounded-full"
                                onClick={this.closeOverlay}
                            >
                                <i className="fas fa-times"></i>
                            </button>

                            {showArrows && (
                                <button
                                    className="absolute left-0 top-1/2 transform -translate-y-1/2 p-2 bg-gray-700 text-white rounded-full"
                                    onClick={this.goToPreviousImage}
                                >
                                    <i className="fas fa-chevron-left"></i>
                                </button>
                            )}

                            <img
                                src={this.state.overlayImage.preview}
                                alt={imageName}
                                className="max-w-full max-h-[calc(100vh-12rem)] object-contain"
                            />
                            <div className="text-center mt-2">
                                <p>{imageName}</p>
                                <p>{imageSize}</p>
                            </div>

                            {showArrows && (
                                <button
                                    className="absolute right-0 top-1/2 transform -translate-y-1/2 p-2 bg-gray-700 text-white rounded-full"
                                    onClick={this.goToNextImage}
                                >
                                    <i className="fas fa-chevron-right"></i>
                                </button>
                            )}

                            {showDots && (
                                <div className="absolute bottom-[-8px] left-0 right-0 mb-4 flex justify-center space-x-2">
                                    {this.state.files.map((_, index) => (
                                        <span
                                            key={index}
                                            className={`w-3 h-3 rounded-full cursor-pointer ${
                                                index ===
                                                this.state.currentImageIndex
                                                    ? "bg-white"
                                                    : "bg-gray-400"
                                            }`}
                                            onClick={() =>
                                                this.setCurrentImage(index)
                                            }
                                        ></span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        );
    }
}
