import BaseGemDetails from '../../../../../api-definitions/items/base-gem-details';

export default interface GemDetailsProps {
  gem: BaseGemDetails;
  on_close: () => void;
}
