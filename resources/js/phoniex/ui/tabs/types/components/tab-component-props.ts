export default interface TabComponentProps {
    index: number;
    selected: boolean;
    icon: string;
    on_click: (index: number) => void;
    children: React.ReactNode;
}
