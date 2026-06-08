<?php

namespace Tests\Feature\Game\Kingdoms\Middleware;

use App\Game\Kingdoms\Middleware\DoesKingdomBelongToAuthorizedUser;
use App\Flare\Models\Kingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DoesKingdomBelongToAuthorizedUserTest extends TestCase
{
    use RefreshDatabase;

    public function testOwnerIsAllowedForOwnKingdom(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $this->actingAs($character->user);

        $response = (new DoesKingdomBelongToAuthorizedUser)->handle(
            $this->requestWithRouteParameters([
                'kingdom' => $kingdom,
                'character' => $character,
            ]),
            fn () => response('allowed')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRouteCharacterCannotOverrideAuthenticatedCharacterOwnership(): void
    {
        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $ownerFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $owner = $ownerFactory->getCharacter();
        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->actingAs($nonOwner->user);

        $response = (new DoesKingdomBelongToAuthorizedUser)->handle(
            $this->requestWithRouteParameters([
                'kingdom' => $kingdom,
                'character' => $owner,
            ]),
            fn () => response('allowed')
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testMatchingKingdomAndCharacterIdsDoNotGrantOwnership(): void
    {
        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $owner = $ownerFactory->getCharacter();
        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = new Kingdom(['character_id' => $owner->id]);
        $kingdom->id = $nonOwner->id;
        $this->actingAs($nonOwner->user);

        $response = (new DoesKingdomBelongToAuthorizedUser)->handle(
            $this->requestWithRouteParameters([
                'kingdom' => $kingdom,
            ]),
            fn () => response('allowed')
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    private function requestWithRouteParameters(array $parameters): Request
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        $route = new Route(['POST'], '/test', fn () => null);
        $route->bind($request);

        foreach ($parameters as $name => $value) {
            $route->setParameter($name, $value);
        }

        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
