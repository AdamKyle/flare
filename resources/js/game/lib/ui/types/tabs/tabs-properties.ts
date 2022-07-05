export default interface TabProperties {

    tabs: { key: string, name: string}[],

    full_width? : boolean;

    icon_key?: string;

    when_tab_changes?: (key: string) => void;

    disabled?: boolean;

}
