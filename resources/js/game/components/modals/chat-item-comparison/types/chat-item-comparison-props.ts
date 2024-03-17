export interface ChatItemComparisonProps {
    is_open: boolean;
    manage_modal: () => void;
    character_id: number;
    slot_id: number;
    is_automation_running: boolean;
}
