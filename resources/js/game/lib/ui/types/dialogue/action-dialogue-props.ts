import React from "react";

export default interface ActionDialogueProps {
    is_open: boolean;

    manage_modal: () => void;

    title: string | JSX.Element;

    loading: boolean;

    do_action: () => void;

    children?: React.ReactNode;
}
