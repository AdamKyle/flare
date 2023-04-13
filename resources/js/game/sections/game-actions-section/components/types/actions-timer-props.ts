
type TimeLeft = (timeLeft: number) => void;

export default interface ActionsTimerProps {
    attack_time_out: number;

    crafting_time_out: number;

    update_attack_timer: TimeLeft;

    update_crafting_timer: TimeLeft;
}
