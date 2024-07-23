import AffixDefinition from "../deffinitions/affix-definition";

export default interface ItemDetailsState {
    affix: AffixDefinition | null;
    view_affix: boolean;
    holy_stacks: any | null;
    view_stacks: boolean;
    view_sockets: boolean;
}
