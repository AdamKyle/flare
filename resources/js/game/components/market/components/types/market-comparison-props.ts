export default interface MarketComparisonProps {
  listing_id: number;
  listing_name: string;
  on_close: () => void;
  on_purchased: (message: string) => void;
}
