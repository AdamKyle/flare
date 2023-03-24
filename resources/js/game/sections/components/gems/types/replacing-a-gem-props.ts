import AttachedGems from "../deffinitions/attached-gems";
import WhenReplacing from "../deffinitions/when-replacing";

export default interface ReplacingAGemProps {
    when_replacing: WhenReplacing[]|[];
    gems_you_have: AttachedGems[]|[];
    action_disabled: boolean;
}
