export default interface InputProps {
  on_change: (value: string) => void;
  clearable?: boolean;
  place_holder?: string;
}
