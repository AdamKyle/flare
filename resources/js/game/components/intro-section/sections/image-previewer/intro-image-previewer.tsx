import React, { createRef } from "react";
import IntroImagePreviewerProps from "./types/intro-image-previewer-props";
import IntroImagePreviewerState from "./types/intro-image-previewer-state";

export default class IntroImagePreviewer extends React.Component<
    IntroImagePreviewerProps,
    IntroImagePreviewerState
> {
    private containerRef: React.RefObject<HTMLDivElement> =
        createRef<HTMLDivElement>();

    constructor(props: IntroImagePreviewerProps) {
        super(props);
        this.state = {
            current_index: 0,
        };
    }

    componentDidMount() {
        setTimeout(() => {
            if (this.containerRef.current) {
                this.containerRef.current.focus();
            }
        }, 0);
    }

    componentDidUpdate(prevProps: IntroImagePreviewerProps) {
        if (prevProps.is_open !== this.props.is_open && this.props.is_open) {
            this.setState({ current_index: 0 });
        }

        setTimeout(() => {
            if (this.containerRef.current) {
                this.containerRef.current.focus();
            }
        }, 0);
    }

    handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        const { current_index } = this.state;
        const { files } = this.props;
        const fileCount = files.length;

        if (event.key === "Escape") {
            this.props.on_close();
        } else if (event.key === "ArrowLeft") {
            this.setState({
                current_index: (current_index - 1 + fileCount) % fileCount,
            });
        } else if (event.key === "ArrowRight") {
            this.setState({
                current_index: (current_index + 1) % fileCount,
            });
        }
    };

    handleSwipe = (direction: "left" | "right") => {
        const { current_index } = this.state;
        const { files } = this.props;
        const fileCount = files.length;

        if (direction === "left") {
            this.setState({
                current_index: (current_index - 1 + fileCount) % fileCount,
            });
        } else if (direction === "right") {
            this.setState({
                current_index: (current_index + 1) % fileCount,
            });
        }
    };

    render() {
        const { files, is_open, on_close } = this.props;
        const { current_index } = this.state;

        if (!is_open) {
            return null;
        }

        const show_arrows = files.length > 1;
        const show_dots = files.length > 1;

        const current_file = files[current_index];
        const image_name = current_file ? current_file.name : "";

        return (
            <div
                ref={this.containerRef} // Attach ref to container div
                className="fixed inset-0 bg-black bg-opacity-75 flex justify-center items-center z-50"
                tabIndex={0}
                onKeyDown={this.handleKeyDown}
                onClick={on_close}
            >
                <div
                    className="relative p-8 bg-white dark:bg-gray-800 rounded"
                    onClick={(e) => e.stopPropagation()}
                >
                    <button
                        className="absolute top-0 right-0 m-2 p-2 bg-red-500 text-white rounded-full"
                        onClick={on_close}
                    >
                        <i className="fas fa-times"></i>
                    </button>

                    {show_arrows && (
                        <button
                            className="absolute left-0 top-1/2 transform -translate-y-1/2 p-2 bg-gray-700 text-white rounded-full"
                            onClick={() => this.handleSwipe("left")}
                        >
                            <i className="fas fa-chevron-left"></i>
                        </button>
                    )}

                    {current_file && (
                        <img
                            src={current_file.url}
                            alt={image_name}
                            className="max-w-full max-h-[calc(100vh-12rem)] object-contain"
                        />
                    )}

                    <div className="text-center mt-2">
                        <p>{image_name}</p>
                    </div>

                    {show_arrows && (
                        <button
                            className="absolute right-0 top-1/2 transform -translate-y-1/2 p-2 bg-gray-700 text-white rounded-full"
                            onClick={() => this.handleSwipe("right")}
                        >
                            <i className="fas fa-chevron-right"></i>
                        </button>
                    )}

                    {show_dots && (
                        <div className="absolute bottom-[-8px] left-0 right-0 mb-4 flex justify-center space-x-2">
                            {files.map((_, index) => (
                                <span
                                    key={index}
                                    className={`w-3 h-3 rounded-full cursor-pointer ${
                                        index === current_index
                                            ? "bg-white"
                                            : "bg-gray-400"
                                    }`}
                                    onClick={() =>
                                        this.setState({ current_index: index })
                                    }
                                ></span>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    }
}
