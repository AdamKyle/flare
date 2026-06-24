import React from "react";
import { createRoot } from "react-dom/client";
import DelveDashboard from "./delve-monitoring/components/delve-dashboard";

const el = document.getElementById("delve-monitoring");

if (el) {
    createRoot(el).render(<DelveDashboard />);
}
