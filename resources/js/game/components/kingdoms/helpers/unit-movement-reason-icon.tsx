import UnitMovementDetails from "../queues/deffinitions/unit-movement-details";
import React, { ReactNode } from "react";

export const unitMovementReasonIcon = (
    unitMovementData: UnitMovementDetails,
): ReactNode => {
    if (unitMovementData.is_moving) {
        return (
            <i className="ra ra-heavy-shield text-blue-500 dark:text-blue-400"></i>
        );
    }

    if (unitMovementData.is_attacking) {
        return <i className="ra ra-axe text-red-500 dark:text-red-400"></i>;
    }

    if (unitMovementData.is_recalled || unitMovementData.is_returning) {
        return (
            <i className="fas fa-exchange-alt text-orange-500 dark:text-orange-300"></i>
        );
    }
};
