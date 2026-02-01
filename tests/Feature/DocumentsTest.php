<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function testItCanListDocuments()
    {
        $user = User::factory()
            ->has(Document::factory()->count(10))
            ->create();

        $this->actingAs($user);

        $this->getJson('/api/documents')
            ->assertJsonCount(10, 'data')
            ->assertSuccessful();
    }

    public function testUserCannotViewOtherUsersDocumentsInList()
    {
        Document::factory()->create(['owner_id' => $this->otherUser->id]);
        Document::factory()->create(['owner_id' => $this->user->id]);

        $this->actingAs($this->user);

        $this->getJson('/api/documents')
            ->assertJsonCount(1, 'data')
            ->assertSuccessful();
    }

    public function testItCanListADocument()
    {
        $user = User::factory()
            ->create();

        $document = Document::factory()
            ->for($user, 'owner')
            ->create();

        $this->actingAs($user);

        $this->getJson("/api/documents/{$document->id}")
            ->assertSuccessful();
    }

    public function testOnlyDocumentOwnersCanViewDocument()
    {
        $document = Document::factory()
            ->for($this->otherUser, 'owner')
            ->create();

        $this->actingAs($this->user);

        $this->getJson("/api/documents/{$document->id}")
            ->assertForbidden();
    }

    public function testItCanStoreADocument()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($this->user);

        $response = $this->postJson('/api/documents', [
            'name' => 'Contract',
            'expires_at' => now()->addWeek(),
            'file' => $file,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('documents', [
            'name' => 'Contract',
            'owner_id' => $this->user->id,
        ]);

        Storage::disk('local')->assertExists(Document::first()->path);
    }

    public function testDocumentCreationRequiresPdfFile()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $this->actingAs($this->user);

        $this->postJson('/api/documents', [
            'name' => 'Test Document',
            'expires_at' => now()->addDays(30)->toDateString(),
            'file' => $file,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function testDocumentCreationRequiresFutureExpiryDate()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($this->user);

        $this->postJson('/api/documents', [
            'name' => 'Test Document',
            'expires_at' => now()->subDay()->toDateString(),
            'file' => $file,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function testDocumentCreationCannotExceedFiveYears()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($this->user);

        $this->postJson('/api/documents', [
            'name' => 'Test Document',
            'expires_at' => now()->addYears(6)->toDateString(),
            'file' => $file,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function testDocumentCreationAllowsExactlyFiveYears()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->actingAs($this->user);

        $this->postJson('/api/documents', [
            'name' => 'Test Document',
            'expires_at' => now()->addYears(5)->toDateString(),
            'file' => $file,
        ])->assertSuccessful();
    }

    public function testUserCanArchiveTheirDocument()
    {
        $document = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user);

        $this->postJson("/api/documents/{$document->id}/archive")
            ->assertSuccessful();

        $this->assertNotNull($document->fresh()->archived_at);
    }

    public function testUserCanArchiveNonExpiredDocument()
    {
        $document = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($this->user);

        $this->postJson("/api/documents/{$document->id}/archive")
            ->assertSuccessful();

        $this->assertNotNull($document->fresh()->archived_at);
    }

    public function testUserCannotArchiveOtherUsersDocument()
    {
        $document = Document::factory()->create([
            'owner_id' => $this->otherUser->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user);

        $this->postJson("/api/documents/{$document->id}/archive")
            ->assertForbidden();
    }

    public function testUserCanDownloadTheirOwnDocument()
    {
        Storage::fake('local');

        $path = Storage::put('documents', UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'));

        $document = Document::factory()->create([
            'owner_id' => $this->user->id,
            'path' => $path,
        ]);

        $this->actingAs($this->user);

        $this->get("/api/documents/{$document->id}/download")
            ->assertSuccessful()
            ->assertDownload();
    }

    public function testUserCannotDownloadOtherUsersDocument()
    {
        Storage::fake('local');

        $path = Storage::put('documents', UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'));

        $document = Document::factory()->create([
            'owner_id' => $this->otherUser->id,
            'path' => $path,
        ]);

        $this->actingAs($this->user);

        $this->get("/api/documents/{$document->id}/download")
            ->assertForbidden();
    }

    public function testUnauthenticatedUserCannotAccessDocuments()
    {
        $this->getJson('/api/documents')
            ->assertUnauthorized();
    }

    public function testDocumentScopesWorkCorrectly()
    {
        // Create documents with different expiry dates
        $expiringSoon = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->addDays(3),
        ]);

        $expired = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->subDays(5),
        ]);

        $archived = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->subDays(10),
            'archived_at' => now(),
        ]);

        $farFuture = Document::factory()->create([
            'owner_id' => $this->user->id,
            'expires_at' => now()->addDays(30),
        ]);

        // Test expiringSoon scope
        $expiringSoonDocs = Document::expiringSoon()->get();
        $this->assertCount(1, $expiringSoonDocs);
        $this->assertEquals($expiringSoon->id, $expiringSoonDocs->first()->id);

        // Test expired scope
        $expiredDocs = Document::expired()->get();
        $this->assertCount(1, $expiredDocs);
        $this->assertEquals($expired->id, $expiredDocs->first()->id);

        // Test notArchived scope
        $notArchivedDocs = Document::notArchived()->get();
        $this->assertCount(3, $notArchivedDocs);
    }
}
