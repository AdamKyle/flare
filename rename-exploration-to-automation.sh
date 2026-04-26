#!/usr/bin/env bash

set -euo pipefail

if [ ! -d app/Game/Exploration ]; then
  echo "Missing app/Game/Exploration. Run this from the project root."
  exit 1
fi

git mv app/Game/Exploration app/Game/Automation

if [ -d tests/Unit/Game/Exploration ]; then
  git mv tests/Unit/Game/Exploration tests/Unit/Game/Automation
fi

if [ -d tests/Feature/Game/Exploration ]; then
  git mv tests/Feature/Game/Exploration tests/Feature/Game/Automation
fi

if [ -d routes/game/exploration ]; then
  mkdir -p routes/game/automation
  git mv routes/game/exploration/api.php routes/game/automation/api.php
  git mv routes/game/exploration/channels.php routes/game/automation/channels.php
  rmdir routes/game/exploration
fi

FILES=$(git ls-files \
  app \
  config \
  routes \
  resources/js \
  tests \
  | grep -Ev '(^test-coverage/|/test-coverage/)')

perl -pi -e 's/App\\\\Game\\\\Exploration/App\\\\Game\\\\Automation/g' $FILES
perl -pi -e 's/App\\Game\\Exploration/App\\Game\\Automation/g' $FILES

perl -pi -e 's/Tests\\\\Unit\\\\Game\\\\Exploration/Tests\\\\Unit\\\\Game\\\\Automation/g' $FILES
perl -pi -e 's/Tests\\Unit\\Game\\Exploration/Tests\\Unit\\Game\\Automation/g' $FILES
perl -pi -e 's/Tests\\\\Feature\\\\Game\\\\Exploration/Tests\\\\Feature\\\\Game\\\\Automation/g' $FILES
perl -pi -e 's/Tests\\Feature\\Game\\Exploration/Tests\\Feature\\Game\\Automation/g' $FILES

perl -pi -e 's/Game\\.Exploration\\.Events/Game.Automation.Events/g' $FILES

perl -pi -e 's/mapExplorationApiRoutes/mapAutomationApiRoutes/g' app/Providers/RouteServiceProvider.php
perl -pi -e 's/routes\/game\/exploration/routes\/game\/automation/g' app/Providers/RouteServiceProvider.php app/Providers/BroadcastServiceProvider.php
perl -pi -e "s/App\\\\Game\\\\Exploration\\\\Controllers/App\\\\Game\\\\Automation\\\\Controllers/g" app/Providers/RouteServiceProvider.php

perl -pi -e "s/App\\\\Game\\\\Exploration\\\\Providers\\\\ServiceProvider::class/App\\\\Game\\\\Automation\\\\Providers\\\\ServiceProvider::class/g" config/app.php

perl -pi -e "s/'\/exploration\/\\{character\\}\/start'/'\/automation\/{character}\/start'/g" routes/game/automation/api.php
perl -pi -e "s/'\/exploration\/\\{character\\}\/stop'/'\/automation\/{character}\/stop'/g" routes/game/automation/api.php
perl -pi -e "s/'exploration\.start'/'automation.start'/g" routes/game/automation/api.php
perl -pi -e "s/'exploration\.stop'/'automation.stop'/g" routes/game/automation/api.php

perl -pi -e 's/"exploration\/" \+ this\.props\.character\.id \+ "\/start"/"automation\/" + this.props.character.id + "\/start"/g' resources/js/game/sections/game-actions-section/components/exploration-section.tsx
perl -pi -e 's/"exploration\/" \+ this\.props\.character\.id \+ "\/stop"/"automation\/" + this.props.character.id + "\/stop"/g' resources/js/game/sections/game-actions-section/components/exploration-section.tsx

composer dump-autoload

echo ""
echo "Remaining structural references to review:"
rg -n "App\\\\Game\\\\Exploration|App\\Game\\Exploration|Tests\\\\Unit\\\\Game\\\\Exploration|Tests\\Unit\\Game\\Exploration|Tests\\\\Feature\\\\Game\\\\Exploration|Tests\\Feature\\Game\\Exploration|Game\\.Exploration\\.Events|routes/game/exploration|mapExplorationApiRoutes|/exploration/|exploration\\." app config routes resources/js tests || true

echo ""
echo "Done. Review remaining matches before committing."
