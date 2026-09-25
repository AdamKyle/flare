# Proof of Work — Flare 2.0 Browser-Test Remediation Pass

## Status

- All 17 requested items are implemented, all 3 review passes are done, and every required command passes.
- Item 13 follows the user's later instruction instead of the original prompt. The user said: "I don't want the activity tab to be on top of, it should be another tab on the bottom like character or map". Activity is therefore a sixth bottom-nav tab, which overrides the prompt's "no sixth tab" rule.
- The tool's permission layer refused `rm`. The user deleted the two dead files by hand: `resources/js/game/components/shop/slots/types/slots-props.ts` and `resources/js/ui/tool-tips/utils/get-scroll-parent.ts`.
- No agents, background tasks, dependency changes, migrations, database access, or state-changing git commands were used.

## Backend contracts changed

### `POST /api/slots/roll/{character}` (`GamblerService::roll`)

- Every successful spin now returns `gold` (the authoritative Gold left after the spin) and `reward`.
- `reward` is `null` for a losing spin and for a Copper Coin match without the quest item.
- Otherwise `reward` is `{currency, amount, balance}`:
  - `amount` is the amount actually credited after the `CurrencyLimit` cap (`newBalance - previousBalance`);
  - `balance` is the resulting stored total.
- The reward bonus is now a whole-number percentage (`resolveRewardBonusPercent`) applied with `intdiv`, so no float arithmetic is involved.
- The message reports the amount actually credited. When the currency is already at its cap, it says: "You matched X, but you are already at the maximum amount you can hold."

### `GET /api/character-sheet/{character}`

- Adds `currency_limits` with values taken from `CurrencyLimit`:
  - `gold` = `MAX_GOLD`
  - `gold_dust` = `MAX_GOLD_DUST`
  - `shards` = `MAX_SHARDS`
  - `copper_coins` = `MAX_COPPER`

### `POST /api/goblin-shop/buy-item/{character}/{item}`

- Now requires `amount` (`GoblinShopPurchaseRequest`: required, integer, min 1).
- `GoblinShopService::buyItem(Character, Item, int $amount)` checks everything before it deducts anything. It rejects:
  - an item the Goblin Shop does not sell;
  - Gold Bars below `cost × amount`;
  - an alchemy bag without room for `amount`;
  - an inventory without room for `amount` (non-alchemy items).
- After the checks pass, it subtracts `cost × amount` and gives the items in one operation (alchemy stacks, or `createMany`).
- The response adds `inventory_count`.
- Previously the service charged Gold Bars even when the alchemy bag was full, and it had no affordability check.

### `GET /api/market-board/access/{character}` (new)

- Returns `{can_access_market}`.
- The rule lives in the new `MarketAccessService::canAccess(User)`: Admin, or the character is standing on an `is_port` Location. The `can.access.market` middleware now uses the same service.
- Behaviour is unchanged: the Admin check still runs before the character is read.

### `GET /api/map/details/{gameMap}`

- Adds `gem_context`, built by `GemWorldService::gameMapGemContext()`, which uses the existing `AreaGemEffectService::resolveForGameMap` and `GemWorldContextTransformer`.
- It is the same player-safe shape the Gem World side peek already consumes, or `null` when the map has no rolled Gem.

## Frontend behaviour changed

1. **Slots**
   - Opens inside the Shops `FloatingCard` using Craft's pattern: the card title switches to "Slots", the header gets the `back_action` left arrow, and the content transitions with the reused `CraftingScreenTransition`.
   - No nested `StackedCard` and no inner X.
   - Shows "Cost per spin" (EXACT) and "You Have:" Gold (BALANCE, read from GameData).
   - After a spin, `updateCharacter(buildSlotCurrencyUpdate(result))` writes the server's Gold and reward balance into GameData.
   - Once the reels stop, `SlotSpinReward` shows `+amount` (EXACT, only when above 0) and "You Have:" the resulting balance (BALANCE).
   - The reel animation, reduced-motion handling, cooldown and help link are unchanged.
