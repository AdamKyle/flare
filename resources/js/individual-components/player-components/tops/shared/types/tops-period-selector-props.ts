import TopsPeriod from "./tops-period";

export default interface TopsPeriodSelectorProps {
    periods: TopsPeriod[];
    value: string;
    onChange: (value: string) => void;
}
