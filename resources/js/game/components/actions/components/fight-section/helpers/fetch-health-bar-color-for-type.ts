import { match } from "ts-pattern";

import { HealthBarType } from "../enums/health-bar-type";

export const fetchHealthBarColorForType = (
    healthBarType: HealthBarType,
): string => {
    return match(healthBarType)
        .with(HealthBarType.ENEMY, () => "bg-rose-600 dark:bg-rose-500")
        .with(HealthBarType.PLAYER, () => "bg-emerald-600 dark:bg-emerald-500")
        .otherwise(() => "");
};