2. **Currency**
   - New `truncateCompactNumber`, used by CurrencyDisplay's BALANCE mode. It truncates to one decimal: 1,961,951,913,134 → `1.9 T`, 1,999,999 → `1.9 M`, 971,167 → `971.1 k`.
   - `shortenNumber` is unchanged, because health and stat displays use it.
   - `CURRENCY_PRESENTATION` adds `obtained_from` and `used_for` text, sourced from the game's information pages.
   - The new `CurrencyBalancePopover` shows, in order:
     - icon and name (`text-base`);
     - separator;
     - two `text-[10px]` sentences;
     - "You can have a max of" (only when a limit is known);
     - "You Have: <exact>" (`text-sm`).
   - The limit comes from `useCurrencyLimit`. It reads the GameData context and returns `null` outside the game (for example on Information pages).
3. **Tooltip**
   - `BaseToolTip` now portals its popover to `document.body` and positions it with `position: fixed` at `z-[99999]`, the same layer as the side peek.
   - The new pure `resolveTooltipPosition` places it above, below, left or right in preference order, then clamps it inside an 8px viewport margin.
   - `useTooltipPlacement` recomputes on resize, on scroll (capture), and when the popover's size changes (`ResizeObserver`).
   - The `placementDeps` prop is removed from `BaseToolTip`, `GeneralToolTip` and `StatToolTip`.
   - `aria-describedby`, Escape, click and focus behaviour are preserved, and hovering onto the popover keeps it open.
4. **Goblin Shop**
   - Item titles use `planeTextItemColors`, so they are readable in dark mode.
   - The no-op Buy button is replaced by `GoblinShopPurchaseForm`:
     - a labelled amount input;
     - price each, number affordable (`floor(gold bars / cost)`) and total cost;
     - strict whole-number validation;
     - a `LoadingButton` that cannot be submitted twice;
     - API error and success alerts;
     - `updateCharacter({gold_bars, inventory_count})`.
5. **Market button:** enabled from `useMarketAccess`, which re-checks when the emitted character position changes, instead of Set Sail availability.
6. **Item skills:** a modifier row is hidden when both values render as `0.00%` (`isDisplayedAsZeroPercent`, which uses the same `toFixed(2)` display precision).
7. **Map detail side peek**
   - Renders `GameMapDetail` with `single_column` (passed to the existing `DetailGrid` prop), plus a gem section (`PlayerGameMapGemSection`).
   - The gem section opens local `StackedCard` layers using the new shared `GemContextProfileDetail` (rolled Gem parameters) and `GemContextEffectsDetail`.
   - Gem World now uses those same components and the shared `resolveRolledGemDisplayGroups`, so the rendering is not duplicated.
   - The Admin `GameMapDetail` layouts are unchanged.
8. **Mobile nav:** the separate strip is removed. Activity is a sixth bottom tab (`grid-cols-6`) with the bell icon and the pulsing `ActivityStatusIndicator`, and the spacer is back to `h-16`.
9. **Map floating card**
   - Gem source and profile text is removed.
   - The entry button and My Kingdoms are full width.
   - Paired buttons sit in equal columns (`grid grid-cols-1 sm:grid-cols-2`, each `w-full`): View Gem Effects/Exit Gem World, and Gem Progress History/All Active Gem Scrolls.

## Files added

