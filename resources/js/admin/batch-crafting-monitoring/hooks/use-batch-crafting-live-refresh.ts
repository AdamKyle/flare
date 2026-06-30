import { useEffect } from "react";

declare global {
    interface Window {
        Echo?: {
            private: (channel: string) => {
                listen: (event: string, callback: () => void) => void;
            };
            leave: (channel: string) => void;
        };
    }
}

export default function useBatchCraftingLiveRefresh(refresh: () => void) {
    useEffect(() => {
        const channelName = "admin-monitoring-batch-crafting";
        const channel = window.Echo?.private(channelName);

        let debounceTimer: ReturnType<typeof setTimeout> | undefined;

        const debounced = () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(refresh, 500);
        };

        channel?.listen(".batch-crafting.monitoring.updated", debounced);

        return () => {
            clearTimeout(debounceTimer);
            window.Echo?.leave(channelName);
        };
    }, [refresh]);
}
