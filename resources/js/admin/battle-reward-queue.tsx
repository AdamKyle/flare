import React from "react";
import { createRoot } from "react-dom/client";
import RewardQueueDashboard from "./battle-reward-queue/components/reward-queue-dashboard";

const rewardQueueElement = document.getElementById("character-reward-queue");

if (rewardQueueElement) {
    createRoot(rewardQueueElement).render(<RewardQueueDashboard />);
}
