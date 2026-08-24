<?php

namespace Tests\Unit\Flare\Pagination\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;
use Tests\TestCase;

class PaginationRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $this->assertTrue((new PaginationRequest())->authorize());
    }

    public function test_rules_returns_the_expected_validation_rules(): void
    {
        $rules = (new PaginationRequest())->rules();

        $this->assertSame([
            'per_page' => 'required|min:1|integer',
            'page' => 'required|min:1|integer',
            'search_text' => 'nullable|string',
            'filters' => 'nullable|array',
        ], $rules);
    }

    public function test_messages_returns_the_expected_custom_messages(): void
    {
        $messages = (new PaginationRequest())->messages();

        $this->assertSame([
            'per_page.required' => 'How many do you want per page?',
            'page.required' => 'What page are we trying to fetch?',
        ], $messages);
    }

    public function test_prepare_for_validation_fills_in_defaults(): void
    {
        $request = PaginationRequest::create('/test', 'GET', []);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->prepareForValidation();

        $this->assertSame(15, $request->input('per_page'));
        $this->assertSame(1, $request->input('page'));
        $this->assertSame('', $request->input('search_text'));
        $this->assertSame([], $request->input('filters'));
    }

    public function test_prepare_for_validation_preserves_provided_values(): void
    {
        $request = PaginationRequest::create('/test', 'GET', [
            'per_page' => 50,
            'page' => 3,
            'search_text' => 'sword',
            'filters' => ['type' => 'weapon'],
        ]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->prepareForValidation();

        $this->assertSame(50, $request->input('per_page'));
        $this->assertSame(3, $request->input('page'));
        $this->assertSame('sword', $request->input('search_text'));
        $this->assertSame(['type' => 'weapon'], $request->input('filters'));
    }
}
