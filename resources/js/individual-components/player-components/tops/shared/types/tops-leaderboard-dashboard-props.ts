import React from "react";
import TopsApiResponse from "./tops-api-response";
import TopsDisplayField from "./tops-display-field";

export default interface TopsLeaderboardDashboardProps {
    title: string;
    description: string;
    resetDescription: string;
    response: TopsApiResponse | null;
    primaryMetric: TopsDisplayField;
    supportingMetrics: TopsDisplayField[];
    tableFields: TopsDisplayField[];
    period: string;
    metric: string;
    search: string;
    onlineOnly?: boolean;
    showOnlineOnly?: boolean;
    loading: boolean;
    errorMessage: string | null;
    minTableWidth?: string;
    onPeriodChange: (value: string) => void;
    onMetricChange: (value: string) => void;
    onSearch: (value: string) => void;
    onOnlineOnly?: (value: boolean) => void;
    children?: React.ReactNode;
}
