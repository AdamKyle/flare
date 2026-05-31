import React from "react";

export interface TabDefinition {
    key: string;
    name: string;
    [key: string]: string | boolean | undefined;
}

export default interface TabProperties {
    tabs: TabDefinition[];

    full_width?: boolean;

    icon_key?: string;

    when_tab_changes?: (key: string) => void;

    disabled?: boolean;

    additonal_css?: string;

    children?: React.ReactNode;

    listen_for_change?: (tabIndex: number) => void;
}
