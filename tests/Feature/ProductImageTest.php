<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_uploads_replaces_and_removes_a_product_photo(): void
    {
        Storage::fake('uploads');
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);

        $first = $this->actingAs($owner)
            ->post("/api/products/{$product->id}/image", ['image' => UploadedFile::fake()->image('tea.jpg', 600, 600)], ['Accept' => 'application/json'])
            ->assertOk()->json('image_url');
        $this->assertStringStartsWith("/uploads/products/{$business->id}/", $first);
        Storage::disk('uploads')->assertExists(substr($first, strlen('/uploads/')));

        // A new photo replaces the old file.
        $second = $this->actingAs($owner)
            ->post("/api/products/{$product->id}/image", ['image' => UploadedFile::fake()->image('tea.png', 400, 400)], ['Accept' => 'application/json'])
            ->assertOk()->json('image_url');
        Storage::disk('uploads')->assertMissing(substr($first, strlen('/uploads/')));
        $this->assertSame($second, $this->actingAs($owner)->getJson('/api/bootstrap')->json('products.0.image_url'));

        $this->actingAs($owner)->deleteJson("/api/products/{$product->id}/image")->assertOk()->assertJson(['image_url' => null]);
        Storage::disk('uploads')->assertMissing(substr($second, strlen('/uploads/')));
    }

    public function test_only_real_images_under_two_megabytes_are_accepted(): void
    {
        Storage::fake('uploads');
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);

        $this->actingAs($owner)->post("/api/products/{$product->id}/image", ['image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->actingAs($owner)->post("/api/products/{$product->id}/image", ['image' => UploadedFile::fake()->image('big.jpg')->size(3000)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('message', 'The image must be 2 MB or smaller');
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_cashiers_and_other_businesses_cannot_change_photos(): void
    {
        Storage::fake('uploads');
        [$business] = $this->makeBusiness();
        [, $otherOwner] = $this->makeBusiness(['name' => 'Other']);
        $cashier = $this->addStaff($business, ['sales' => true]);
        $product = $this->product($business);
        $file = fn () => ['image' => UploadedFile::fake()->image('a.jpg', 100, 100)];

        $this->actingAs($cashier)->post("/api/products/{$product->id}/image", $file(), ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($otherOwner)->post("/api/products/{$product->id}/image", $file(), ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertNull($product->fresh()->image_url);
    }
}
