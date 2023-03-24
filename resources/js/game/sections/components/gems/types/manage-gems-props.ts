
export default interface ManageGemsProps<T> {
    character_id: number;
    selected_item: number
    selected_gem: number;
    cost:number;
    manage_model: () => void;
    update_parent: (value: T, property: string) => void;
    is_open: boolean;
}
