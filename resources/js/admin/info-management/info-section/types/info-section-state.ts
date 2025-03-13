export default interface InfoSectionState {
    content: string | null | undefined;
    selected_live_wire_component: string | null;
    selected_item_table_type: string | null | undefined;
    image_to_upload: File | null;
    order: number;
    loading: boolean;
}
