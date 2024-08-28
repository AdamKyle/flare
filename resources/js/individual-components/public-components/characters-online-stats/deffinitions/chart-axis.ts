import { AxisOptions } from "react-charts";
import { LogInStats } from "./login-stats";

export const primaryAxis: AxisOptions<LogInStats> = {
    getValue: (datum: LogInStats) => datum.date,
};

export const secondaryAxes: AxisOptions<LogInStats>[] = [
    {
        getValue: (datum: LogInStats) => datum.login_count,
        elementType: "line",
    },
];
