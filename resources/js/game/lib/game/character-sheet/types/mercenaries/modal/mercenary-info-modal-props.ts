import MercenaryType from "../types/mercenary-type";
import {CharacterType} from "../../../../character/character-type";
import MercenaryXpBuffs from "../types/mercenary-xp-buffs";

export default interface MercenaryInfoModalProps {

    mercenary: MercenaryType|null;

    is_open: boolean;

    handle_close: () => void;

    character: CharacterType;

    reincarnating: boolean;

    error_message: string|null;

    reincarnate: (mercId: number) => void;

    xp_buffs: MercenaryXpBuffs[]|[],

    purchase_buff: (mercId: number, buffType: string) => void

    buying_buff: boolean;
}
