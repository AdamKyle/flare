import { HealthBarType } from "../enums/health-bar-type";

export default interface HealthBarProps {
    name: string;
    current_health: number;
    max_health: number;
    health_bar_type: HealthBarType;
}
