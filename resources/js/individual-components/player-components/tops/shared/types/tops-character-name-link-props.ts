export default interface TopsCharacterNameLinkProps {
    characterId: number | null;
    characterName: string | null;
    tableLink?: boolean;
    variant?: "default" | "table";
    rank?: number;
}
