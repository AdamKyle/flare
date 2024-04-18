import Items from "../actions/deffinitions/items";
import Gems from "../actions/deffinitions/gems";

export default interface SeerCampState {
    items: Items[]|[],
    gems: Gems[]|[],
    seer_actions: {label: string, value: string}[],
    socket_cost: number,
    attach_gem: number,
    remove_gem: number,
    item_selected: number,
    gem_selected: number,
    is_loading: boolean,
    trading_with_seer: boolean,
    error_message: string|null,
    success_message: string|null,
    selected_seer_action: string|null,
    manage_gems_on_item: boolean;
    manage_remove_gem: boolean;
}
