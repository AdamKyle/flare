export default interface ManageTreasuryModalState {
    amount_to_withdraw: string | number;

    amount_to_deposit: string | number;

    loading: boolean;

    success_message: string;

    error_message: string;
}
