export default interface TopsProfileTabsProps {
    active: string;
    tabs: string[];
    onChange: (tab: string) => void;
}
