export default interface RequiredListItemProps {
    requirement: string | number | JSX.Element | null;
    label: string | JSX.Element;
    isFinished: boolean;
}
