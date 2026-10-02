import PresentGuideStepParams from '../../types/present-guide-step-params';

export default interface UseGuideWalkthroughDriverDefinition {
  presentDriverStep: (params: PresentGuideStepParams) => void;
  destroyDriver: () => void;
}
