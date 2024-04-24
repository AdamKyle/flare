import BuildingDetails from "../buildings/deffinitions/building-details";

export default interface KingdomDetailsState {
    show_change_name_modal: boolean;

    show_buy_pop_modal: boolean;

    show_goblin_bank: boolean;

    show_abandon_kingdom: boolean;

    show_manage_treasury: boolean;

    show_call_for_reinforcements: boolean;

    show_smelter: boolean;

    show_specialty_help: boolean;

    goblin_bank_building: BuildingDetails | null;

    show_resource_transfer: boolean;
}
