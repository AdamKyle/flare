import React from "react";

export default interface TabProperties {

    tabs: { key: string, name: string}[],

    full_width? : boolean;

    icon_key?: string;

    when_tab_changes?: (key: string) => void;

    disabled?: boolean;

    additonal_css?: string;

    children?: React.ReactNode;

    listen_for_change?: (tabIndex: number) => void;
}
