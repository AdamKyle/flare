import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileSpecialtyDetailModalProps {
    specialty: Record<string, TopsValue>;
    onClose: () => void;
}
