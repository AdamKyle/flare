export default interface OrangeProgressBarProps {
    primary_label: string;

    secondary_label: string;

    percentage_filled: number;

    push_down: boolean;

    height_override_class?: string;

    text_override_class?: string;
}
