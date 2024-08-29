export default interface DangerButtonProps<T extends unknown[] = []> {
    on_click: (...args: T) => void;
    label: string;
}
