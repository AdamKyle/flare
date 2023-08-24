import IconType from "../../types/icon-type";

export default interface SpinSectionProps {
    icons: IconType[] | [];
    is_small: boolean;
    spin_action: () => void;
    spinning_indexes: number[]|[];
}
