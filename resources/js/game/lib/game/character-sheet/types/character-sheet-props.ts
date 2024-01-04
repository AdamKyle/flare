import {CharacterType} from "../../character/character-type";
import {FameTasks} from "../../../../sections/faction-loyalty/deffinitions/faction-loaylaty";

export default interface CharacterSheetProps {

    character: CharacterType | null;

    finished_loading: boolean;

    view_port?: number;

    update_disable_tabs?: () => void;

    update_pledge_tab?: (canSee: boolean) => void;

    update_faction_action_tasks?: (fameTasks: FameTasks[] | null, factionId?: number) => void;
}
