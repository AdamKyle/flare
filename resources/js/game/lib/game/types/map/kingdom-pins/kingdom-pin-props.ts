export default interface KingdomPinProps {

    kingdom: {id: number, x_position: number, y_position: number, color: string};

    open_kingdom_modal: (kingdomId: number) => void;
}
