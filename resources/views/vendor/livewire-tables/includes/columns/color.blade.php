<div
  @class([
    'place-content-center place-items-center content-center items-center' => $isTailwind,
  ])
>
  <div
    {{
      $attributeBag->class([
        'h-6 w-6 self-center rounded-md' => $isTailwind && ($attributeBag['default'] ?? empty($attributeBag['class']) || (! empty($attributeBag['class']) && ($attributeBag['default'] ?? false))),
      ])
    }}
    @style([
      "background-color: {$color}" => $color,
    ])
  ></div>
</div>
