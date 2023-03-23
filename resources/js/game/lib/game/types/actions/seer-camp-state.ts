import ItemsForSeer from "./components/seer-camp/items-for-seer";
import GemsForSeer from "./components/seer-camp/gems-for-seer";

export default interface SeerCampState {
    items: ItemsForSeer[]|[],
    gems: GemsForSeer[]|[],
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
    view_gem: boolean,
    manage_gems_on_item: boolean;
}
