import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';
import GuideQuestFormStateDefinition from '../definitions/guide-quest-form-state-definition';

const CONTENT_FIELDS = [
  'intro_text',
  'desktop_instructions',
  'mobile_instructions',
] as const;

const appendContentBlocks = (
  formData: FormData,
  field: (typeof CONTENT_FIELDS)[number],
  blocks: GuideQuestContentBlockDefinition[]
): void => {
  if (blocks.length === 0) {
    formData.append(`content[${field}]`, '');
    return;
  }

  blocks.forEach((block, index) => {
    formData.append(`content[${field}][${index}][id]`, block.id);
    formData.append(`content[${field}][${index}][content]`, block.content);
    formData.append(
      `content[${field}][${index}][image_url]`,
      block.image_url ?? ''
    );
  });
};

export const buildGuideQuestFormData = (
  state: GuideQuestFormStateDefinition
): FormData => {
  const formData = new FormData();

  Object.entries(state).forEach(([field, value]) => {
    if (field === 'walkthrough_steps') {
      return;
    }

    if (CONTENT_FIELDS.includes(field as (typeof CONTENT_FIELDS)[number])) {
      appendContentBlocks(
        formData,
        field as (typeof CONTENT_FIELDS)[number],
        value as GuideQuestContentBlockDefinition[]
      );
      return;
    }

    if (field === 'required_batch_crafted_items') {
      const rows =
        value as GuideQuestFormStateDefinition['required_batch_crafted_items'];
      rows.forEach((row, index) => {
        Object.entries(row).forEach(([rowField, rowValue]) => {
          formData.append(
            `content[required_batch_crafted_items][${index}][${rowField}]`,
            typeof rowValue === 'boolean'
              ? rowValue
                ? '1'
                : '0'
              : String(rowValue ?? '')
          );
        });
      });
      return;
    }

    if (typeof value === 'boolean') {
      formData.append(`content[${field}]`, value ? '1' : '0');
      return;
    }

    formData.append(`content[${field}]`, String(value ?? ''));
  });

  return formData;
};

export const guideQuestFormHasImages = (
  state: GuideQuestFormStateDefinition
): boolean =>
  CONTENT_FIELDS.some((field) =>
    state[field].some((block) => block.image_url !== null)
  );
