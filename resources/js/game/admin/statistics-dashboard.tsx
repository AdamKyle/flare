import { createRoot } from 'react-dom/client';
import React from "react";
import Dashboard from "./statistics/dashboard";

const administratorStatistics = document.getElementById('administrator-statistics');

if (administratorStatistics !== null) {

    const root = createRoot(administratorStatistics);

    root.render(<Dashboard />);
}
