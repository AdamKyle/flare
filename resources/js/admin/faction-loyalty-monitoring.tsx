import React from "react";
import { createRoot } from "react-dom/client";
import FactionLoyaltyDashboard from "./faction-loyalty-monitoring/components/faction-loyalty-dashboard";

const el = document.getElementById("faction-loyalty-monitoring");

if (el) {
    createRoot(el).render(<FactionLoyaltyDashboard />);
}
