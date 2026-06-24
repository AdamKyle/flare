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

export default function useRewardQueueLiveRefresh(refresh: () => void) {
    useEffect(() => {
        const channelName = "admin-character-reward-queue";
        const channel = window.Echo?.private(channelName);
        channel?.listen(".battle.reward.queue.updated", refresh);

        return () => {
            window.Echo?.leave(channelName);
        };
    }, [refresh]);
}
