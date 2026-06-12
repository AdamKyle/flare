import KingdomDamageSlotItems from "../../deffinitions/kingdom-damage-slot-items";
import KingdomReinforcementType from "../kingdom-reinforcement-type";
import SelectedUnitsToCallType from "../selected-units-to-call-type";

export default interface AttackKingdomModalState {
    loading: boolean;

    fetching_data: boolean;

    items_to_use: KingdomDamageSlotItems[] | [];

    kingdoms: KingdomReinforcementType[] | [];

    error_message: string;

    success_message: string;

    selected_kingdoms: number[] | [];

    selected_units: SelectedUnitsToCallType[] | [];

    selected_items: number[] | [];

    raw_item_damage: number;

    damage_after_defence: number;

    final_damage: number;

    show_help_modal: boolean;

    help_type: string;
}
