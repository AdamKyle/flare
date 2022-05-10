export default interface DialogueTypes {

    is_open: boolean;

    handle_close: () => void;

    primary_button_disabled?: boolean;

    secondary_actions: {
        secondary_button_disabled: boolean;
        secondary_button_label: string;
        handle_action: (args: any) => void;
    } | null

    tertiary_actions?: {
        tertiary_button_disabled: boolean;
        tertiary_button_label: string;
        handle_action: (args: any) => void;
    } | null

    title: string | JSX.Element;

    large_modal?: boolean;

    medium_modal?: boolean;
}
