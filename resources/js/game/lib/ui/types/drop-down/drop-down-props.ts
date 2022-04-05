export default interface DropDownProps {

    menu_items: {name: string, icon_class?: string, on_click: Function }[] | [];

    selected_name?: string;

    secondary_selected?: string;

    button_title: string;

    disabled?: boolean;
}
