import { ReactNode } from "react";

export default interface FloatingCardProps {
    title: string;
    close_action: () => void;
    children: ReactNode | ReactNode[];
}