- `app/Game/Market/Services/MarketAccessService.php`
- `app/Game/Shop/Requests/GoblinShopPurchaseRequest.php`
- `resources/js/ui/tool-tips/types/tooltip-position.ts`
- `resources/js/ui/tool-tips/types/tooltip-placement.ts`
- `resources/js/ui/tool-tips/types/resolve-tooltip-position-params.ts`
- `resources/js/ui/tool-tips/utils/resolve-tooltip-position.ts`
- `resources/js/game-data/api-data-definitions/character/currency-limits-definition.ts`
- `resources/js/game/reusable-components/currency/hooks/use-currency-limit.ts`
- `resources/js/game/reusable-components/currency/components/currency-balance-popover.tsx`
- `resources/js/game/reusable-components/currency/components/types/currency-balance-popover-props.ts`
- `resources/js/game/components/shop/slots/api/definitions/spin-slots-reward-definition.ts`
- `resources/js/game/components/shop/slots/utils/build-slot-currency-update.ts`
- `resources/js/game/components/shop/slots/components/slot-spin-reward.tsx`
- `resources/js/game/components/shop/slots/components/types/slot-spin-reward-props.ts`
- `resources/js/game/components/market/api/definitions/market-access-response-definition.ts`
- `resources/js/game/components/market/api/hooks/use-market-access.ts`
- `resources/js/game/components/market/api/hooks/definitions/use-market-access-definition.ts`
- `resources/js/game/components/market/api/hooks/definitions/use-market-access-params.ts`
- `resources/js/game/components/goblin-shop/api/definitions/goblin-shop-purchase-request-definition.ts`
- `resources/js/game/components/goblin-shop/api/definitions/goblin-shop-purchase-response-definition.ts`
- `resources/js/game/components/goblin-shop/api/hooks/use-purchase-goblin-shop-item.ts`
- `resources/js/game/components/goblin-shop/api/hooks/definitions/use-purchase-goblin-shop-item-definition.ts`
- `resources/js/game/components/goblin-shop/api/hooks/definitions/use-purchase-goblin-shop-item-params.ts`
- `resources/js/game/components/goblin-shop/components/goblin-shop-purchase-form.tsx`
- `resources/js/game/components/goblin-shop/components/types/goblin-shop-purchase-form-props.ts`
- `resources/js/game/components/goblin-shop/types/goblin-shop-quantity-validation.ts`
- `resources/js/game/components/goblin-shop/utils/validate-goblin-shop-quantity.ts`
- `resources/js/game/reusable-components/game-map/types/player-game-map-detail-definition.ts`
- `resources/js/game/reusable-components/gems/utils/resolve-rolled-gem-display-groups.ts`
- `resources/js/game/reusable-components/gems/components/gem-context-profile-detail.tsx`
- `resources/js/game/reusable-components/gems/components/gem-context-effects-detail.tsx`
- `resources/js/game/reusable-components/gems/types/gem-context-detail-props.ts`
- `resources/js/game/reusable-components/gems/types/gem-context-profile-detail-props.ts`
- `resources/js/game/components/side-peeks/game-data/components/player-game-map-gem-section.tsx`
- `resources/js/game/components/side-peeks/game-data/components/types/player-game-map-gem-section-props.ts`
- `resources/js/game/components/side-peeks/game-data/enums/player-game-map-gem-detail.ts`

## Files changed

### Backend

- `app/Game/Gambler/Services/GamblerService.php`
- `app/Game/Character/CharacterSheet/Transformers/CharacterSheetTransformer.php`
- `app/Game/Shop/Services/GoblinShopService.php`
- `app/Game/Shop/Controllers/Api/GoblinShopController.php`
- `app/Game/Market/Middleware/CanCharacterAccessMarket.php`
- `app/Game/Market/Controllers/Api/MarketController.php`
- `routes/game/market-board/api.php`
- `app/Game/Maps/Services/GemWorldService.php` — new `gameMapGemContext()`; every method docblock brought to the required format; the narrative class docblock removed
- `app/Game/Maps/Controllers/Api/MapController.php` — `GemWorldService` injected and `gem_context` added; every method now documented; the unused `Exception` import removed. The existing method bodies are unchanged.
- `app/Game/Maps/Transformers/GameMapDetailTransformer.php`

### Frontend

