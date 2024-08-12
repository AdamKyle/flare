import React from "react";
import { FileUploader } from "react-drag-drop-files";
import FileUploaderElementProps from "./types/file-uploader-element-props";
import FileUploaderElementState from "./types/file-uploader-element-state";
import FileWithPreview from "./deffinitions/file-with-preview";
import ImagePreviewer from "./compoents/image-previewer";

const file_types = ["JPG", "PNG", "GIF"];

export default class FileUploaderElement extends React.Component<
    FileUploaderElementProps,
    FileUploaderElementState
> {
    constructor(props: FileUploaderElementProps) {
        super(props);
        this.state = {
            files: [],
            uploaded_files: [],
            show_preview: false,
            preview_index: 0,
            error_message: "", // Add error_message to the state
        };
    }

    componentDidUpdate() {
        if (this.props.should_reset && this.state.files.length > 0) {
            this.setState(
                {
                    files: [],
                    uploaded_files: [],
                    show_preview: false,
                    preview_index: 0,
                    error_message: "", // Reset error_message
                },
                () => {
                    this.props.on_reset();
                },
            );
        }
    }

    handleFileChange = (files: FileList | File | null) => {
        if (!files) return;

        const file_array =
            files instanceof FileList
                ? Array.from(files)
                : files instanceof File
                  ? [files]
                  : [];

        const valid_files = file_array.filter(
            (file) => file instanceof File,
        ) as File[];

        const files_with_preview_url: FileWithPreview[] = valid_files.map(
            (file) => ({
                ...file,
                preview_url: URL.createObjectURL(file),
                name: file.name,
                size: file.size,
            }),
        );

        this.setState((prev_state) => {
            const updated_files = [
                ...prev_state.files,
                ...files_with_preview_url,
            ];
            const updated_uploaded_files = [
                ...prev_state.uploaded_files,
                ...valid_files,
            ];
            this.props.on_files_change(updated_uploaded_files);
            return {
                files: updated_files,
                uploaded_files: updated_uploaded_files,
            };
        });
    };

    handleRemoveFile = (index: number) => {
        const files = [...this.state.files] as FileWithPreview[];
        const uploaded_files = [...this.state.uploaded_files];

        if (files[index].preview_url) {
            URL.revokeObjectURL(files[index].preview_url); // Ensure using 'preview_url'
        }

        files.splice(index, 1);
        uploaded_files.splice(index, 1);

        this.setState({ files, uploaded_files });
        // Pass the updated list of original File objects to parent
        this.props.on_files_change(uploaded_files);
    };

    handleImageClick = (index: number) => {
        const selected_file = this.state.files[index];
        const file_error = this.props.file_errors?.find(
            (error) => error.fileName === selected_file.name,
        );
        this.setState({
            show_preview: true,
            preview_index: index,
            error_message: file_error?.errorMessage || "", // Add error_message handling
        });
    };

    closePreview = () => {
        this.setState({
            show_preview: false,
            preview_index: 0,
            error_message: "", // Reset error_message on close
        });
    };

    navigatePreview = (direction: "previous" | "next") => {
        const { files, preview_index } = this.state;
        const new_index =
            direction === "previous"
                ? (preview_index - 1 + files.length) % files.length
                : (preview_index + 1) % files.length;
        const selected_file = files[new_index];
        const file_error = this.props.file_errors?.find(
            (error) => error.fileName === selected_file.name,
        );
        this.setState({
            preview_index: new_index,
            error_message: file_error?.errorMessage || "", // Update error_message on navigation
        });
    };

    selectPreview = (index: number) => {
        const selected_file = this.state.files[index];
        const file_error = this.props.file_errors?.find(
            (error) => error.fileName === selected_file.name,
        );
        this.setState({
            preview_index: index,
            error_message: file_error?.errorMessage || "", // Update error_message on selection
        });
    };

    render() {
        const { files, show_preview, preview_index, error_message } =
            this.state;
        const fileErrors = this.props.file_errors || [];

        return (
            <div>
                <FileUploader
                    handleChange={this.handleFileChange}
                    name="file"
                    types={file_types}
                    multiple
                    hoverTitle="Drop Here"
                />
                <div className="mt-2 flex flex-wrap gap-2">
                    {files.map((file, index) => {
                        const hasError = fileErrors.some(
                            (error) => error.fileName === file.name,
                        );
                        return (
                            <div
                                key={index}
                                className="relative cursor-pointer"
                                onClick={() => this.handleImageClick(index)}
                            >
                                <img
                                    src={file.preview_url} // Ensure using 'preview_url'
                                    alt={file.name}
                                    className={`w-20 h-20 object-contain cursor-pointer ${hasError ? "filter bg-red-500 bg-opacity-75" : ""}`}
                                />
                                {hasError && (
                                    <i className="fas fa-exclamation absolute inset-0 flex items-center justify-center text-yellow-600 text-3xl pointer-events-none"></i>
                                )}
                                <button
                                    className="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 cursor-pointer"
                                    onClick={(e) => {
                                        e.stopPropagation(); // Prevent the click event from propagating to the thumbnail
                                        this.handleRemoveFile(index);
                                    }}
                                >
                                    <i className="fas fa-times"></i>
                                </button>
                            </div>
                        );
                    })}
                </div>

                {show_preview && (
                    <ImagePreviewer
                        files={files}
                        current_index={preview_index}
                        on_close={this.closePreview.bind(this)}
                        on_navigate={this.navigatePreview.bind(this)}
                        on_select={this.selectPreview.bind(this)}
                        error_message={error_message} // Pass the error message
                    />
                )}
            </div>
        );
    }
}
