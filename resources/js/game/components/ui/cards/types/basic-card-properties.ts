import React from "react";

export default interface BasicCardProperties {
    additionalClasses?: string;

    children?: React.ReactNode;

    close_action?: () => void;
}
