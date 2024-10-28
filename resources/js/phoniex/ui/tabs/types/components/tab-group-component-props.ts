export default interface TabGroupComponentProps {
    selected_index: number;
    handle_change: (index: number) => void;
    children: React.ReactNode;
}
