export default interface TimerProgressBarProps {
    time_remaining: number;

    time_out_label: string;

    timer_started_at?: number;

    update_time_remaining?: (timeLeft: number) => void;

    additional_css?: string;

    useSmallTimer?: boolean;
}
