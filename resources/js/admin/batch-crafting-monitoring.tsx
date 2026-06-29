import React from "react";
import { createRoot } from "react-dom/client";
import BatchCraftingDashboard from "./batch-crafting-monitoring/components/batch-crafting-dashboard";

const el = document.getElementById("batch-crafting-monitoring");

if (el) {
    createRoot(el).render(<BatchCraftingDashboard />);
}
