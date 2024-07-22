import KingdomDetails from "../../../kingdom-details";

export default interface KingdomDetailsProps {
    kingdom_id: number;

    character_id: number;

    update_loading: (kingdomDetails: KingdomDetails) => void;

    show_top_section: boolean;

    allow_purchase: boolean;

    can_attack_kingdom: boolean;

    update_action_in_progress: () => void;

    close_modal: () => void;
}
