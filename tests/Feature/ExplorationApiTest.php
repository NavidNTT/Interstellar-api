<?php

namespace Tests\Feature;

use App\Models\CelestialBody;
use App\Models\Mission;
use App\Models\MissionDestination;
use Database\Seeders\ExplorationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExplorationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_catalog_matches_the_frontend_and_seeding_is_repeatable(): void
    {
        $unrelated = CelestialBody::factory()->create();
        $this->seed(ExplorationSeeder::class);
        $this->seed(ExplorationSeeder::class);
        $this->assertDatabaseCount('celestial_bodies', 4);
        $this->assertDatabaseCount('missions', 5);
        $this->assertDatabaseCount('mission_destinations', 8);
        $this->assertModelExists($unrelated);
        $response = $this->getJson('/api/celestial-bodies')->assertOk()->assertJsonCount(4, 'data');
        foreach (['sun', 'earth', 'mars'] as $slug) {
            $response->assertJsonFragment(['slug' => $slug]);
            $this->getJson('/api/celestial-bodies/'.$slug)->assertOk()->assertJsonPath('data.slug', $slug)->assertJsonMissingPath('data.id')->assertJsonMissingPath('data.created_at');
        }
        $this->getJson('/api/missions')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/missions/inner-survey')->assertOk()->assertJsonPath('data.destinations.0.body_slug', 'earth')->assertJsonPath('data.destinations.1.body_slug', 'mars')->assertJsonPath('data.destinations.1.sequence', 2);
        $this->getJson('/api/missions/stellar-neighborhood')->assertOk()->assertJsonPath('data.destinations.0.kind', 'inspect');
    }

    public function test_empty_collections_are_successful_and_unknown_or_invalid_routes_are_safe(): void
    {
        $this->getJson('/api/celestial-bodies')->assertOk()->assertExactJson(['data' => []]);
        $this->getJson('/api/missions')->assertOk()->assertExactJson(['data' => []]);
        foreach (['celestial-bodies/unknown', 'missions/unknown', 'missions/INVALID!', 'celestial-bodies/1', 'unknown'] as $route) {
            $this->getJson('/api/'.$route)->assertNotFound()->assertExactJson(['error' => ['code' => 'not_found', 'message' => 'The requested resource was not found.']]);
        }
        $this->postJson('/api/missions')->assertStatus(405)->assertJsonPath('error.code', 'method_not_allowed');
    }

    public function test_inactive_resources_are_not_public(): void
    {
        $body = CelestialBody::factory()->create(['is_active' => false]);
        $mission = Mission::factory()->create(['is_active' => false]);
        $this->getJson('/api/celestial-bodies/'.$body->slug)->assertNotFound();
        $this->getJson('/api/missions/'.$mission->slug)->assertNotFound();
        $this->getJson('/api/celestial-bodies')->assertJsonCount(0, 'data');
        $this->getJson('/api/missions')->assertJsonCount(0, 'data');
    }

    public function test_missing_or_unavailable_destinations_return_sanitized_errors(): void
    {
        config(['app.debug' => true]);
        $mission = Mission::factory()->create();
        $this->getJson('/api/missions/'.$mission->slug)->assertStatus(500)->assertExactJson(['error' => ['code' => 'catalog_error', 'message' => 'The catalog could not be loaded.']]);
        $destination = MissionDestination::factory()->for($mission)->create();
        $destination->celestialBody->update(['is_active' => false]);
        $this->getJson('/api/missions')->assertStatus(500)->assertJsonMissingPath('trace');
    }

    public function test_malformed_body_and_out_of_order_destinations_are_rejected(): void
    {
        $body = CelestialBody::factory()->create(['color' => 'invalid']);
        $this->getJson('/api/celestial-bodies/'.$body->slug)->assertStatus(500)->assertJsonPath('error.code', 'catalog_error');
        $destination = MissionDestination::factory()->create(['sequence' => 2]);
        $this->getJson('/api/missions/'.$destination->mission->slug)->assertStatus(500);
    }

    public function test_database_failure_does_not_disclose_query_or_connection_details(): void
    {
        DB::statement('DROP TABLE celestial_bodies');
        $this->getJson('/api/celestial-bodies')->assertStatus(503)->assertExactJson(['error' => ['code' => 'service_unavailable', 'message' => 'Catalog storage is temporarily unavailable.']]);
    }

    public function test_cors_allows_configured_frontend_and_rejects_unlisted_origins(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000'])->getJson('/api/celestial-bodies')->assertOk()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $this->withHeaders(['Origin' => 'https://unlisted.invalid'])->getJson('/api/celestial-bodies')->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->withHeaders(['Origin' => 'http://localhost:3000', 'Access-Control-Request-Method' => 'GET'])->options('/api/missions')->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }
}
