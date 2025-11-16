import { debounce } from 'lodash';
import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { v4 as uuidv4 } from 'uuid';

import ManageGuideQuestsTextContentProps from './types/manage-guide-quest-test-content-props';
import GuideQuestDefinition, {
  GuideQuestContentBlockDefinition,
} from '../api/definitions/guide-quest-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ImageUploader from 'ui/file-upload/image-uploader';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const ManageGuideQuestsTextContent = ({
  step,
  field_key,
  on_update_content,
  initial_content,
}: ManageGuideQuestsTextContentProps) => {
  const sectionBlocks = useMemo<
    GuideQuestContentBlockDefinition[] | null
  >(() => {
    if (!initial_content) {
      return null;
    }

    const value = initial_content[field_key as keyof GuideQuestDefinition];

    if (!Array.isArray(value)) {
      return null;
    }

    return value as GuideQuestContentBlockDefinition[];
  }, [initial_content, field_key]);

  const seedBlocks = useMemo<GuideQuestContentBlockDefinition[]>(() => {
    if (!sectionBlocks || sectionBlocks.length === 0) {
      return [{ id: uuidv4(), content: '', image_url: null }];
    }

    return sectionBlocks.map((contentBlock) => ({
      id: contentBlock.id || uuidv4(),
      content: contentBlock.content || '',
      image_url: contentBlock.image_url ?? null,
    }));
  }, [sectionBlocks]);

  const [contentBlocks, setContentBlocks] =
    useState<GuideQuestContentBlockDefinition[]>(seedBlocks);

  const latestRefs = useRef({
    on_update_content,
    step,
    field_key,
  });

  const debouncedEmitRef = useRef(
    debounce((currentBlocks: GuideQuestContentBlockDefinition[]) => {
      const {
        on_update_content: onUpdate,
        step: currentStep,
        field_key: fieldKey,
      } = latestRefs.current;

      const payload = {
        [fieldKey]: currentBlocks,
      } as unknown as Partial<
        Record<typeof field_key, GuideQuestContentBlockDefinition[]>
      >;

      onUpdate(currentStep, payload);
    }, 300)
  );

  const lastSeedSignatureRef = useRef<string>('');

  const seedSignature = useMemo(() => {
    return JSON.stringify(
      seedBlocks.map((block) => ({
        id: block.id,
        content: block.content,
        image_url:
          typeof block.image_url === 'string'
            ? block.image_url
            : block.image_url
              ? 'FILE'
              : null,
      }))
    );
  }, [seedBlocks]);

  useEffect(() => {
    latestRefs.current.on_update_content = on_update_content;
    latestRefs.current.step = step;
    latestRefs.current.field_key = field_key;

    const debounced = debouncedEmitRef.current;

    if (lastSeedSignatureRef.current !== seedSignature) {
      setContentBlocks(seedBlocks);
      lastSeedSignatureRef.current = seedSignature;
    }

    if (contentBlocks.length > 0) {
      debounced(contentBlocks);
    }

    return () => {
      debounced.cancel();
    };
  }, [
    on_update_content,
    step,
    field_key,
    seedSignature,
    seedBlocks,
    contentBlocks,
  ]);

  const handleMarkdownChange = useCallback((id: string, markdown: string) => {
    setContentBlocks((previousBlocks) => {
      return previousBlocks.map((block) =>
        block.id === id ? { ...block, content: markdown ?? '' } : block
      );
    });
  }, []);

  const handleFileChange = useCallback((id: string, file: File | null) => {
    setContentBlocks((previousBlocks) => {
      return previousBlocks.map((block) =>
        block.id === id ? { ...block, image_url: file ?? null } : block
      );
    });
  }, []);

  const handleClearImage = useCallback((id: string) => {
    setContentBlocks((previousBlocks) => {
      return previousBlocks.map((block) =>
        block.id === id ? { ...block, image_url: null } : block
      );
    });
  }, []);

  const handleAddSection = useCallback(() => {
    setContentBlocks((previousBlocks) => {
      return [
        ...previousBlocks,
        { id: uuidv4(), content: '', image_url: null },
      ];
    });
  }, []);

  const handleRemoveSection = useCallback((id: string) => {
    setContentBlocks((previousBlocks) => {
      return previousBlocks.filter((block) => block.id !== id);
    });
  }, []);

  const renderRemoveButton = (canRemove: boolean, id: string) => {
    if (!canRemove) {
      return null;
    }

    return (
      <Button
        on_click={() => handleRemoveSection(id)}
        label="Remove Section"
        variant={ButtonVariant.DANGER}
        additional_css="ml-2"
      />
    );
  };

  const renderRow = (
    block: GuideQuestContentBlockDefinition,
    canRemove: boolean
  ) => {
    const initialImageUrl =
      typeof block.image_url === 'string' && block.image_url.length > 0
        ? block.image_url
        : null;

    return (
      <div
        key={block.id}
        className="container rounded-md border-1 border-gray-500 p-2 pb-4"
      >
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <ImageUploader
            initialImageUrl={initialImageUrl}
            onFileChange={(file) => handleFileChange(block.id, file)}
            onDelete={() => handleClearImage(block.id)}
          />
          <MarkDownEditor
            id={block.id}
            initial_markdown={block.content || ''}
            on_value_change={(markdown) =>
              handleMarkdownChange(block.id, markdown)
            }
          />
        </div>

        <div className="mt-4 w-full text-right">
          <Button
            on_click={handleAddSection}
            label="Add Another Section"
            variant={ButtonVariant.PRIMARY}
          />
          {renderRemoveButton(canRemove, block.id)}
        </div>
      </div>
    );
  };

  const renderRows = () => {
    if (!contentBlocks.length) {
      return null;
    }

    const canRemove = contentBlocks.length > 1;

    return (
      <div className="space-y-4">
        {contentBlocks.map((block) => renderRow(block, canRemove))}
      </div>
    );
  };

  return <div className="w-full">{renderRows()}</div>;
};

export default ManageGuideQuestsTextContent;
