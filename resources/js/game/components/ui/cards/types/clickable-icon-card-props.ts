import React from "react";

export default interface ClickableIconCardProps {
    icon_class: string;
    title: string;
    children: React.ReactNode;
    on_click: () => void;
}
