import KingdomModalProps from "./kingdom-modal-props";

export default interface OtherKingdomModalProps extends KingdomModalProps {
    is_enemy_kingdom: boolean;

    can_move: boolean;
}