- `resources/js/game/util/format-number.ts`
- `resources/js/game/reusable-components/currency/currency-display.tsx`
- `resources/js/game/reusable-components/currency/constants/currency-presentation.ts`
- `resources/js/game/reusable-components/currency/types/currency-presentation-definition.ts`
- `resources/js/game-data/api-data-definitions/character/character-sheet-definition.ts`
- `resources/js/ui/tool-tips/base-tool-tip.tsx`
- `resources/js/ui/tool-tips/general-tool-tip.tsx`
- `resources/js/ui/tool-tips/hooks/use-tooltip-placement.ts`
- `resources/js/ui/tool-tips/hooks/definitions/use-tooltip-placement-params.ts`
- `resources/js/ui/tool-tips/hooks/definitions/use-tooltip-placement-definition.ts`
- `resources/js/ui/tool-tips/types/base-tool-tips-props.ts`
- `resources/js/ui/tool-tips/types/general-tool-tip-props.ts`
- `resources/js/game/reusable-components/item/tool-tips/stat-tool-tip.tsx`
- `resources/js/game/components/actions/partials/floating-cards/shop-section/shop-card.tsx`
- `resources/js/game/components/shop/slots/slots.tsx`
- `resources/js/game/components/shop/slots/hooks/use-slot-machine.ts`
- `resources/js/game/components/shop/slots/hooks/definitions/use-slot-machine-definition.ts`
- `resources/js/game/components/shop/slots/hooks/definitions/use-slot-machine-params.ts`
- `resources/js/game/components/shop/slots/api/definitions/spin-slots-response-definition.ts`
- `resources/js/game/components/market/api/enums/market-apis.ts`
- `resources/js/game/components/goblin-shop/components/goblin-shop-card.tsx`
- `resources/js/game/components/goblin-shop/goblin-shop.tsx`
- `resources/js/game/components/goblin-shop/types/goblin-shop-card-props.ts`
- `resources/js/game/components/side-peeks/character-inventory/inventory-item/item-skills/utils/build-item-skill-modifier-rows.ts`
- `resources/js/game/reusable-components/game-map/components/game-map-detail.tsx`
- `resources/js/game/reusable-components/game-map/types/game-map-detail-props.ts`
- `resources/js/game/reusable-components/game-map/api/hooks/use-player-game-map-detail.ts`
- `resources/js/game/reusable-components/game-map/api/hooks/definitions/use-player-game-map-detail-definition.ts`
- `resources/js/game/components/side-peeks/game-data/player-game-map-detail-side-peek.tsx`
- `resources/js/game/components/side-peeks/map-actions/gem-world/gem-world.tsx`
- `resources/js/game/components/actions/components/mobile-nav-bar/core-mobile-nav-bar.tsx`
- `resources/js/game/components/actions/partials/floating-cards/map-section/gem-world-actions.tsx`
- `resources/js/game/components/actions/partials/floating-cards/map-section/map-tab-content.tsx`

## Files deleted (by the user; `rm` was refused)

- `resources/js/game/components/shop/slots/types/slots-props.ts` — Slots no longer takes props.
- `resources/js/ui/tool-tips/utils/get-scroll-parent.ts` — replaced by viewport positioning.

## Tests added or updated

### `tests/Unit/Game/Gambler/Services/GamblerServiceTest.php`

Added:

- `test_losing_spin_returns_the_gold_left_after_the_spin_and_no_reward`
- `test_winning_spin_returns_the_credited_reward_and_resulting_balance`
- `test_winning_spin_near_the_currency_cap_reports_only_the_amount_actually_credited`
- `test_copper_coin_match_without_the_quest_item_reports_no_reward_and_the_gold_left`

Updated: the three at-cap tests now expect the factual message for a match that credits nothing.

### `tests/Unit/Game/Shop/Services/GoblinShopServiceTest.php`

- `resolve()` is replaced by explicit `new GoblinShopService(...)`, and the calls use the new `amount` argument.
- Added:
  - `test_buy_item_buys_the_requested_amount_and_charges_the_total_gold_bars`
  - `test_buy_item_rejects_an_amount_the_character_cannot_afford`
  - `test_buy_item_rejects_more_non_alchemy_items_than_the_inventory_can_hold`
- The alchemy-bag-full test now also checks the message and that Gold Bars are unchanged.

### Controller tests

- `tests/Feature/Game/Shop/Controllers/Api/GoblinShopControllerTest.php`: sends `amount`, checks `character_gold_bars` and `inventory_count`, and adds `test_purchase_item_rejects_a_non_positive_amount`.
- `tests/Feature/Game/Market/Controllers/Api/MarketControllerTest.php`: adds `test_market_access_is_granted_when_the_character_stands_on_a_port` and `test_market_access_is_refused_when_the_character_is_not_on_a_port`.
- `tests/Feature/Game/Maps/Controllers/Api/MapControllerTest.php`: adds `test_game_map_details_include_the_rolled_map_gem_context` and `test_game_map_details_have_no_gem_context_when_the_map_has_no_rolled_gem`.
- `tests/Feature/Game/Character/CharacterSheet/Controllers/Api/CharacterSheetControllerTest.php`: adds `test_character_sheet_returns_the_maximum_amount_of_each_currency`.

