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

export default function useExplorationLiveRefresh(refresh: () => void) {
    useEffect(() => {
        const channelName = "admin-monitoring-exploration";
        const channel = window.Echo?.private(channelName);
        channel?.listen(".exploration.monitoring.updated", refresh);

        return () => {
            window.Echo?.leave(channelName);
        };
    }, [refresh]);
}
