import GoldBarData from "../deffinitions/gold-bar-data";

export default interface GoldBarManagementState {
    loading: boolean;
    success_message: string | null;
    error_message: string | null;
    gold_bar_data: GoldBarData | null;
    amount_of_gold_bars_to_buy: number;
    amount_of_gold_bars_to_sell: number;
    max_gold_bars_allowed: number;
}
