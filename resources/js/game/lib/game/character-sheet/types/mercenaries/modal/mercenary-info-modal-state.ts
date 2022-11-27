export default interface MercenaryInfoModalState {

    selected_buff: string|null;

    selected_buff_xp: number;

    selected_buff_cost: number;

    loading: boolean;

    error_message: string|null;

    success_message: string|null;
}
