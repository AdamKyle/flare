import FileWithPreview from "../../deffinitions/file-with-preview";

export default interface ImagePreviewerProps {
    files: FileWithPreview[];
    current_index: number;
    on_close: () => void;
    on_navigate: (direction: "previous" | "next") => void;
    on_select: (index: number) => void;
    error_message: string;
}
