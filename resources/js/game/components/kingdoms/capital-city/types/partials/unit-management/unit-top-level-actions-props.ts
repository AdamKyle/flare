import React from "react";
import UnitQueue from "../../../deffinitions/unit-queue";

export default interface UnitTopLevelActionsProps {
    search_term: string;
    actions_disabled: boolean;
    handle_search_change: (event: React.ChangeEvent<HTMLInputElement>) => void;
    send_orders: () => void;
    reset: () => void;
    unit_queue: UnitQueue[] | [];
}
