import AttachedGems from "../deffinitions/attached-gems";
import WhenReplacing from "../deffinitions/when-replacing";

export default interface ReplacingAGemProps<T> {
    when_replacing: WhenReplacing[]|[];
    gems_you_have: AttachedGems[]|[];
    action_disabled: boolean;
    original_atonement: any[]|[];
    if_replacing: any[]|[];
    update_parent: (value: T, property: string) => void;
    selected_gem: number;
    selected_item: number;
    manage_parent_modal: () => void;
    character_id: number;
}
