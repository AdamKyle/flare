import React, { ReactNode } from 'react';

import ItemFormFieldsProps from '../../types/item-form-fields-props';
import {
  ITEM_ALCHEMY_TYPE_LABELS,
  isItemAlchemyType,
} from '../../enums/item-alchemy-type';
import {
  ITEM_CATALOG_TYPE_LABELS,
  isItemCatalogType,
} from '../../enums/item-catalog-type';
import {
  ITEM_DEFAULT_POSITION_LABELS,
  isItemDefaultPosition,
} from '../../enums/item-default-position';
import {
  ITEM_SPECIALTY_TYPE_LABELS,
  isItemSpecialtyType,
} from '../../enums/item-specialty-type';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const ItemBasicFields = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: ItemFormFieldsProps): ReactNode => {
  const typeItems: DropdownItem[] = formOptions.types.map((type) => ({
    label: ITEM_CATALOG_TYPE_LABELS[type],
    value: type,
  }));
  const positionItems: DropdownItem[] = formOptions.default_positions.map(
    (position) => ({
      label: ITEM_DEFAULT_POSITION_LABELS[position],
      value: position,
    })
  );
  const alchemyItems: DropdownItem[] = formOptions.alchemy_types.map(
    (type) => ({ label: ITEM_ALCHEMY_TYPE_LABELS[type], value: type })
  );
  const specialtyItems: DropdownItem[] = formOptions.specialty_types.map(
    (type) => ({ label: ITEM_SPECIALTY_TYPE_LABELS[type], value: type })
  );

  return (
    <div className="space-y-4">
      <FieldWrapper id="item-name" label="Name" required error={errors.name}>
        {(describedBy) => (
          <Input
            id="item-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="item-type" label="Type" required error={errors.type}>
        {(describedBy) => (
          <Dropdown
            id="item-type"
            aria_label="Type"
            aria_described_by={describedBy}
            aria_invalid={!!errors.type}
            aria_required
            items={typeItems}
            pre_selected_item={typeItems.find(
              (item) => item.value === state.type
            )}
            on_select={(item) => {
              if (!isItemCatalogType(item.value)) {
                return;
              }

              onChange('type', item.value);
            }}
            selection_placeholder="Select a type"
          />
        )}
      </FieldWrapper>

      <TextAreaField
        id="item-description"
        label="Description"
        required
        value={state.description}
        on_change={(value) => onChange('description', value)}
        error={errors.description}
      />

      <FieldWrapper id="item-default-position" label="Default Position">
        {(describedBy) => (
          <Dropdown
            id="item-default-position"
            aria_label="Default Position"
            aria_described_by={describedBy}
            items={positionItems}
            pre_selected_item={positionItems.find(
              (item) => item.value === state.default_position
            )}
            on_select={(item) => {
              if (!isItemDefaultPosition(item.value)) {
                return;
              }

              onChange('default_position', item.value);
            }}
            on_clear={() => onChange('default_position', '')}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <CheckboxField
        id="item-market-sellable"
        label="Market Sellable"
        checked={state.market_sellable}
        on_change={(value) => onChange('market_sellable', value)}
      />

      <CheckboxField
        id="item-can-drop"
        label="Can Drop"
        checked={state.can_drop}
        on_change={(value) => onChange('can_drop', value)}
      />

      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="item-cost"
          label="Gold Cost"
          value={state.cost}
          on_change={(value) => onChange('cost', value)}
          error={errors.cost}
          min={0}
        />
        <NumberField
          id="item-gold-dust-cost"
          label="Gold Dust Cost"
          value={state.gold_dust_cost}
          on_change={(value) => onChange('gold_dust_cost', value)}
          error={errors.gold_dust_cost}
          min={0}
        />
        <NumberField
          id="item-shards-cost"
          label="Shards Cost"
          value={state.shards_cost}
          on_change={(value) => onChange('shards_cost', value)}
          error={errors.shards_cost}
          min={0}
        />
        <NumberField
          id="item-copper-coin-cost"
          label="Copper Coin Cost"
          value={state.copper_coin_cost}
          on_change={(value) => onChange('copper_coin_cost', value)}
          error={errors.copper_coin_cost}
          min={0}
        />
        <NumberField
          id="item-gold-bars-cost"
          label="Gold Bars Cost"
          value={state.gold_bars_cost}
          on_change={(value) => onChange('gold_bars_cost', value)}
          error={errors.gold_bars_cost}
          min={0}
        />
      </div>

      <FieldWrapper id="item-alchemy-type" label="Alchemy Type">
        {(describedBy) => (
          <Dropdown
            id="item-alchemy-type"
            aria_label="Alchemy Type"
            aria_described_by={describedBy}
            items={alchemyItems}
            pre_selected_item={alchemyItems.find(
              (item) => item.value === state.alchemy_type
            )}
            on_select={(item) => {
              if (!isItemAlchemyType(item.value)) {
                return;
              }

              onChange('alchemy_type', item.value);
            }}
            on_clear={() => onChange('alchemy_type', '')}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>

      <FieldWrapper id="item-specialty-type" label="Specialty Type">
        {(describedBy) => (
          <Dropdown
            id="item-specialty-type"
            aria_label="Specialty Type"
            aria_described_by={describedBy}
            items={specialtyItems}
            pre_selected_item={specialtyItems.find(
              (item) => item.value === state.specialty_type
            )}
            on_select={(item) => {
              if (!isItemSpecialtyType(item.value)) {
                return;
              }

              onChange('specialty_type', item.value);
            }}
            on_clear={() => onChange('specialty_type', '')}
            selection_placeholder="None"
          />
        )}
      </FieldWrapper>
    </div>
  );
};

export default ItemBasicFields;
