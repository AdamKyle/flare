import FileWithPreview from "../deffinitions/file-with-preview";

export default interface FileUploaderElementState {
    files: FileWithPreview[];
    show_preview: boolean;
    preview_index: number;
    uploaded_files: File[] | [];
}
