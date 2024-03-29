import MercenaryType from "./types/mercenary-type";
import MercenariesToBuyType from "./types/mercenaries-to-buy-type";
import MercenaryXpBuffs from "./types/mercenary-xp-buffs";

export default interface CharacterMercenariesState {

    loading: boolean;

    mercs: MercenaryType[]|[];

    mercs_to_buy: MercenariesToBuyType[]|[];

    merc_xp_buffs: MercenaryXpBuffs[]|[];

    merc_selected: string|null;

    buying_merc: boolean;

    error_message: string|null;

    success_message: string|null;

    reincarnate_error_message: string|null;

    reincarnate_success_message: string|null;

    reincarnating: boolean;

    dark_tables: boolean;

    show_merc_details: boolean;

    merc_for_show: MercenaryType|null;

    buying_buff: boolean;
}
