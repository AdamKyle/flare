import React from "react";
import { createRoot } from "react-dom/client";
import LogsDashboard from "./logs-dashboard/components/logs-dashboard";

const el = document.getElementById("logs-dashboard");

if (el) {
    createRoot(el).render(<LogsDashboard />);
}
