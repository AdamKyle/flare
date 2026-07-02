import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileStatBreakdownModalProps {
    stat: Record<string, TopsValue>;
    onClose: () => void;
}
