export default interface GemBagSlotDetails {
    id: number;
    name: string;
    primary_atonement_amount: number;
    primary_atonement_name: string;
    secondary_atonement_amount: number;
    secondary_atonement_name: string;
    tertiary_atonement_amount: number;
    tertiary_atonement_name: string;
    tier: number;
    weak_against: string;
    strong_against: string;
    element_atoned_to: string;
    element_atoned_to_amount: number;
}
