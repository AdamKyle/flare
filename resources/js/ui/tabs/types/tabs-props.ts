export default interface TabsProps {
    tabs: string[];
    icons: string[];
    onChange?: (index: number) => void;
    children: React.ReactNode[] | [];
}
