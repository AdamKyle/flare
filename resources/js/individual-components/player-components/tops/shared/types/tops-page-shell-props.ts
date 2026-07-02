import React from "react";

export default interface TopsPageShellProps {
    title: string;
    description: string;
    resetDescription?: string;
    children: React.ReactNode;
}
