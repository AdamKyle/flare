import FileError from "../deffinitions/file-error";

export default interface SuggestionsAndBugsState {
    processing_submission: boolean;
    title: string;
    type: string;
    platform: string;
    description: string;
    files: File[];
    file_errors: FileError[] | [];
    overlay_image: File | null;
    current_image_index: number;
    error_message: string | null;
    success_message: string | null;
    should_reset_markdown_element: boolean;
    should_reset_file_upload: boolean;
}
