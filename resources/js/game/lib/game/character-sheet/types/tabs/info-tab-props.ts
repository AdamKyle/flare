import CharacterSheetProps from "../character-sheet-props";

export default interface InfoTabProps extends CharacterSheetProps {
    manage_addition_data: () => void;
}
