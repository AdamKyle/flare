import Gems from "../deffinitions/gems";
import Items from "../deffinitions/items";

export default interface AddGemsToItemProps<T> {
    gem_selected: number;
    item_selected: number;
    gems: Gems[] | [];
    items: Items[] | [];
    update_parent: (value: T, property: string) => void;
}