## Commands run (final state)

| Command | Result |
|---|---|
| `./vendor/bin/pest --filter "Tests\\Unit\\Game\\Gambler\\Services\\GamblerServiceTest\|Tests\\Feature\\Game\\Gambler\\Controllers\\Api\\GamblerControllerTest\|Tests\\Unit\\Game\\Shop\\Services\\GoblinShopServiceTest\|Tests\\Feature\\Game\\Shop\\Controllers\\Api\\GoblinShopControllerTest\|Tests\\Feature\\Game\\Market\\Controllers\\Api\\MarketControllerTest\|Tests\\Feature\\Game\\Maps\\Controllers\\Api\\MapControllerTest\|Tests\\Feature\\Game\\Maps\\Controllers\\Api\\GemWorldControllerTest\|Tests\\Feature\\Game\\Character\\CharacterSheet\\Controllers\\Api\\CharacterSheetControllerTest\|Tests\\Unit\\Game\\Character\\CharacterSheet\\Transformers\\CharacterSheetTransformerTest"` | PASS: 178 tests, 536 assertions |
| `yarn type-check` | pass |
| `yarn cleanup` | ran (Prettier and ESLint fixes) |
| `yarn lint` | 0 errors; the same 7 existing warnings, all in files this pass did not touch |
| `yarn unused-files-check` | pass, after the user deleted the two files |
| `./vendor/bin/pint --test` | pass |
| `yarn build:dev` | pass |

The repository's PHPUnit tests only run through the Pest binary.

## Review passes

### Pass 1 — requirement audit

Every item was checked against the final source:

- **Slots:** no nested card or inner X; back arrow present; Gold and reward display; truncation rule; popover content.
- **Tooltip:** portal, fixed position and clamping.
- **Goblin Shop:** readable titles; real Buy; quantity limited by Gold Bars.
- **Market:** gate uses the access service.
- **Item skills:** rows that display as zero are hidden.
- **Map:** gem drill-down; single-column side peek.
- **Activity:** a bottom tab, per the user's instruction.
- **Map card:** gem text removed; button widths as required.

### Pass 2 — skills and conventions

- **PHP:** no `final`, `strict_types`, casts, `resolve()`/`app()` or debug output in the changed PHP. Every method in the touched classes has a single-space `@param`/`@return` docblock.
- **Tests:** no helpers, data providers, reflection or `resolve()` in the changed tests.
- **Frontend:**
  - props are in `types` files;
  - API definitions and URL enums are in place;
  - hooks use abort/stale guards, a duplicate-submit ref and owned errors;
  - render helpers replace inline conditional blocks;
  - no `console`, `eslint-disable`, `any` or type assertions were added.
- **No duplication:** gem rendering and currency formatting each have one shared implementation.

### Pass 3 — dead code

`rg` over the changed paths found:

- none of these: `on_click={() => {}}`, `bottom-16`, `isSetSailEnabled` in the Shop card, `shortenNumber` in currency, `TODO`/`FIXME`;
- `StackedCard` only in the intended gem drill-down.

`yarn unused-files-check` reports no unimported files.

## Environment limitations

- No browser or websocket client could be run, so nothing was observed in a running game.
- These checks were used instead:
  - backend feature and unit tests for every changed contract;
  - TypeScript type-check and a production-mode build;
  - source inspection of:
    - the Slots flow (server `rolls`, `gold` and `reward` → GameData and the result section);
    - the tooltip positioning utility;
    - the mobile nav markup;
    - the map side peek composition.

## Follow-up: mobile Activity tab label indicator

**File changed:** `resources/js/game/components/actions/components/mobile-nav-bar/core-mobile-nav-bar.tsx` (only this file).

**Bell removed**
- The mobile Activity tab no longer renders `ActivityStatusIndicator`, and its import is removed from this file.
- The desktop/tablet `icon-section.tsx` still uses the indicator and is unchanged.
- The tab icon is still `far fa-bell` with no badge.

