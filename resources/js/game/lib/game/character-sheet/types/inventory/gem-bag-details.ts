export default interface GemBagDetails {
    id: number;
    name: string;
    amount: number;
    tier: number;
    weak_against: string;
    strong_against: string;
    element_atoned_to: string;
    element_atoned_to_amount: number;
}
