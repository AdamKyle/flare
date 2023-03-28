import AttachedGems from "../deffinitions/attached-gems";
import GemBagSlotDetails from "../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";
import WhenReplacing from "../deffinitions/when-replacing";

type ManageGemsTabs = {key: string, name: string}[];

export default interface ManageGemsState {
    loading: boolean;
    gem_to_attach: GemBagSlotDetails|null;
    when_replacing: WhenReplacing[]|[];
    has_gems_on_item: boolean;
    attached_gems: AttachedGems[]|[],
    socket_data: object;
    tabs: ManageGemsTabs,
    trading_with_seer: boolean,
    error_message: string|null,
    if_replacing_atonements: any[]|[],
    original_atonement: any,
}
