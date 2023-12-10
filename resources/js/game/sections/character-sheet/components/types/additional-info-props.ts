import CharacterSheetProps from "../../../../lib/game/character-sheet/types/character-sheet-props";
import {CharacterType} from "../../../../lib/game/character/character-type";

export interface AdditionalInfoProps extends CharacterSheetProps {

    is_open: boolean;

    manage_modal: () => void;

    title: string;

    character: CharacterType | null

    when_tab_changes?: (tabIndex: number, tabs: {key: string, name: string}[]) => void
}

