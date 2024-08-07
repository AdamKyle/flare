import FileWithPreview from "../deffinitions/file-with-preview";

export default interface FileUploaderElementProps {
    on_files_change: (files: FileWithPreview[]) => void;
}
