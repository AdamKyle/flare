export default interface MenuItemType {
    name: string;

    icon_class?: string;

    on_click: (...args: any[]) => void;
}
