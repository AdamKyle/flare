export default interface InfoSectionData {
    content: string | null | undefined;
    content_image_path?: File | null;
    live_wire_component: string | null;
    item_table_type?: string | null | undefined;
    order: number;
    is_new_section?: boolean;
    insert_at_index?: number;
    new_order?: number;
}
