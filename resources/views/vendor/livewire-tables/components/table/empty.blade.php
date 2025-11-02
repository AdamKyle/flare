@aware(['isTailwind', 'isBootstrap'])

@php($attributes = $attributes->merge(['wire:key' => 'empty-message-' . $this->getId()]))

@if ($isTailwind)
  <tr {{ $attributes }}>
    <td colspan="{{ $this->getColspanCount() }}">
      <div class="flex items-center justify-center space-x-2 dark:bg-gray-800">
        <span class="py-8 text-lg font-medium text-gray-400 dark:text-white">
          {{ $this->getEmptyMessage() }}
        </span>
      </div>
    </td>
  </tr>
@elseif ($isBootstrap)
  <tr {{ $attributes }}>
    <td colspan="{{ $this->getColspanCount() }}">
      {{ $this->getEmptyMessage() }}
    </td>
  </tr>
@endif
