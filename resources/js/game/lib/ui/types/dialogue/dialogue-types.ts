export default interface DialogueTypes {

    is_open: boolean;

    handle_close: () => void;


    secondary_actions: {
        secondary_button_disabled: boolean;
        secondary_button_label: string;
        handle_action: (args: any) => void;
    } | null

    title: string;

    large_modal?: boolean;
}
