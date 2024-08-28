import { LogInStats } from "../deffinitions/login-stats";

export interface LoginDurationChartState {
    error_message: string;
    chart_data: LogInStats[];
    loading: boolean;
}
