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

export default function useDelveMonitoringLiveRefresh(refresh: () => void) {
    useEffect(() => {
        const channelName = "admin-monitoring-delve";
        const channel = window.Echo?.private(channelName);
        channel?.listen(".delve.monitoring.updated", refresh);

        return () => {
            window.Echo?.leave(channelName);
        };
    }, [refresh]);
}
