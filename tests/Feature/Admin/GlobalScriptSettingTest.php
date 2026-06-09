<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalScriptSettingTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_and_update_global_script_slots(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->putJson('/api/admin/global-scripts', [
                'head_code' => '<meta name="global-head" content="1">',
                'body_code' => '<script>window.globalBody=true</script>',
                'footer_code' => '<script>window.globalFooter=true</script>',
            ])
            ->assertOk()
            ->assertJsonPath('data.head_code', '<meta name="global-head" content="1">');

        $this->assertDatabaseHas('global_script_settings', [
            'id' => 1,
            'head_code' => '<meta name="global-head" content="1">',
            'body_code' => '<script>window.globalBody=true</script>',
            'footer_code' => '<script>window.globalFooter=true</script>',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson('/api/admin/global-scripts', [
                'head_code' => '<meta name="global-head" content="2">',
            ])
            ->assertOk()
            ->assertJsonPath('data.head_code', '<meta name="global-head" content="2">')
            ->assertJsonPath('data.body_code', '<script>window.globalBody=true</script>');

        $this->assertDatabaseCount('global_script_settings', 1);
        $this->assertDatabaseHas('global_script_settings', [
            'id' => 1,
            'head_code' => '<meta name="global-head" content="2">',
            'body_code' => '<script>window.globalBody=true</script>',
        ]);
    }

    public function test_public_endpoint_returns_global_script_slots(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->putJson('/api/admin/global-scripts', [
                'head_code' => '<meta name="global-public" content="1">',
                'body_code' => '<script>window.globalPublicBody=true</script>',
                'footer_code' => '<script>window.globalPublicFooter=true</script>',
            ])
            ->assertOk();

        $this->getJson('/api/global-scripts')
            ->assertOk()
            ->assertJsonPath('data.head_code', '<meta name="global-public" content="1">')
            ->assertJsonPath('data.body_code', '<script>window.globalPublicBody=true</script>')
            ->assertJsonPath('data.footer_code', '<script>window.globalPublicFooter=true</script>');
    }
}
