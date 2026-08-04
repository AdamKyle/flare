export default interface UseEnchantingIntroductionDefinition {
  introductionAcknowledged: boolean;
  acknowledgeIntroduction: (hidePermanently: boolean) => void;
}
