export default interface UseTooltipDisclosureDefinition {
  open: boolean;
  openTip: () => void;
  closeTip: () => void;
  toggleTip: () => void;
  setOpen: (next: boolean) => void;
}
