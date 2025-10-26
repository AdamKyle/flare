export default interface UseTooltipPlacementDefinition {
  horizontal: 'left' | 'right';
  vertical: 'above' | 'below';
  place: () => void;
}
