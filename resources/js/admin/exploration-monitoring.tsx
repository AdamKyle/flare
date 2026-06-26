import React from "react";
import { createRoot } from "react-dom/client";
import ExplorationDashboard from "./exploration-monitoring/components/exploration-dashboard";

const element = document.getElementById("exploration-monitoring");

if (element) {
    createRoot(element).render(<ExplorationDashboard />);
}
