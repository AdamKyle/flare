import Items from "../deffinitions/items";

export default interface ManageItemSocketsProps<T> {
    items: Items[] | [];
    update_parent: (value: T, property: string) => void;
}
