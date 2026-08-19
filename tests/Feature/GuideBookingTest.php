<?php

namespace Tests\Feature;

use App\Enums\GuideBookingStatus;
use App\Models\Guide;
use App\Models\GuideBooking;
use App\Models\GuideBookingLog;
use App\Models\User;
use App\Services\CheckGuideAvailability;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

class GuideBookingTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api';

    // ─── Tourist: Book a Guide ───────────────────────

    public function test_tourist_can_book_guide(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create();

        // Mock availability check (DATE_ADD is MySQL-only, fails on SQLite)
        $mock = Mockery::mock(CheckGuideAvailability::class);
        $mock->shouldReceive('handle')->once()->andReturn(false);
        App::instance(CheckGuideAvailability::class, $mock);

        $payload = [
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'day_count' => 3,
            'description' => 'I want a guided tour',
        ];

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'day_count' => 3,
                ],
            ]);

        $this->assertDatabaseHas('guide_bookings', [
            'tourist_id' => $tourist->id,
            'guide_id' => $guide->id,
            'status' => GuideBookingStatus::Pending,
        ]);

        Mockery::close();
    }

    public function test_booking_calculates_total_price(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create(['daily_price' => 50]);

        $mock = Mockery::mock(CheckGuideAvailability::class);
        $mock->shouldReceive('handle')->once()->andReturn(false);
        App::instance(CheckGuideAvailability::class, $mock);

        $payload = [
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'day_count' => 4,
            'description' => 'I want a guided tour',
        ];

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('guide_bookings', [
            'tourist_id' => $tourist->id,
            'guide_id' => $guide->id,
            'total_price' => 200.00,
        ]);

        Mockery::close();
    }

    public function test_tourist_cannot_book_unavailable_guide(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create([
            'user_id' => User::factory()->guide()->unavailable()->create()->id,
        ]);

        $payload = [
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'day_count' => 2,
            'description' => 'I want a guided tour',
        ];

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", $payload);

        $response->assertStatus(422);
    }

    public function test_tourist_cannot_book_guide_with_past_date(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create();

        $payload = [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'day_count' => 2,
            'description' => 'I want a guided tour',
        ];

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    public function test_tourist_cannot_book_guide_with_missing_fields(): void
    {
        $tourist = User::factory()->tourist()->create();
        $guide = Guide::factory()->create();

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'day_count', 'description']);
    }

    public function test_guide_cannot_book_themselves(): void
    {
        // A guide trying to act as tourist - the role middleware should block this
        $guideUser = User::factory()->guide()->create();
        $guide = Guide::factory()->create(['user_id' => $guideUser->id]);
        $otherGuide = Guide::factory()->create();

        $payload = [
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'day_count' => 2,
            'description' => 'I want a guided tour',
        ];

        $response = $this->actingAs($guideUser)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$otherGuide->id}/book", $payload);

        // Should fail due to role:tourist middleware
        $response->assertStatus(400);
    }

    // ─── Tourist: List Bookings ──────────────────────

    public function test_tourist_can_list_own_bookings(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->create(['tourist_id' => $tourist->id]);

        $response = $this->actingAs($tourist)
            ->getJson("{$this->baseUrl}/tourist/guide-bookings");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_tourist_sees_only_own_bookings(): void
    {
        $tourist = User::factory()->tourist()->create();
        $otherTourist = User::factory()->tourist()->create();

        $myBooking = GuideBooking::factory()->create(['tourist_id' => $tourist->id]);
        $otherBooking = GuideBooking::factory()->create(['tourist_id' => $otherTourist->id]);

        $response = $this->actingAs($tourist)
            ->getJson("{$this->baseUrl}/tourist/guide-bookings");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    // ─── Tourist: Show Booking ───────────────────────

    public function test_tourist_can_show_own_booking(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->create(['tourist_id' => $tourist->id]);

        $response = $this->actingAs($tourist)
            ->getJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── Tourist: Cancel Booking ─────────────────────

    public function test_tourist_can_cancel_pending_booking(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'tourist_id' => $tourist->id,
            'start_date' => now()->addDays(20),
        ]);

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}/cancel");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('guide_bookings', [
            'id' => $booking->id,
            'status' => GuideBookingStatus::CancelledByTourist,
        ]);
    }

    public function test_tourist_can_cancel_accepted_booking(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->accepted()->create([
            'tourist_id' => $tourist->id,
            'start_date' => now()->addDays(20),
        ]);

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}/cancel");

        $response->assertOk();

        $this->assertDatabaseHas('guide_bookings', [
            'id' => $booking->id,
            'status' => GuideBookingStatus::CancelledByTourist,
        ]);
    }

    public function test_tourist_cannot_cancel_rejected_booking(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->rejected()->create([
            'tourist_id' => $tourist->id,
            'start_date' => now()->addDays(20),
        ]);

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_tourist_cannot_cancel_within_7_days(): void
    {
        $tourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->accepted()->create([
            'tourist_id' => $tourist->id,
            'start_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_tourist_cannot_cancel_other_tourists_booking(): void
    {
        $tourist = User::factory()->tourist()->create();
        $otherTourist = User::factory()->tourist()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'tourist_id' => $otherTourist->id,
            'start_date' => now()->addDays(20),
        ]);

        $response = $this->actingAs($tourist)
            ->postJson("{$this->baseUrl}/tourist/guide-bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    // ─── Guide: List Bookings ────────────────────────

    public function test_guide_can_list_own_bookings(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->create(['guide_id' => $guide->id]);

        $response = $this->actingAs($guide->user)
            ->getJson("{$this->baseUrl}/guide/bookings");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── Guide: Accept Booking ───────────────────────

    public function test_guide_can_accept_pending_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/accept");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('guide_bookings', [
            'id' => $booking->id,
            'status' => GuideBookingStatus::Accepted,
        ]);
    }

    public function test_guide_cannot_accept_already_accepted_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->accepted()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/accept");

        $response->assertStatus(403);
    }

    public function test_guide_cannot_accept_rejected_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->rejected()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/accept");

        $response->assertStatus(403);
    }

    // ─── Guide: Reject Booking ───────────────────────

    public function test_guide_can_reject_pending_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/reject");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('guide_bookings', [
            'id' => $booking->id,
            'status' => GuideBookingStatus::Rejected,
        ]);
    }

    public function test_guide_cannot_reject_other_guides_booking(): void
    {
        $guide = Guide::factory()->create();
        $otherGuide = Guide::factory()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'guide_id' => $otherGuide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/reject");

        $response->assertStatus(403);
    }

    // ─── Guide: Cancel Booking ───────────────────────

    public function test_guide_can_cancel_accepted_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->accepted()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/cancel");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('guide_bookings', [
            'id' => $booking->id,
            'status' => GuideBookingStatus::CancelledByGuide,
        ]);
    }

    public function test_guide_cannot_cancel_pending_booking(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'guide_id' => $guide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_guide_cannot_cancel_other_guides_booking(): void
    {
        $guide = Guide::factory()->create();
        $otherGuide = Guide::factory()->create();
        $booking = GuideBooking::factory()->accepted()->create([
            'guide_id' => $otherGuide->id,
        ]);

        $response = $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    // ─── Role Enforcement ────────────────────────────

    public function test_tourist_cannot_access_guide_booking_routes(): void
    {
        $tourist = User::factory()->tourist()->create();

        $response = $this->actingAs($tourist)
            ->getJson("{$this->baseUrl}/guide/bookings");

        $response->assertStatus(400);
    }

    public function test_guide_cannot_access_tourist_booking_routes(): void
    {
        $guide = Guide::factory()->create();

        $response = $this->actingAs($guide->user)
            ->getJson("{$this->baseUrl}/tourist/guide-bookings");

        $response->assertStatus(400);
    }

    // ─── Booking Log (Observer) ──────────────────────

    public function test_booking_status_change_creates_log(): void
    {
        $guide = Guide::factory()->create();
        $booking = GuideBooking::factory()->pending()->create([
            'guide_id' => $guide->id,
        ]);

        $this->actingAs($guide->user)
            ->postJson("{$this->baseUrl}/guide/bookings/{$booking->id}/accept");

        $this->assertDatabaseHas('guide_booking_logs', [
            'booking_id' => $booking->id,
            'old_status' => GuideBookingStatus::Pending,
            'new_status' => GuideBookingStatus::Accepted,
        ]);
    }

    // ─── Unauthenticated Access ──────────────────────

    public function test_unauthenticated_user_cannot_book(): void
    {
        $guide = Guide::factory()->create();

        $response = $this->postJson("{$this->baseUrl}/tourist/guide-bookings/{$guide->id}/book", [
            'start_date' => now()->addDays(15)->format('Y-m-d'),
            'day_count' => 2,
            'description' => 'test',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_list_bookings(): void
    {
        $response = $this->getJson("{$this->baseUrl}/tourist/guide-bookings");

        $response->assertStatus(401);
    }
}
