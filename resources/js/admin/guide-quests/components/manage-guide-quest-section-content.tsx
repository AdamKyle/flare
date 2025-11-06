import { debounce } from 'lodash';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { v4 as uuidv4 } from 'uuid';

import UseFetchGuideQuestsDefinition from './types/manage-guide-quest-section-content-props';
import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ImageUploader from 'ui/file-upload/image-uploader';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const ManageGuideQuestSectionContent = ({
  step,
  on_update_content,
}: UseFetchGuideQuestsDefinition) => {
  const [contentBlock, setContentBlock] = useState('');
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [blockId] = useState(() => uuidv4());

  const handleFileChange = useCallback((file: File | null) => {
    setImageFile(file);
  }, []);

  const handleMarkdownChange = useCallback((markdown: string) => {
    setContentBlock(markdown);
  }, []);

  const emitDebounced = useMemo(
    () =>
      debounce(
        (payload: { intro_text: GuideQuestContentBlockDefinition[] }) => {
          on_update_content(step, payload);
        },
        300
      ),
    [on_update_content, step]
  );

  useEffect(() => {
    const block: GuideQuestContentBlockDefinition = {
      id: blockId,
      content: contentBlock ?? '',
      image_url: imageFile ?? null,
    };

    emitDebounced({ intro_text: [block] });

    return () => {
      emitDebounced.cancel();
    };
  }, [contentBlock, imageFile, blockId, emitDebounced]);

  const handleAddAnotherSection = useCallback(() => {
    return;
  }, []);

  return (
    <div className="container rounded-md border-1 border-gray-500 p-2 pb-4">
      <div className="grid grid-cols-2 gap-4">
        <ImageUploader onFileChange={handleFileChange} />
        <MarkDownEditor on_value_change={handleMarkdownChange} />
      </div>

      <div className="mt-4 w-full text-right">
        <Button
          on_click={handleAddAnotherSection}
          label="Add Another Section"
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label="Remove Section"
          variant={ButtonVariant.DANGER}
          additional_css="ml-2"
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestSectionContent;
