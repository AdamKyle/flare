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

export default function useFactionLoyaltyLiveRefresh(refresh: () => void) {
    useEffect(() => {
        const channelName = "admin-monitoring-faction-loyalty";
        const channel = window.Echo?.private(channelName);
        channel?.listen(".faction.loyalty.monitoring.updated", refresh);

        return () => {
            window.Echo?.leave(channelName);
        };
    }, [refresh]);
}
