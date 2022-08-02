export default interface HealthMeterProps {
    is_enemy: boolean;

    name: string;

    current_health: number|undefined;

    max_health: number|undefined;
}
