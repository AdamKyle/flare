export default interface GoblinCoinBankModalState {
    amount_to_withdraw: string | number;

    amount_to_deposit: string | number;

    cost_to_deposit: string | number;

    gold_gained: number;

    error_message: string;

    success_message: string;

    loading: boolean;
}
