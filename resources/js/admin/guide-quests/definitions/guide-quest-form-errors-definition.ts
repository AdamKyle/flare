import GuideQuestFormStateDefinition from './guide-quest-form-state-definition';

export type GuideQuestBatchItemErrorDefinition = Partial<
  Record<'source' | 'item_id' | 'amount' | 'must_be_enchanted', string>
>;

type GuideQuestFieldErrorsDefinition = Partial<
  Record<keyof GuideQuestFormStateDefinition, string>
>;

export default interface GuideQuestFormErrorsDefinition extends GuideQuestFieldErrorsDefinition {
  required_batch_crafted_items_rows?: Record<
    number,
    GuideQuestBatchItemErrorDefinition
  >;
}
