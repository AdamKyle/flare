import React from "react";

export default interface UnitTopLevelActionsProps {
    search_term: string;
    handle_search_change: (event: React.ChangeEvent<HTMLInputElement>) => void;
    send_orders: () => void;
    reset_queue: () => void;
    reset_filters: () => void;
}
