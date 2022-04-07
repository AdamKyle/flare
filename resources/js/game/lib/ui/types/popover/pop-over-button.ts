export default interface PopOverButtonProps {

    button_type: 'danger' | 'primary' | 'success'

    button_title: string;

    disabled?: boolean;

    additional_css?: string;

    make_small?: boolean;
}
