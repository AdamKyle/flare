export default interface DropDownProps {

    menu_items: {name: string, icon_class?: string, on_click: Function }[] | [];

    button_title: string;
}
