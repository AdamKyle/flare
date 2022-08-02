import ActionsState from "./actions-state";

export default interface SmallActionsState extends ActionsState {
    selected_action: string | null;

    automation_time_out: number;

    movement_time_left: number;
}
