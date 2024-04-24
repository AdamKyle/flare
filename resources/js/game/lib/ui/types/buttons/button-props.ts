export default interface ButtonProps {
    additional_css?: string;

    disabled?: boolean;

    button_label: string | JSX.Element;

    on_click: (args: any) => void;
}
