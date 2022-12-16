import MenuItemType from "./menu-item-type";

export default interface DropDownProps {

    menu_items: MenuItemType[] | [];

    selected_name?: string | null;

    secondary_selected?: string;

    button_title: string;

    disabled?: boolean;

    use_relative?: boolean;
}
