export default interface CharacterInventoryTabsProps {

    character_id: number;

    is_dead: boolean;

    user_id: number;

    is_automation_running: boolean;

    finished_loading: boolean;

    update_disable_tabs?: () => void;

    view_port: number;
}
