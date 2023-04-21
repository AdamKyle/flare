import React from "react";

export default interface AddGemsToItemsActionsProps {
    do_action: (action: string) => void;
    is_disabled: boolean;
    is_loading: boolean;
    children?: React.ReactNode;
}
