export default interface LabyrinthOracleProps {
    character_id: number;
    remove_crafting: () => void;
    cannot_craft: boolean;
    user_id: number;
}
