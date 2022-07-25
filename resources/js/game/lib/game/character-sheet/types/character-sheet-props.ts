import {CharacterType} from "../../character/character-type";

export default interface CharacterSheetProps {

    character: CharacterType | null;

    finished_loading: boolean;

    view_port?: number;

    update_disable_tabs?: () => void;
}
