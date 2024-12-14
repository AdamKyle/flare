import React from "react";

export default interface AlertProps {
    additional_css?: string;

    close_alert?: () => void;

    children?: React.ReactNode;
}
