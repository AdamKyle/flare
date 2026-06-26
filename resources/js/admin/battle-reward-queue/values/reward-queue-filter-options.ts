export const rewardQueueFilterOptions = {
    status: [
        { value: "pending", label: "Pending" },
        { value: "processing", label: "Processing" },
        { value: "completed", label: "Completed" },
        { value: "failed", label: "Failed" },
    ],
    priority: [
        { value: "first", label: "First" },
        { value: "second", label: "Second" },
        { value: "third", label: "Third" },
    ],
    source_type: [
        { value: "quest", label: "Quest" },
        { value: "guide_quest", label: "Guide Quest" },
        { value: "raid_quest", label: "Raid Quest" },
        { value: "battle", label: "Battle" },
        { value: "exploration", label: "Exploration" },
        { value: "automation", label: "Automation" },
        { value: "future", label: "Future" },
    ],
};
