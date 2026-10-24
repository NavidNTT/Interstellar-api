<?php

namespace Tests\Feature;

use App\Models\CelestialBody;
use App\Models\Mission;
use App\Models\MissionDestination;
use Database\Seeders\ExplorationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExplorationApiTest extends TestCase
{
    use RefreshDatabase;

    public static function apiPrefixes(): array
    {
        return ['legacy' => ['/api'], 'v1' => ['/api/v1']];
    }

    #[DataProvider('apiPrefixes')]
    public function test_public_collection_and_detail_contracts_are_stable(string $prefix): void
    {
        $this->seed(ExplorationSeeder::class);
        // Independent assertions pin the public wire contract; Resources remain the only serializer.
        $bodyTypes = array_fill_keys(['slug', 'name', 'type', 'short_description', 'full_description', 'diameter', 'mass', 'gravity', 'average_temperature', 'distance_from_sun', 'orbital_period', 'rotation_period', 'number_of_moons', 'number_of_planets', 'atmosphere', 'composition', 'color'], 'string');
        $bodyTypes += ['scene_radius' => 'number', 'scene_orbit_radius' => 'number', 'interesting_facts' => 'array'];
        $missionTypes = array_fill_keys(['slug', 'title', 'description', 'objective', 'category', 'difficulty', 'estimated_duration', 'completion_label'], 'string');
        $missionTypes['destinations'] = 'array';
        $destinationTypes = ['slug' => 'string', 'body_slug' => 'string', 'sequence' => 'integer', 'kind' => 'string', 'objective' => 'string'];
        foreach (['celestial-bodies' => $bodyTypes, 'missions' => $missionTypes] as $resource => $types) {
            $response = $this->getJson($prefix.'/'.$resource)->assertOk()->assertHeader('Content-Type', 'application/json');
            $this->assertSame(['data'], array_keys($response->json()));
            $this->assertTrue(array_is_list($response->json('data')));
            $this->assertSame($this->getJson('/api/'.$resource)->json(), $this->getJson('/api/v1/'.$resource)->json());
            foreach ($response->json('data') as $record) {
                $this->assertPublicTypes($record, $types);
                $this->getJson($prefix.'/'.$resource.'/'.$record['slug'])->assertOk()->assertExactJson(['data' => $record]);
                $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $record['slug']);
                if ($resource === 'celestial-bodies') {
                    $this->assertContains($record['type'], ['star', 'planet']);
                    $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $record['color']);
                    $this->assertGreaterThan(0, $record['scene_radius']);
                    $this->assertLessThanOrEqual(10, $record['scene_radius']);
                    $this->assertGreaterThanOrEqual(0, $record['scene_orbit_radius']);
                    $this->assertLessThanOrEqual(100, $record['scene_orbit_radius']);
                    $this->assertNotEmpty($record['interesting_facts']);
                    $this->assertTrue(array_is_list($record['interesting_facts']));
                    foreach ($record['interesting_facts'] as $fact) {
                        $this->assertIsString($fact);
                        $this->assertNotSame('', trim($fact));
                    }
                } else {
                    $this->assertContains($record['category'], ['observation', 'travel', 'science']);
                    $this->assertContains($record['difficulty'], ['introductory', 'standard']);
                    $this->assertNotEmpty($record['destinations']);
                    $this->assertTrue(array_is_list($record['destinations']));
                    foreach ($record['destinations'] as $index => $destination) {
                        $this->assertPublicTypes($destination, $destinationTypes);
                        $this->assertSame($index + 1, $destination['sequence']);
                        $this->assertContains($destination['kind'], ['visit', 'inspect']);
                        $this->getJson($prefix.'/celestial-bodies/'.$destination['body_slug'])->assertOk();
                    }
                }
            }
        }
    }

    /** @param array<string, mixed> $record
     * @param  array<string, string>  $types
     */
    private function assertPublicTypes(array $record, array $types): void
    {
        $this->assertEqualsCanonicalizing(array_keys($types), array_keys($record));
        foreach ($types as $field => $type) {
            if ($type === 'number') {
                $this->assertTrue(is_int($record[$field]) || is_float($record[$field]), $field);
            } else {
                $this->assertSame($type, gettype($record[$field]), $field);
                if ($type === 'string') {
                    $this->assertNotSame('', trim($record[$field]), $field);
                }
            }
        }
    }

    #[DataProvider('apiPrefixes')]
    public function test_seeded_bodies_match_the_seed_source_by_slug(string $prefix): void
    {
        $this->seed(ExplorationSeeder::class);
        $source = json_decode(file_get_contents(database_path('seeders/data/exploration.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(9, $source['bodies']);
        $this->assertSame(['sun', 'earth', 'mars', 'mercury', 'venus', 'jupiter', 'saturn', 'uranus', 'neptune'], array_column($source['bodies'], 'slug'));
        $collection = $this->getJson($prefix.'/celestial-bodies')->assertOk()->assertJsonCount(9, 'data');
        // The API orders by scene_orbit_radius, so source and response records are matched by slug.
        $this->assertEqualsCanonicalizing(array_column($source['bodies'], 'slug'), array_column($collection->json('data'), 'slug'));
        foreach ($source['bodies'] as $body) {
            $record = $this->getJson($prefix.'/celestial-bodies/'.$body['slug'])->assertOk()->json('data');
            foreach ($record as $field => $actual) {
                $expected = $body[$field];
                if (is_int($expected) || is_float($expected)) {
                    $this->assertEqualsWithDelta((float) $expected, (float) $actual, 1e-9, $body['slug'].'.'.$field);
                } else {
                    $this->assertSame($expected, $actual, $body['slug'].'.'.$field);
                }
            }
            $this->assertTrue(CelestialBody::query()->where('slug', $body['slug'])->sole()->is_active);
        }
    }

    #[DataProvider('apiPrefixes')]
    public function test_destination_order_uses_sequence_instead_of_insertion_order(string $prefix): void
    {
        $mission = Mission::factory()->create();
        $last = MissionDestination::factory()->for($mission)->create(['sequence' => 2, 'kind' => 'inspect']);
        $first = MissionDestination::factory()->for($mission)->create(['sequence' => 1]);
        $expected = [$first->slug, $last->slug];
        $this->assertSame($expected, array_column($this->getJson($prefix.'/missions/'.$mission->slug)->assertOk()->json('data.destinations'), 'slug'));
        $this->assertSame($expected, array_column($this->getJson($prefix.'/missions')->assertOk()->json('data.0.destinations'), 'slug'));
    }

    #[DataProvider('apiPrefixes')]
    public function test_seeded_catalog_matches_the_frontend_and_seeding_is_repeatable(string $prefix): void
    {
        $unrelated = CelestialBody::factory()->create();
        $this->seed(ExplorationSeeder::class);
        $this->seed(ExplorationSeeder::class);
        // Nine seeded bodies plus the unrelated factory body.
        $this->assertDatabaseCount('celestial_bodies', 10);
        $this->assertDatabaseCount('missions', 5);
        $this->assertDatabaseCount('mission_destinations', 8);
        $this->assertModelExists($unrelated);
        // The collection also returns the unrelated active factory body.
        $response = $this->getJson($prefix.'/celestial-bodies')->assertOk()->assertJsonCount(10, 'data');
        foreach (['sun', 'mercury', 'venus', 'earth', 'mars', 'jupiter', 'saturn', 'uranus', 'neptune'] as $slug) {
            $response->assertJsonFragment(['slug' => $slug]);
            $this->getJson($prefix.'/celestial-bodies/'.$slug)->assertOk()->assertJsonPath('data.slug', $slug)->assertJsonMissingPath('data.id')->assertJsonMissingPath('data.created_at');
        }
        $this->getJson($prefix.'/missions')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson($prefix.'/missions/inner-survey')->assertOk()->assertJsonPath('data.destinations.0.body_slug', 'earth')->assertJsonPath('data.destinations.1.body_slug', 'mars')->assertJsonPath('data.destinations.1.sequence', 2);
        $this->getJson($prefix.'/missions/stellar-neighborhood')->assertOk()->assertJsonPath('data.destinations.0.kind', 'inspect');
    }

    #[DataProvider('apiPrefixes')]
    public function test_empty_collections_are_successful_and_unknown_or_invalid_routes_are_safe(string $prefix): void
    {
        $this->getJson($prefix.'/celestial-bodies')->assertOk()->assertExactJson(['data' => []]);
        $this->getJson($prefix.'/missions')->assertOk()->assertExactJson(['data' => []]);
        foreach (['celestial-bodies/unknown', 'missions/unknown', 'missions/INVALID!', 'celestial-bodies/1', 'unknown'] as $route) {
            $this->getJson($prefix.'/'.$route)->assertNotFound()->assertExactJson(['error' => ['code' => 'not_found', 'message' => 'The requested resource was not found.']]);
        }
        $this->postJson($prefix.'/missions')->assertStatus(405)->assertHeader('Allow', 'GET, HEAD')->assertExactJson(['error' => ['code' => 'method_not_allowed', 'message' => 'This API is read-only.']]);
    }

    #[DataProvider('apiPrefixes')]
    public function test_inactive_resources_are_not_public(string $prefix): void
    {
        $body = CelestialBody::factory()->create(['is_active' => false]);
        $mission = Mission::factory()->create(['is_active' => false]);
        $this->getJson($prefix.'/celestial-bodies/'.$body->slug)->assertNotFound();
        $this->getJson($prefix.'/missions/'.$mission->slug)->assertNotFound();
        $this->getJson($prefix.'/celestial-bodies')->assertJsonCount(0, 'data');
        $this->getJson($prefix.'/missions')->assertJsonCount(0, 'data');
    }

    #[DataProvider('apiPrefixes')]
    public function test_missing_or_unavailable_destinations_return_sanitized_errors(string $prefix): void
    {
        config(['app.debug' => true]);
        $mission = Mission::factory()->create();
        $this->getJson($prefix.'/missions/'.$mission->slug)->assertStatus(500)->assertExactJson(['error' => ['code' => 'catalog_error', 'message' => 'The catalog could not be loaded.']]);
        $destination = MissionDestination::factory()->for($mission)->create();
        $destination->celestialBody->update(['is_active' => false]);
        $this->getJson($prefix.'/missions')->assertStatus(500)->assertJsonMissingPath('trace');
    }

    #[DataProvider('apiPrefixes')]
    public function test_malformed_body_and_out_of_order_destinations_are_rejected(string $prefix): void
    {
        $body = CelestialBody::factory()->create(['color' => 'invalid']);
        $this->getJson($prefix.'/celestial-bodies/'.$body->slug)->assertStatus(500)->assertJsonPath('error.code', 'catalog_error');
        $destination = MissionDestination::factory()->create(['sequence' => 2]);
        $this->getJson($prefix.'/missions/'.$destination->mission->slug)->assertStatus(500);
    }

    #[DataProvider('apiPrefixes')]
    public function test_database_failure_does_not_disclose_query_or_connection_details(string $prefix): void
    {
        DB::statement('DROP TABLE celestial_bodies');
        $this->getJson($prefix.'/celestial-bodies')->assertStatus(503)->assertExactJson(['error' => ['code' => 'service_unavailable', 'message' => 'Catalog storage is temporarily unavailable.']]);
    }

    #[DataProvider('apiPrefixes')]
    public function test_cors_allows_configured_frontend_and_rejects_unlisted_origins(string $prefix): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000'])->getJson($prefix.'/celestial-bodies')->assertOk()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $this->withHeaders(['Origin' => 'https://unlisted.invalid'])->getJson($prefix.'/celestial-bodies')->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->withHeaders(['Origin' => 'http://localhost:3000', 'Access-Control-Request-Method' => 'GET'])->options($prefix.'/missions')->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }
}
