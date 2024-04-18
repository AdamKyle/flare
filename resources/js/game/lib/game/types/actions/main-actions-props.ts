import CharacterStatusType from "../../character/character-status-type";
import MonsterType from "./monster/monster-type";
import {CharacterType} from "../../character/character-type";
import {CraftingOptions} from "../../../../components/crafting/base-components/types/crafting-type-options";

export default interface MainActionsProps {

    character_statuses: CharacterStatusType | null;

    monsters: MonsterType[];

    remove_crafting_type: () => void;

    crafting_type: CraftingOptions;

    character: CharacterType | null;

    cannot_craft: boolean;
}
