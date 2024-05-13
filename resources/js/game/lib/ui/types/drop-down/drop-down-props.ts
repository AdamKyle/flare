import MenuItemType from "./menu-item-type";

export default interface DropDownProps {
    menu_items: MenuItemType[] | [];

    selected_name?: string | null;

    secondary_selected?: string;

    button_title: string;

    disabled?: boolean;

    use_relative?: boolean;

    show_close_button?: boolean;

    close_button_action?: () => void;

    show_alert?: boolean;

    alert_names?: string[];

    greenButton?: boolean;
}
