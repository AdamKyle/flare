export default interface SuggestionsAndBugsState {
    processing_submission: boolean;
    title: string;
    type: string;
    platform: string;
    description: string;
    files: File[];
    overlay_image: File | null;
    current_image_index: number;
    error_message: string | null;
    success_message: string | null;
}
