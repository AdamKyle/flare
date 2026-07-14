export default interface GuideQuestProps {
    is_open: boolean;
    manage_modal: () => void;
    user_id: number;
    view_port: number;
    read_only?: boolean;
    preloaded_guide_quest?: any;
    viewer_has_access?: boolean;
}
