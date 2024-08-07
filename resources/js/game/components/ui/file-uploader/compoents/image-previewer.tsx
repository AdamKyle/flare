import React, { createRef } from "react";
import ImagePreviewerProps from "./types/image-previewer-props";
import ImagePreviewerState from "./types/image-previewer-state";

export default class ImagePreviewer extends React.Component<
    ImagePreviewerProps,
    ImagePreviewerState
> {
    private containerRef: React.RefObject<HTMLDivElement> =
        createRef<HTMLDivElement>(); // Ref for focusing

    constructor(props: ImagePreviewerProps) {
        super(props);
        this.state = {
            current_index: props.current_index,
        };
    }

    componentDidMount() {
        // Use setTimeout to ensure focus happens after component mounts
        setTimeout(() => {
            if (this.containerRef.current) {
                this.containerRef.current.focus();
            }
        }, 0);
    }

    componentDidUpdate(prevProps: ImagePreviewerProps) {
        if (prevProps.current_index !== this.props.current_index) {
            this.setState({ current_index: this.props.current_index });
        }

        // Use setTimeout to ensure focus happens after component updates
        setTimeout(() => {
            if (this.containerRef.current) {
                this.containerRef.current.focus();
            }
        }, 0);
    }

    handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (event.key === "Escape") {
            this.props.on_close();
        } else if (event.key === "ArrowLeft") {
            this.props.on_navigate("previous");
        } else if (event.key === "ArrowRight") {
            this.props.on_navigate("next");
        }
    };

    render() {
        const { files, current_index, on_close, on_navigate, on_select } =
            this.props;
        const show_arrows = files.length > 1;
        const show_dots = files.length > 1;

        const current_file = files[current_index];
        const image_name = current_file ? current_file.name : "";
        const image_size = current_file
            ? `${(current_file.size / 1024).toFixed(2)} KB`
            : "";

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
                            onClick={() => on_navigate("previous")}
                        >
                            <i className="fas fa-chevron-left"></i>
                        </button>
                    )}

                    {current_file && (
                        <img
                            src={current_file.preview_url} // Ensure using 'preview_url'
                            alt={image_name}
                            className="max-w-full max-h-[calc(100vh-12rem)] object-contain"
                        />
                    )}

                    <div className="text-center mt-2">
                        <p>{image_name}</p>
                        <p>{image_size}</p>
                    </div>

                    {show_arrows && (
                        <button
                            className="absolute right-0 top-1/2 transform -translate-y-1/2 p-2 bg-gray-700 text-white rounded-full"
                            onClick={() => on_navigate("next")}
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
                                    onClick={() => on_select(index)}
                                ></span>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    }
}
