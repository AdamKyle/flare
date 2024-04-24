export default interface SelectItemToCraftProps {
    set_item_to_craft: (data: any) => void;
    items: { label: string; value: string }[];
    default_item: { label: string; value: string };
}
