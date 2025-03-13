export default interface InfoSectionContent {
    content: string | null | undefined;
    live_wire_component: string | null;
    item_table_type?: string | null;
    content_image_path?: File | null;
    order: number;
}
