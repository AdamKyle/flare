import TopsPeriod from "./tops-period";

export default interface TopsMetricSelectorProps {
    metrics: TopsPeriod[];
    value: string;
    onChange: (value: string) => void;
}
