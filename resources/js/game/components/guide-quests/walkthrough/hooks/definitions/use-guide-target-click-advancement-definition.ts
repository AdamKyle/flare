export default interface UseGuideTargetClickAdvancementDefinition {
  bindTargetClick: (target: HTMLElement, onClick: () => void) => void;
  unbindTargetClick: () => void;
}
