import React from "react";

export default interface ResizableBoxProps {
    width?: number;

    height: number;

    style?: { [key: string]: string };

    additional_css?: string;

    children?: React.ReactNode;
}
