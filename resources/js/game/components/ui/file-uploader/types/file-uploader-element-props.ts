import FileWithPreview from "../deffinitions/file-with-preview";
import FileError from "../../../suggestions/deffinitions/file-error";
export default interface FileUploaderElementProps {
    on_files_change: (files: FileWithPreview[]) => void;
    file_errors: FileError[] | [];
    should_reset: boolean;
    on_reset: () => void;
}
