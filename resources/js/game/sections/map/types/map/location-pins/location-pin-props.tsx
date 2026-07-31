export default interface LocationPinProps {
    location: { id: number; y: number; x: number };

    openLocationDetails: Function;

    pin_class: string;

    aria_label?: string;

    onMouseEnter?: () => void;

    onMouseLeave?: () => void;
}