**Label and icon pulse** (the icon was added at the user's later request)
- `renderItem` takes an optional `isPulsing` flag, defaulting to `false`. Other tabs don't pass it, so they render exactly as before.
- For the Activity tab, `isPulsing` is `has_new_announcements`.
- When it is true, `renderItemContent()` wraps the tab's icon and label in a `motion.div` that animates only `color`:
  - from `var(--activity-pulse-base)` to `var(--activity-pulse-accent)`;
  - 1.2s, `easeInOut`, repeating forever and reversing each cycle.
- The icon and label inherit that color, so they pulse together. There is no opacity or scale animation.
- Both variables are set per theme with Tailwind classes pointing at existing theme tokens:
  - base: `--color-gray-800`, or `--color-gray-100` in dark mode;
  - accent: `--color-mango-tango-600`, or `--color-mango-tango-400` in dark mode (the accent `ActivityStatusIndicator` already uses).
- No hex values, CSS files or keyframes were added.
- When there is nothing new, the default label span renders and no animation runs.

**Reduced motion**
- `useReducedMotion()` from Framer Motion.
- With something new, the icon and label render statically in `text-mango-tango-600 dark:text-mango-tango-400`.
- With nothing new, the normal content renders.

**Accessibility**
- The button keeps `aria-label={activityAriaLabel}`, which says "Activity, new announcements available" when there is something new.
- No live region was added.
- Opening and closing the panel, the active-tab state, the tab count (6) and every other tab are unchanged.

**Commands**
- `yarn build:dev` passes.
- `yarn prettier --write` and `yarn eslint` on the changed file pass, with no warnings.

## Follow-up: generic mobile slide-up panel extracted to `ui`

**Files added**
- `resources/js/ui/mobile-panel/mobile-panel.tsx`
- `resources/js/ui/mobile-panel/types/mobile-panel-props.ts`

Props: `title`, `on_close`, optional `allow_clicking_outside` (default `true`), `children`.

**Files changed**
- `resources/js/game/components/activity/components/mobile-activity-panel.tsx`

No files were removed. `activity/components/types/mobile-activity-panel-props.ts` (`on_close`) is still used by the Activity wrapper.

**Moved into `MobilePanel`**
Each item keeps its previous markup, classes and values:
- Backdrop:
  - fixed full-screen, `z-50`;
  - `bg-black/50 dark:bg-black/70`, `aria-hidden`;
  - fades over 0.25s;
  - clicking it closes the panel.
- Panel:
  - fixed to the left, right and bottom, `z-50`;
  - `max-h-3/4`, scrolls vertically, rounded top corners, shadow;
  - white, or gray-800 in dark mode;
  - bottom safe-area padding, `sm:hidden`.
- Animation: slides from `y: 100%` to `0` and back on exit (tween, `easeOut`, 0.25s).
- Reduced motion: `useReducedMotion()`, so no movement animation (zero duration).
- Accessibility:
  - `role="dialog"`, `aria-modal`, and `aria-labelledby` pointing to the heading's `useId`;
  - `inert` while exiting;
  - focus, Escape and outside-click handling through the existing `useSidePeekAccessibility`.
- Body scroll lock: saves and restores the previous `document.body.style.overflow`.
- Header: the title, plus a close button (`aria-label` "Close {title} panel", disabled while exiting).

**What Activity still owns**
- `useActivityActions()` and `has_new_announcements`.
- `ActivityContent`, whose `on_action_selected` calls `on_close`, so choosing an action still closes the panel.
- The title "Activity".

`ActivityContent`, the mobile nav, its `AnimatePresence`, the label/icon pulse and the desktop side peek are unchanged. The generic component imports nothing from features.

Activity was the only copy of this shell; no other duplicate was found.

**Commands**
- `yarn build:dev` passes.
- `yarn prettier --check` and `yarn eslint` on the changed files are clean.

## Not touched

- `SellItemListener`
- Other Shop, Market and Inventory hooks
- `MapController` method logic other than `gameMapDetails`
- Admin Game Map screens
- The 7 existing lint warnings
