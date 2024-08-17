import ImagePreviewFile from "../deffinitions/file";

export default interface IntroImagePreviewerProps {
    files: ImagePreviewFile[];
    is_open: boolean;
    on_close: () => void;
}
