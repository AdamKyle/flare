export default interface LocationGemRangeFieldProps {
  id: string;
  label: string;
  value: string;
  error?: string;
  on_change: (value: string) => void;
}
