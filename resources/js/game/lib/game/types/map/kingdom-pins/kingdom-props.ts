export default interface KingdomProps {

    kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number}[] | null;

    character_id: number;
}
