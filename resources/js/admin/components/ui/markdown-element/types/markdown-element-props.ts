export default interface MarkdownElementProps {
    initialValue?: string;
    onChange?: (content: string) => void;
    should_reset: boolean;
    on_reset: () => void;
}
