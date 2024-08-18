export default interface CloseSurveyProps {
    is_open: boolean;
    handle_close: () => void;
    confirm_close: () => void;
}
