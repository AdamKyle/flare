import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileItemDetailModalProps {
    item: Record<string, TopsValue>;
    onClose: () => void;
}
