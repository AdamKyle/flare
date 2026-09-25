export default interface MarketPriceFieldProps {
  id: string;
  label: string;
  value: string;
  error: string | null;
  disabled: boolean;
  on_change: (value: string) => void;
}
