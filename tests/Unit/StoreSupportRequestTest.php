<?php

namespace Tests\Unit;

use App\Http\Requests\StoreSupportRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreSupportRequestTest extends TestCase
{
    public function test_it_normalizes_identity_fields_through_the_validation_lifecycle(): void
    {
        config()->set('app.frontend_url', 'http://localhost:3000/');
        $request = $this->request([
            'email' => '  TRAVELER@EXAMPLE.COM ',
            'city_slug' => ' DUBAI ',
            'item_slug' => ' DESERT-SAFARI ',
            'item_type' => ' ACTIVITY ',
        ]);

        $request->validateResolved();

        $this->assertSame('traveler@example.com', $request->input('email'));
        $this->assertSame('dubai', $request->input('city_slug'));
        $this->assertSame('desert-safari', $request->input('item_slug'));
        $this->assertSame('activity', $request->input('item_type'));
    }

    public function test_it_accepts_the_configured_origin_and_exact_item_path(): void
    {
        config()->set('app.frontend_url', 'https://weelp.example/');

        $this->request([
            'page_url' => 'https://weelp.example/cities/dubai/activities/desert-safari?source=help#form',
        ])->validateResolved();

        $this->addToAssertionCount(1);
    }

    public function test_it_treats_an_explicit_default_port_as_the_same_origin(): void
    {
        config()->set('app.frontend_url', 'http://localhost:80/');

        $this->request([
            'page_url' => 'http://localhost/cities/dubai/activities/desert-safari',
        ])->validateResolved();

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidPageBoundaries')]
    public function test_it_rejects_invalid_page_url_boundaries(string $pageUrl): void
    {
        config()->set('app.frontend_url', 'https://weelp.example/');

        $errors = $this->validationErrors($this->request(['page_url' => $pageUrl]));

        $this->assertArrayHasKey('page_url', $errors);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPageBoundaries(): array
    {
        return [
            'wrong scheme' => ['http://weelp.example/cities/dubai/activities/desert-safari'],
            'wrong host' => ['https://evil.example/cities/dubai/activities/desert-safari'],
            'wrong nondefault port' => ['https://weelp.example:8443/cities/dubai/activities/desert-safari'],
            'wrong path' => ['https://weelp.example/cities/dubai/packages/desert-safari'],
        ];
    }

    public function test_malformed_non_string_inputs_return_validation_errors_without_unexpected_exceptions(): void
    {
        config()->set('app.frontend_url', 'https://weelp.example/');
        $request = $this->request([
            'email' => ['traveler@example.com'],
            'city_slug' => ['dubai'],
            'item_slug' => ['desert-safari'],
            'item_type' => ['activity'],
            'page_url' => ['https://weelp.example'],
        ]);

        $errors = $this->validationErrors($request);

        foreach (['email', 'city_slug', 'item_slug', 'item_type', 'page_url'] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    #[DataProvider('invalidFrontendUrls')]
    public function test_missing_or_invalid_frontend_configuration_is_a_validation_error(mixed $frontendUrl): void
    {
        config()->set('app.frontend_url', $frontendUrl);

        $errors = $this->validationErrors($this->request());

        $this->assertArrayHasKey('page_url', $errors);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidFrontendUrls(): array
    {
        return [
            'missing' => [null],
            'malformed' => ['not a URL'],
            'unsupported scheme' => ['ftp://weelp.example'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function request(array $overrides = []): StoreSupportRequest
    {
        $request = StoreSupportRequest::create('/api/support-requests', 'POST', array_merge([
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
            'topic' => 'before_booking',
            'message' => 'I need help before completing this booking.',
            'item_type' => 'activity',
            'item_id' => 1,
            'item_title' => 'Desert Safari',
            'city_slug' => 'dubai',
            'item_slug' => 'desert-safari',
            'page_url' => 'http://localhost:3000/cities/dubai/activities/desert-safari',
            'client_request_id' => 'd13c4072-f70d-49ca-915c-19a959715755',
            'website' => null,
        ], $overrides));

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        return $request;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationErrors(StoreSupportRequest $request): array
    {
        try {
            $request->validateResolved();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        $this->fail('Expected the support request to fail validation.');
    }
}
