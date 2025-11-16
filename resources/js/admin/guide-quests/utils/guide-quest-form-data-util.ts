import GuideQuestDefinition, {
  GuideQuestContentBlockDefinition,
} from '../api/definitions/guide-quest-definition';
import UseStoreGuideQuestRequestDefinition from '../api/hooks/definitions/use-store-guide-quest-request-definition';
import StepSliceDefinition from '../definitions/step-slice-definition';

/**
 * Append a single content-block array (intro/desktop/mobile) to FormData.
 * @param form FormData to mutate
 * @param key One of "intro_text" | "desktop_instructions" | "mobile_instructions"
 * @param blocks Array of content blocks
 * @return void
 */
export function appendContentBlocksToForm(
  form: FormData,
  key: 'intro_text' | 'desktop_instructions' | 'mobile_instructions',
  blocks: GuideQuestContentBlockDefinition[]
): void {
  blocks.forEach((block, index) => {
    form.append(`${key}[${index}][id]`, block?.id ?? '');
    form.append(`${key}[${index}][content]`, block?.content ?? '');

    if (block?.image_url instanceof File) {
      form.append(`${key}[${index}][image_url]`, block.image_url);
    } else if (typeof block?.image_url === 'string') {
      form.append(`${key}[${index}][image_url]`, block.image_url);
    } else {
      form.append(`${key}[${index}][image_url]`, '');
    }
  });
}

/**
 * Build a FormData snapshot from a step slice, handling block arrays and primitives.
 * @param slice Step slice object (maybe undefined)
 * @return FormData containing the serialized slice
 */
export function buildFormDataFromSlice(
  slice: StepSliceDefinition | undefined
): FormData {
  const form = new FormData();

  if (!slice) {
    return form;
  }

  Object.entries(slice).forEach(([key, value]) => {
    if (
      key === 'intro_text' ||
      key === 'desktop_instructions' ||
      key === 'mobile_instructions'
    ) {
      if (Array.isArray(value)) {
        appendContentBlocksToForm(
          form,
          key,
          value as GuideQuestContentBlockDefinition[]
        );
      }
      return;
    }

    if (value !== null && value !== undefined) {
      form.append(key, String(value as string | number | boolean));
    }
  });

  return form;
}

/**
 * Determine if a step slice contains any uploaded images in its block arrays.
 * @param slice Step slice object (maybe undefined)
 * @return true if any block has a non-null image_url; otherwise false
 */
export function hasImagesInSlice(
  slice: Partial<GuideQuestDefinition> | undefined
): boolean {
  if (!slice) {
    return false;
  }

  const keys: Array<keyof Partial<GuideQuestDefinition>> = [
    'intro_text',
    'desktop_instructions',
    'mobile_instructions',
  ];

  for (const k of keys) {
    const blocks = slice[k];

    if (
      Array.isArray(blocks) &&
      blocks.some((b) => (b as GuideQuestContentBlockDefinition)?.image_url)
    ) {
      return true;
    }
  }

  return false;
}

/**
 * Construct the request object expected by the submitter for a given step.
 * @param guideQuestId Numeric guide quest id (0 for create)
 * @param slice Step slice object (maybe undefined)
 * @return FormRequestDefinition containing form_data and has_images
 */
export function makeRequestObject(
  guideQuestId: number,
  slice: Partial<GuideQuestDefinition>
): UseStoreGuideQuestRequestDefinition {
  const form_data = buildFormDataFromSlice(slice);
  const has_images = hasImagesInSlice(slice);

  return {
    guide_quest_id: guideQuestId,
    content: form_data,
    has_image: has_images,
  };
}
