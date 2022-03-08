export default interface ButtonProps {

    additional_css?: string

    disabled?: boolean,

    button_label: string,

    on_click: (args: any) => void;
}
