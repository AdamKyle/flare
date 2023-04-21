export default interface ItemNameColorationTextProps {

    item: ItemForColorizationDefinition
}

export interface ItemForColorizationDefinition {
    name: string;

    type: string;

    affix_count: number;

    is_unique: boolean;

    holy_stacks_applied: number;

    is_mythic: boolean;
}
