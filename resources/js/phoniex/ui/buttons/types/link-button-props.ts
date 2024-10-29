import { ButtonVariant } from "../enums/button-variant-enum";

export default interface LinkButtonProps {
    label: string;
    variant: ButtonVariant;
    on_click: () => void;
    aria_label?: string;
    additional_css?: string;
    disabled?: boolean;
}
