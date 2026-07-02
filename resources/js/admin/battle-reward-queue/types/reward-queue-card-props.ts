import { ReactNode } from "react";

export default interface RewardQueueCardProps {
    title?: string;
    description?: string;
    children: ReactNode;
    className?: string;
}
