export default interface NpcKingdomPinProps {
    kingdom: {
        id: number;
        x_position: number;
        y_position: number;
        npc_owned: boolean;
    };

    color: string;

    open_kingdom_modal: (kingdomId: number) => void;
}
