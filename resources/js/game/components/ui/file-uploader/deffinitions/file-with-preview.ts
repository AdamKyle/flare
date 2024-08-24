export default interface FileWithPreview extends File {
    preview_url?: string;
    name: string;
    size: number;
}
