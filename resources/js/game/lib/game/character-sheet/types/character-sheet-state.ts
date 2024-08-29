export default interface CharacterSheetState {
    show_inventory_section: boolean;
    show_skills_section: boolean;
    show_top_section: boolean;
    show_additional_character_data: boolean;
    reincarnating: boolean;
    success_message: string | null;
    error_message: string | null;
    reincarnation_check: boolean;
    is_showing_kingdom_tree: boolean;
}
