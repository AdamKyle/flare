export default interface TimerProgressBarProps {
    time_remaining: number;

    time_out_label: string;

    update_time_remaining?: (timeLeft: number) => void;
}
