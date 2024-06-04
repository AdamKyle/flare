import React from "react";
export var unitMovementReasonIcon = function (unitMovementData) {
    if (unitMovementData.is_moving) {
        return React.createElement("i", {
            className: "ra ra-heavy-shield text-blue-500 dark:text-blue-400",
        });
    }
    if (unitMovementData.is_attacking) {
        return React.createElement("i", {
            className: "ra ra-axe text-red-500 dark:text-red-400",
        });
    }
    if (unitMovementData.is_recalled || unitMovementData.is_returning) {
        return React.createElement("i", {
            className:
                "fas fa-exchange-alt text-orange-500 dark:text-orange-300",
        });
    }
    if (unitMovementData.resources_requested) {
        return React.createElement("i", {
            className:
                "fas fa-truck-loading text-orange-500 dark:text-orange-300",
        });
    }
};
//# sourceMappingURL=unit-movement-reason-icon.js.map
