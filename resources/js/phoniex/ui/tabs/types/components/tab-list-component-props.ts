import { ReactNode } from "react";

export default interface TabListComponentProps {
    tabs: ReactNode[] | [];
    selected_index: number;
    icons: string[];
    on_tab_change: (index: number) => void;
    additional_css?: string;
}
